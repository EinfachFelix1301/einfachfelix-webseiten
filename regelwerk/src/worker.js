import {
  hashPassword,
  verifyPassword,
  createSession,
  sessionCookie,
  clearCookie,
  getUser,
  readSessionToken,
  ensureBootstrapAdmin,
} from "./auth.js";

const json = (data, init = {}) =>
  new Response(JSON.stringify(data), {
    ...init,
    headers: {
      "content-type": "application/json; charset=utf-8",
      "cache-control": "no-store",
      ...(init.headers || {}),
    },
  });

const err = (status, message) => json({ error: message }, { status });

// Zeitkonstanter String-Vergleich (über SHA-256, damit die Längen gleich sind)
async function safeEqual(a, b) {
  const enc = new TextEncoder();
  const [ha, hb] = await Promise.all([
    crypto.subtle.digest("SHA-256", enc.encode(String(a))),
    crypto.subtle.digest("SHA-256", enc.encode(String(b))),
  ]);
  return crypto.subtle.timingSafeEqual(ha, hb);
}

function slugify(s) {
  return String(s)
    .normalize("NFD")
    .replace(/\p{M}/gu, "")
    .toLowerCase()
    .replace(/ß/g, "ss")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

// Fremd-HTML (öffentliche Vorschläge) serverseitig entschärfen (HTMLRewriter):
// gefährliche Elemente entfernen, on*-Handler/srcdoc/formaction streichen,
// javascript:/vbscript:-URLs (und data: außer Rasterbildern) entfernen. Frontend bereinigt zusätzlich (DOMPurify).
const DANGEROUS_TAGS = [
  "script", "style", "iframe", "frame", "frameset", "object", "embed", "applet", "link", "meta", "base",
  "form", "input", "button", "textarea", "select", "option", "svg", "math", "template", "noscript",
  "noembed", "noframes", "xmp", "plaintext", "title",
];
const URL_ATTRS = new Set(["href", "src", "xlink:href", "action", "formaction", "background", "poster", "data", "srcset", "ping"]);
function decodeAttr(v) {
  const cp = (n) => (n >= 0 && n <= 0x10ffff ? String.fromCodePoint(n) : "");
  return String(v)
    .replace(/&#x([0-9a-f]+);?/gi, (_, h) => cp(parseInt(h, 16)))
    .replace(/&#(\d+);?/g, (_, d) => cp(Number(d)))
    .replace(/&colon;/gi, ":")
    .replace(/&(tab|newline);/gi, "")
    .replace(/[\u0000-\u0020\u007f-\u009f]/g, "");
}
async function sanitizeHtml(html) {
  const src = String(html || "");
  if (!src) return "";
  const rw = new HTMLRewriter();
  for (const tag of DANGEROUS_TAGS) rw.on(tag, { element(el) { el.remove(); } });
  const res = rw
    .on("*", {
      element(el) {
        for (const [name] of [...el.attributes]) {
          const n = name.toLowerCase();
          if (n.startsWith("on") || n === "srcdoc" || n === "formaction") {
            el.removeAttribute(name);
          } else if (URL_ATTRS.has(n)) {
            const v = decodeAttr(el.getAttribute(name) || "");
            // data: nur für einfache Rasterbilder erlauben (eingefügte Bilder)
            if (/(javascript|vbscript):/i.test(v) || (/data:/i.test(v) && !/^data:image\/(png|jpe?g|gif|webp);/i.test(v))) {
              el.removeAttribute(name);
            }
          }
        }
      },
    })
    .transform(new Response(src, { headers: { "content-type": "text/html; charset=utf-8" } }));
  return res.text();
}

async function audit(env, actor, action, target, details) {
  await env.DB.prepare(
    `INSERT INTO audit_log (actor, action, target, details, created_at) VALUES (?, ?, ?, ?, ?)`
  )
    .bind(actor, action, target || null, details || null, Date.now())
    .run();
}

// ---------- Public API ----------

async function listLawsPublic(env) {
  const { results } = await env.DB.prepare(
    `SELECT slug, title, category, html, text, download_path AS downloadPath, word_count AS wordCount, ref_count AS refCount, updated_at AS updatedAt, prev_html AS prevHtml, prev_updated_at AS prevUpdatedAt
     FROM laws ORDER BY position ASC, title ASC`
  ).all();
  return results || [];
}

async function listChangelog(env, limit = 50) {
  const { results } = await env.DB.prepare(
    `SELECT slug, title, category, updated_at AS updatedAt, updated_by AS updatedBy
     FROM laws WHERE updated_at IS NOT NULL ORDER BY updated_at DESC LIMIT ?`
  ).bind(limit).all();
  const { results: approved } = await env.DB.prepare(
    `SELECT id, slug, kind, proposer_name AS proposerName, summary, reviewed_at AS reviewedAt, reviewed_by AS reviewedBy
     FROM law_proposals WHERE status = 'approved' ORDER BY reviewed_at DESC LIMIT ?`
  ).bind(limit).all();
  return { recentEdits: results || [], approvedProposals: approved || [] };
}

// ---------- API router ----------

async function handleApi(req, env, url, user) {
  const p = url.pathname;
  const m = req.method;

  // Public read (CORS offen, damit bloodline.cc die Regelwerk-Änderungen anzeigen kann)
  const CORS = { "access-control-allow-origin": "*" };
  if (p === "/api/laws" && m === "GET") {
    return json({ laws: await listLawsPublic(env) }, { headers: CORS });
  }

  if (p === "/api/changelog" && m === "GET") {
    return json(await listChangelog(env), { headers: CORS });
  }

  // Public: list pending proposals (titles only, no content) per slug
  if (p === "/api/pending-proposals" && m === "GET") {
    const { results } = await env.DB.prepare(
      `SELECT id, slug, kind, proposer_name AS proposerName, summary, created_at AS createdAt
       FROM law_proposals WHERE status = 'pending' ORDER BY created_at DESC LIMIT 100`
    ).all();
    return json({ proposals: results || [] });
  }

  // Public proposal submission
  if (p === "/api/proposals" && m === "POST") {
    const body = await req.json().catch(() => ({}));
    const kind = body.kind === "new" ? "new" : body.kind === "delete" ? "delete" : "edit";
    const proposerName = String(body.proposerName || "").trim().slice(0, 80);
    const proposerContact = String(body.proposerContact || "").trim().slice(0, 120);
    const slug = body.slug ? String(body.slug).trim() : null;
    const proposedTitle = String(body.proposedTitle || "").trim().slice(0, 200);
    const proposedCategory = String(body.proposedCategory || "").trim().slice(0, 60);
    const proposedHtml = await sanitizeHtml(String(body.proposedHtml || ""));
    const summary = String(body.summary || "").trim().slice(0, 1000);
    if (!proposerName) return err(400, "Name fehlt");
    if (!summary) return err(400, "Begründung fehlt");
    if (kind !== "delete" && !proposedHtml) return err(400, "Vorgeschlagener Inhalt fehlt");
    if (kind === "edit" && !slug) return err(400, "Slug fehlt für Änderungsvorschlag");
    if (kind === "new" && !proposedTitle) return err(400, "Titel für neues Gesetz fehlt");

    let currentHtml = null;
    if (slug) {
      const cur = await env.DB.prepare(`SELECT html FROM laws WHERE slug = ?`).bind(slug).first();
      if (kind === "edit" && !cur) return err(404, "Gesetz nicht gefunden");
      currentHtml = cur?.html || null;
    }
    const r = await env.DB.prepare(
      `INSERT INTO law_proposals (slug, kind, proposer_name, proposer_contact, current_html, proposed_title, proposed_category, proposed_html, summary, status, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)`
    )
      .bind(
        slug,
        kind,
        proposerName,
        proposerContact || null,
        currentHtml,
        proposedTitle || null,
        proposedCategory || null,
        proposedHtml || null,
        summary,
        Date.now()
      )
      .run();
    return json({ ok: true, id: r.meta.last_row_id });
  }

  if (p === "/api/me" && m === "GET") {
    return json({ user });
  }

  // Bot-Login: Discord-Bot (Rolle highteam) mintet eine Admin-Session.
  // Geschützt durch geteiltes Secret (env.BOT_SECRET) zwischen Bot und Worker.
  if (p === "/api/bot/login" && m === "POST") {
    const provided = (req.headers.get("x-bot-secret") || "").trim();
    const expected = (env.BOT_SECRET || "").trim();
    if (!expected || !(await safeEqual(provided, expected))) return err(403, "Forbidden");
    const body = await req.json().catch(() => ({}));
    const did = String(body.discordId || "").trim();
    if (!/^\d{5,25}$/.test(did)) return err(400, "discordId ungültig");
    const display = String(body.username || "").trim().slice(0, 60) || ("discord_" + did);
    const handle = "discord_" + did;
    let u = await env.DB.prepare(`SELECT id FROM users WHERE username = ?`).bind(handle).first();
    if (!u) {
      const { hash, salt } = await hashPassword(crypto.randomUUID());
      const r = await env.DB.prepare(
        `INSERT INTO users (username, password_hash, password_salt, role, display_name, created_at) VALUES (?, ?, ?, 'admin', ?, ?)`
      ).bind(handle, hash, salt, display, Date.now()).run();
      u = { id: r.meta.last_row_id };
    } else {
      await env.DB.prepare(`UPDATE users SET role = 'admin', display_name = ? WHERE id = ?`).bind(display, u.id).run();
    }
    const token = await createSession(env, u.id);
    await audit(env, handle, "bot.login", display, null);
    return json({ ok: true, token, loginUrl: `https://${env.ADMIN_HOST}/auth?token=${token}` });
  }

  // Auth: login (admin host only)
  if (p === "/api/login" && m === "POST") {
    const body = await req.json().catch(() => ({}));
    const username = String(body.username || "").trim().toLowerCase();
    const password = String(body.password || "");
    if (!username || !password) return err(400, "Missing credentials");
    const row = await env.DB.prepare(
      `SELECT id, password_hash, password_salt FROM users WHERE username = ?`
    )
      .bind(username)
      .first();
    if (!row) return err(401, "Invalid credentials");
    const ok = await verifyPassword(password, row.password_hash, row.password_salt);
    if (!ok) return err(401, "Invalid credentials");
    const token = await createSession(env, row.id);
    await env.DB.prepare(`UPDATE users SET last_login = ? WHERE id = ?`)
      .bind(Date.now(), row.id)
      .run();
    await audit(env, username, "login", null, null);
    return json(
      { ok: true },
      { headers: { "set-cookie": sessionCookie(token) } }
    );
  }

  if (p === "/api/logout" && m === "POST") {
    const token = readSessionToken(req);
    if (token) {
      await env.DB.prepare(`DELETE FROM sessions WHERE token = ?`).bind(token).run();
    }
    return json({ ok: true }, { headers: { "set-cookie": clearCookie() } });
  }

  // Access request — anyone can submit
  if (p === "/api/request-access" && m === "POST") {
    const body = await req.json().catch(() => ({}));
    const username = String(body.username || "").trim().toLowerCase();
    const password = String(body.password || "");
    const reason = String(body.reason || "").slice(0, 500);
    if (!/^[a-z0-9._-]{3,32}$/.test(username))
      return err(400, "Username invalid (3-32 chars, a-z 0-9 . _ -)");
    if (password.length < 8) return err(400, "Password min 8 chars");

    const exists = await env.DB.prepare(`SELECT 1 FROM users WHERE username = ?`)
      .bind(username)
      .first();
    if (exists) return err(409, "Username taken");
    const pending = await env.DB.prepare(
      `SELECT 1 FROM access_requests WHERE username = ? AND status = 'pending'`
    )
      .bind(username)
      .first();
    if (pending) return err(409, "Request already pending");

    const { hash, salt } = await hashPassword(password);
    await env.DB.prepare(
      `INSERT INTO access_requests (username, password_hash, password_salt, reason, status, created_at) VALUES (?, ?, ?, ?, 'pending', ?)`
    )
      .bind(username, hash, salt, reason, Date.now())
      .run();
    return json({ ok: true });
  }

  // Everything below requires auth
  if (!user) return err(401, "Auth required");

  // Editor + admin: list/edit laws
  if (p === "/api/admin/laws" && m === "GET") {
    const { results } = await env.DB.prepare(
      `SELECT slug, title, category, html, text, download_path AS downloadPath, word_count AS wordCount, ref_count AS refCount, position, updated_at AS updatedAt, updated_by AS updatedBy
       FROM laws ORDER BY position ASC, title ASC`
    ).all();
    return json({ laws: results || [] });
  }

  const lawMatch = p.match(/^\/api\/admin\/laws\/([^/]+)$/);
  if (lawMatch && m === "GET") {
    const row = await env.DB.prepare(
      `SELECT slug, title, category, html, text, download_path AS downloadPath, word_count AS wordCount, position, updated_at AS updatedAt, updated_by AS updatedBy
       FROM laws WHERE slug = ?`
    )
      .bind(lawMatch[1])
      .first();
    if (!row) return err(404, "Not found");
    return json({ law: row });
  }

  if (lawMatch && m === "PUT") {
    const body = await req.json().catch(() => ({}));
    const slug = lawMatch[1];
    const title = String(body.title || "").trim();
    const category = String(body.category || "").trim() || "Verwaltung";
    const html = String(body.html || "");
    const text = String(body.text || "");
    if (!title) return err(400, "Title required");
    const wc = (text.match(/\S+/g) || []).length;
    // Snapshot previous version for diff highlighting
    const prev = await env.DB.prepare(`SELECT html, updated_at FROM laws WHERE slug = ?`).bind(slug).first();
    if (!prev) return err(404, "Not found");
    const res = await env.DB.prepare(
      `UPDATE laws SET title = ?, category = ?, html = ?, text = ?, word_count = ?, updated_at = ?, updated_by = ?, prev_html = ?, prev_updated_at = ? WHERE slug = ?`
    )
      .bind(title, category, html, text, wc, Date.now(), user.username, prev.html, prev.updated_at, slug)
      .run();
    if (!res.success || res.meta.changes === 0) return err(404, "Not found");
    await audit(env, user.username, "law.update", slug, title);
    return json({ ok: true });
  }

  if (lawMatch && m === "DELETE") {
    if (user.role !== "admin") return err(403, "Admin only");
    await env.DB.prepare(`DELETE FROM laws WHERE slug = ?`).bind(lawMatch[1]).run();
    await audit(env, user.username, "law.delete", lawMatch[1], null);
    return json({ ok: true });
  }

  // Duplicate law
  const dupMatch = p.match(/^\/api\/admin\/laws\/([^/]+)\/duplicate$/);
  if (dupMatch && m === "POST") {
    const src = await env.DB.prepare(
      `SELECT title, category, html, text, word_count FROM laws WHERE slug = ?`
    ).bind(dupMatch[1]).first();
    if (!src) return err(404, "Quelle nicht gefunden");
    let newSlug = `${dupMatch[1]}-kopie`;
    let i = 2;
    while (await env.DB.prepare(`SELECT 1 FROM laws WHERE slug = ?`).bind(newSlug).first()) {
      newSlug = `${dupMatch[1]}-kopie-${i++}`;
    }
    const max = await env.DB.prepare(`SELECT COALESCE(MAX(position), 0) AS m FROM laws`).first();
    await env.DB.prepare(
      `INSERT INTO laws (slug, title, category, html, text, word_count, ref_count, position, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)`
    ).bind(
      newSlug,
      `${src.title} (Kopie)`,
      src.category,
      src.html,
      src.text,
      src.word_count,
      (max?.m || 0) + 1,
      Date.now(),
      user.username
    ).run();
    await audit(env, user.username, "law.duplicate", newSlug, src.title);
    return json({ ok: true, slug: newSlug });
  }

  if (p === "/api/admin/laws" && m === "POST") {
    const body = await req.json().catch(() => ({}));
    const title = String(body.title || "").trim();
    if (!title) return err(400, "Title required");
    const category = String(body.category || "Verwaltung").trim();
    const html = String(body.html || "");
    const text = String(body.text || "");
    let slug = body.slug ? slugify(body.slug) : slugify(title);
    if (!slug) return err(400, "Invalid slug");
    const exists = await env.DB.prepare(`SELECT 1 FROM laws WHERE slug = ?`)
      .bind(slug)
      .first();
    if (exists) slug = `${slug}-${Date.now().toString(36)}`;
    const wc = (text.match(/\S+/g) || []).length;
    const max = await env.DB.prepare(
      `SELECT COALESCE(MAX(position), 0) AS m FROM laws`
    ).first();
    const pos = (max?.m || 0) + 1;
    await env.DB.prepare(
      `INSERT INTO laws (slug, title, category, html, text, word_count, ref_count, position, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)`
    )
      .bind(slug, title, category, html, text, wc, pos, Date.now(), user.username)
      .run();
    await audit(env, user.username, "law.create", slug, title);
    return json({ ok: true, slug });
  }

  // Admin-only: requests
  if (p === "/api/admin/requests" && m === "GET") {
    if (user.role !== "admin") return err(403, "Admin only");
    const { results } = await env.DB.prepare(
      `SELECT id, username, reason, status, created_at AS createdAt, reviewed_at AS reviewedAt, reviewed_by AS reviewedBy
       FROM access_requests ORDER BY created_at DESC LIMIT 200`
    ).all();
    return json({ requests: results || [] });
  }

  const reqMatch = p.match(/^\/api\/admin\/requests\/(\d+)\/(approve|reject)$/);
  if (reqMatch && m === "POST") {
    if (user.role !== "admin") return err(403, "Admin only");
    const id = Number(reqMatch[1]);
    const action = reqMatch[2];
    const row = await env.DB.prepare(
      `SELECT username, password_hash, password_salt, status FROM access_requests WHERE id = ?`
    )
      .bind(id)
      .first();
    if (!row) return err(404, "Not found");
    if (row.status !== "pending") return err(409, "Already reviewed");
    if (action === "approve") {
      const dup = await env.DB.prepare(`SELECT 1 FROM users WHERE username = ?`)
        .bind(row.username)
        .first();
      if (dup) return err(409, "Username taken");
      await env.DB.prepare(
        `INSERT INTO users (username, password_hash, password_salt, role, created_at) VALUES (?, ?, ?, 'editor', ?)`
      )
        .bind(row.username, row.password_hash, row.password_salt, Date.now())
        .run();
    }
    await env.DB.prepare(
      `UPDATE access_requests SET status = ?, reviewed_at = ?, reviewed_by = ? WHERE id = ?`
    )
      .bind(action === "approve" ? "approved" : "rejected", Date.now(), user.username, id)
      .run();
    await audit(env, user.username, `request.${action}`, row.username, null);
    return json({ ok: true });
  }

  // Admin-only: users
  if (p === "/api/admin/users" && m === "GET") {
    if (user.role !== "admin") return err(403, "Admin only");
    const { results } = await env.DB.prepare(
      `SELECT id, username, display_name AS displayName, role, created_at AS createdAt, last_login AS lastLogin FROM users ORDER BY created_at DESC`
    ).all();
    return json({ users: results || [] });
  }

  const userMatch = p.match(/^\/api\/admin\/users\/(\d+)$/);
  if (userMatch && m === "DELETE") {
    if (user.role !== "admin") return err(403, "Admin only");
    const id = Number(userMatch[1]);
    if (id === user.id) return err(400, "Cannot delete self");
    await env.DB.prepare(`DELETE FROM sessions WHERE user_id = ?`).bind(id).run();
    await env.DB.prepare(`DELETE FROM users WHERE id = ?`).bind(id).run();
    await audit(env, user.username, "user.delete", String(id), null);
    return json({ ok: true });
  }

  if (p === "/api/admin/change-password" && m === "POST") {
    const body = await req.json().catch(() => ({}));
    const current = String(body.current || "");
    const next = String(body.next || "");
    if (next.length < 8) return err(400, "Password min 8 chars");
    const row = await env.DB.prepare(
      `SELECT password_hash, password_salt FROM users WHERE id = ?`
    )
      .bind(user.id)
      .first();
    if (!row) return err(404, "User not found");
    const ok = await verifyPassword(current, row.password_hash, row.password_salt);
    if (!ok) return err(401, "Current password wrong");
    const { hash, salt } = await hashPassword(next);
    await env.DB.prepare(
      `UPDATE users SET password_hash = ?, password_salt = ? WHERE id = ?`
    )
      .bind(hash, salt, user.id)
      .run();
    await env.DB.prepare(`DELETE FROM sessions WHERE user_id = ?`)
      .bind(user.id)
      .run();
    await audit(env, user.username, "password.change", null, null);
    return json({ ok: true });
  }

  // Admin: proposals
  if (p === "/api/admin/proposals" && m === "GET") {
    const { results } = await env.DB.prepare(
      `SELECT id, slug, kind, proposer_name AS proposerName, proposer_contact AS proposerContact,
              proposed_title AS proposedTitle, proposed_category AS proposedCategory,
              summary, status, created_at AS createdAt, reviewed_at AS reviewedAt, reviewed_by AS reviewedBy, review_note AS reviewNote
       FROM law_proposals ORDER BY
         CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END,
         created_at DESC LIMIT 200`
    ).all();
    return json({ proposals: results || [] });
  }

  const propMatch = p.match(/^\/api\/admin\/proposals\/(\d+)$/);
  if (propMatch && m === "GET") {
    const row = await env.DB.prepare(
      `SELECT id, slug, kind, proposer_name AS proposerName, proposer_contact AS proposerContact,
              current_html AS currentHtml, proposed_title AS proposedTitle, proposed_category AS proposedCategory,
              proposed_html AS proposedHtml, summary, status, created_at AS createdAt,
              reviewed_at AS reviewedAt, reviewed_by AS reviewedBy, review_note AS reviewNote
       FROM law_proposals WHERE id = ?`
    ).bind(Number(propMatch[1])).first();
    if (!row) return err(404, "Not found");
    return json({ proposal: row });
  }

  const propActMatch = p.match(/^\/api\/admin\/proposals\/(\d+)\/(approve|reject)$/);
  if (propActMatch && m === "POST") {
    if (user.role !== "admin") return err(403, "Admin only");
    const id = Number(propActMatch[1]);
    const action = propActMatch[2];
    const body = await req.json().catch(() => ({}));
    const note = String(body.note || "").slice(0, 500);
    const prop = await env.DB.prepare(
      `SELECT slug, kind, proposed_title, proposed_category, proposed_html, status FROM law_proposals WHERE id = ?`
    ).bind(id).first();
    if (!prop) return err(404, "Not found");
    if (prop.status !== "pending") return err(409, "Bereits geprüft");
    // Auch Alt-Vorschläge (vor Server-Bereinigung gespeichert) entschärfen
    prop.proposed_html = await sanitizeHtml(prop.proposed_html);

    if (action === "approve") {
      const now = Date.now();
      if (prop.kind === "edit" && prop.slug) {
        const cur = await env.DB.prepare(`SELECT html, updated_at FROM laws WHERE slug = ?`).bind(prop.slug).first();
        if (!cur) return err(404, "Gesetz inzwischen entfernt");
        const tmp = (prop.proposed_html || "").replace(/<[^>]+>/g, " ");
        const wc = (tmp.match(/\S+/g) || []).length;
        await env.DB.prepare(
          `UPDATE laws SET title = COALESCE(?, title), category = COALESCE(?, category), html = ?, text = ?, word_count = ?, updated_at = ?, updated_by = ?, prev_html = ?, prev_updated_at = ? WHERE slug = ?`
        ).bind(
          prop.proposed_title || null,
          prop.proposed_category || null,
          prop.proposed_html || "",
          tmp.trim(),
          wc,
          now,
          `proposal#${id}`,
          cur.html,
          cur.updated_at,
          prop.slug
        ).run();
      } else if (prop.kind === "new") {
        let slug = (prop.proposed_title || "untitled")
          .normalize("NFD").replace(/\p{M}/gu, "").toLowerCase()
          .replace(/ß/g, "ss").replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "");
        if (!slug) slug = "law-" + id;
        const exists = await env.DB.prepare(`SELECT 1 FROM laws WHERE slug = ?`).bind(slug).first();
        if (exists) slug = `${slug}-${id}`;
        const tmp = (prop.proposed_html || "").replace(/<[^>]+>/g, " ");
        const wc = (tmp.match(/\S+/g) || []).length;
        const max = await env.DB.prepare(`SELECT COALESCE(MAX(position), 0) AS m FROM laws`).first();
        await env.DB.prepare(
          `INSERT INTO laws (slug, title, category, html, text, word_count, ref_count, position, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)`
        ).bind(slug, prop.proposed_title || "Neu", prop.proposed_category || "Verwaltung", prop.proposed_html || "", tmp.trim(), wc, (max?.m || 0) + 1, now, `proposal#${id}`).run();
      } else if (prop.kind === "delete" && prop.slug) {
        await env.DB.prepare(`DELETE FROM laws WHERE slug = ?`).bind(prop.slug).run();
      }
    }

    await env.DB.prepare(
      `UPDATE law_proposals SET status = ?, reviewed_at = ?, reviewed_by = ?, review_note = ? WHERE id = ?`
    ).bind(action === "approve" ? "approved" : "rejected", Date.now(), user.username, note || null, id).run();
    await audit(env, user.username, `proposal.${action}`, String(id), null);
    return json({ ok: true });
  }

  return err(404, "Unknown endpoint");
}

// ---------- Static routing per host ----------

async function serveAsset(req, env, pathname) {
  // Rewrite URL so ASSETS sees the right path
  const u = new URL(req.url);
  u.pathname = pathname;
  return env.ASSETS.fetch(new Request(u.toString(), req));
}

export default {
  async fetch(req, env, ctx) {
    const url = new URL(req.url);
    const host = url.hostname;
    const isAdmin = host === env.ADMIN_HOST;
    const isPublic = host === env.PUBLIC_HOST;

    // Auto-bootstrap initial admin (idempotent)
    ctx.waitUntil(ensureBootstrapAdmin(env).catch(() => {}));

    // API
    if (url.pathname.startsWith("/api/")) {
      const user = await getUser(env, req);
      // Block login/admin endpoints from public host
      const publicAllowed = new Set([
        "/api/laws", "/api/me", "/api/changelog", "/api/proposals", "/api/pending-proposals",
      ]);
      if (!isAdmin && !publicAllowed.has(url.pathname)) {
        return err(403, "Admin host required");
      }
      try {
        return await handleApi(req, env, url, user);
      } catch (e) {
        return err(500, e.message || "Server error");
      }
    }

    // ---- Discord OAuth (Button-Login) ----
    if (url.pathname === "/auth/login") {
      if (!env.DISCORD_CLIENT_ID || !env.DISCORD_CLIENT_SECRET) {
        return Response.redirect(`https://${host}/?err=oauth_unconfigured`, 302);
      }
      const state = "rw_" + crypto.randomUUID();
      const redirect = env.DISCORD_REDIRECT_URI;
      const authUrl = `https://discord.com/oauth2/authorize?client_id=${encodeURIComponent(env.DISCORD_CLIENT_ID)}`
        + `&response_type=code&redirect_uri=${encodeURIComponent(redirect)}`
        + `&scope=${encodeURIComponent("identify guilds.members.read")}&state=${state}`;
      return new Response(null, {
        status: 302,
        headers: {
          location: authUrl,
          "set-cookie": `rw_state=${state}; Path=/; Max-Age=600; HttpOnly; Secure; SameSite=Lax`,
        },
      });
    }

    if (url.pathname === "/auth/callback") {
      try {
        const code = url.searchParams.get("code");
        const state = url.searchParams.get("state");
        const cookieState = (req.headers.get("cookie") || "").match(/(?:^|;\s*)rw_state=([^;]+)/);
        if (!code || !state || !cookieState || state !== cookieState[1]) {
          return Response.redirect(`https://${host}/?err=state`, 302);
        }
        const redirect = env.DISCORD_REDIRECT_URI;
        const tok = await fetch("https://discord.com/api/oauth2/token", {
          method: "POST",
          headers: { "content-type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            client_id: env.DISCORD_CLIENT_ID,
            client_secret: env.DISCORD_CLIENT_SECRET,
            grant_type: "authorization_code",
            code,
            redirect_uri: redirect,
          }),
        }).then((r) => r.json());
        if (!tok.access_token) return Response.redirect(`https://${host}/?err=token`, 302);
        const member = await fetch(
          `https://discord.com/api/users/@me/guilds/${env.DISCORD_GUILD_ID}/member`,
          { headers: { authorization: `Bearer ${tok.access_token}` } }
        ).then((r) => r.json());
        const roles = (member && member.roles) || [];
        if (!Array.isArray(roles) || !roles.includes(String(env.DISCORD_ROLE_ID))) {
          return Response.redirect(`https://${host}/?err=norole`, 302);
        }
        const du = member.user || {};
        const did = du.id;
        if (!did) return Response.redirect(`https://${host}/?err=nouser`, 302);
        const name = du.global_name || du.username || ("discord_" + did);
        const handle = "discord_" + did;
        let row = await env.DB.prepare(`SELECT id FROM users WHERE username = ?`).bind(handle).first();
        if (!row) {
          const { hash, salt } = await hashPassword(crypto.randomUUID());
          const r = await env.DB.prepare(
            `INSERT INTO users (username, password_hash, password_salt, role, display_name, created_at) VALUES (?, ?, ?, 'admin', ?, ?)`
          ).bind(handle, hash, salt, name, Date.now()).run();
          row = { id: r.meta.last_row_id };
        } else {
          await env.DB.prepare(`UPDATE users SET role = 'admin', display_name = ? WHERE id = ?`).bind(name, row.id).run();
        }
        await audit(env, handle, "oauth.login", name, null);
        const token = await createSession(env, row.id);
        return new Response(null, {
          status: 302,
          headers: { location: "/", "set-cookie": sessionCookie(token) },
        });
      } catch (e) {
        return Response.redirect(`https://${host}/?err=exception`, 302);
      }
    }

    // Bot-Magic-Login: setzt Session-Cookie aus gültigem Token und leitet zur Startseite
    if (url.pathname === "/auth") {
      const token = url.searchParams.get("token") || "";
      if (token) {
        const sess = await env.DB.prepare(
          `SELECT token FROM sessions WHERE token = ? AND expires_at > ?`
        ).bind(token, Date.now()).first();
        if (sess) {
          return new Response(null, {
            status: 302,
            headers: { location: "/", "set-cookie": sessionCookie(token) },
          });
        }
      }
      return new Response(null, { status: 302, headers: { location: "/" } });
    }

    // Static asset routing
    // Both hosts share /assets/* and /styles.css /shared.js
    // Per host, root → different HTML
    if (url.pathname === "/" || url.pathname === "") {
      return serveAsset(req, env, isAdmin ? "/admin.html" : "/index.html");
    }

    return serveAsset(req, env, url.pathname);
  },
};
