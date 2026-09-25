// Admin panel — doj.bloodline.cc — inline WYSIWYG editor

const state = {
  user: null,
  view: "login", // login | laws | proposals | requests | users | account
  authTab: "login",
  laws: [],
  requests: [],
  users: [],
  proposals: [],
  proposalDetail: null,
  current: null,         // currently opened law slug
  editing: false,        // true = body is contentEditable
  dirty: false,
  filter: "",
  notice: null,
  catalog: [],           // Bußgeldkatalog (Sektionen + Einträge)
  catalogEdit: null,     // Eintrag-ID (Zahl) oder "new:<CODE>" für Inline-Formular
};

const CATEGORIES = [
  "Verfassung", "Strafrecht", "Verfahren", "Verkehr", "Verwaltung", "Katalog",
];

const $ = (sel, root = document) => root.querySelector(sel);
const root = () => document.getElementById("app");

function escapeHtml(s) {
  return String(s ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  })[c]);
}

// Fremd-HTML (öffentliche Vorschläge) nur bereinigt einfügen.
// Ohne DOMPurify (CDN nicht geladen): als reiner Text anzeigen.
function safeHtml(html) {
  const s = String(html ?? "");
  if (window.DOMPurify && typeof window.DOMPurify.sanitize === "function") {
    return window.DOMPurify.sanitize(s);
  }
  return escapeHtml(s);
}

function notice(kind, msg) {
  state.notice = { kind, msg };
  render();
  setTimeout(() => {
    if (state.notice && state.notice.msg === msg) {
      state.notice = null;
      render();
    }
  }, 4000);
}

async function api(path, opts = {}) {
  const res = await fetch(path, {
    ...opts,
    headers: { "content-type": "application/json", ...(opts.headers || {}) },
  });
  let data = null;
  try { data = await res.json(); } catch {}
  if (!res.ok) {
    const err = new Error((data && data.error) || `HTTP ${res.status}`);
    err.status = res.status;
    throw err;
  }
  return data;
}

async function loadMe() {
  try {
    const { user } = await api("/api/me");
    state.user = user;
    state.view = user ? "laws" : "login";
  } catch {
    state.user = null;
    state.view = "login";
  }
}

async function loadLaws() {
  const { laws } = await api("/api/admin/laws");
  state.laws = laws;
}

async function loadRequests() {
  const { requests } = await api("/api/admin/requests");
  state.requests = requests;
}

async function loadUsers() {
  const { users } = await api("/api/admin/users");
  state.users = users;
}

async function loadProposals() {
  const { proposals } = await api("/api/admin/proposals");
  state.proposals = proposals;
}

async function loadCatalog() {
  const { catalog } = await api("/api/catalog");
  state.catalog = catalog || [];
}

async function loadProposalDetail(id) {
  const { proposal } = await api(`/api/admin/proposals/${id}`);
  state.proposalDetail = proposal;
}

// ---------- Topbar ----------

function pendingProposalCount() {
  return (state.proposals || []).filter((p) => p.status === "pending").length;
}

function renderTopbar() {
  if (!state.user) return "";
  const isAdmin = state.user.role === "admin";
  return `
    <header class="topbar">
      <div class="topbar-inner">
        <a class="brand" href="#">
          <img src="/assets/doj.png" alt="DOJ" />
          <span class="brand-copy">
            <span class="brand-eyebrow">Verwaltung</span>
            <span class="brand-title">Department of Justice</span>
          </span>
        </a>
        <nav>
          <button data-nav="laws" class="${state.view === "laws" ? "active" : ""}">Gesetze</button>
          <button data-nav="katalog" class="${state.view === "katalog" ? "active" : ""}">Bußgeldkatalog</button>
          <button data-nav="proposals" class="${state.view === "proposals" ? "active" : ""}">Vorschläge${pendingProposalCount() > 0 ? ` <span class="dot-badge">${pendingProposalCount()}</span>` : ""}</button>
          ${isAdmin ? `<button data-nav="requests" class="${state.view === "requests" ? "active" : ""}">Anträge</button>` : ""}
          ${isAdmin ? `<button data-nav="users" class="${state.view === "users" ? "active" : ""}">Benutzer</button>` : ""}
          <button data-nav="account" class="${state.view === "account" ? "active" : ""}">${escapeHtml(state.user.username)}</button>
          <button id="logout">Abmelden</button>
        </nav>
      </div>
    </header>
  `;
}

function renderNotice() {
  if (!state.notice) return "";
  return `<div class="shell"><div class="notice notice-${state.notice.kind}">${escapeHtml(state.notice.msg)}</div></div>`;
}

// ---------- Login ----------

function renderLogin() {
  return `
    <div class="auth-wrap">
      <div class="auth-card">
        <div class="auth-head">
          <img src="/assets/doj.png" alt="DOJ" />
          <h1>Department of Justice</h1>
          <p>Verwaltungsportal · doj.bloodline.cc</p>
        </div>
        <div class="auth-body">
          ${(() => {
            const err = new URLSearchParams(location.search).get("err");
            if (!err) return "";
            const map = {
              norole: "Kein Zugang — dir fehlt eine berechtigte DOJ-Rolle.",
              oauth_unconfigured: "Discord-Login ist noch nicht eingerichtet.",
              state: "Sitzung abgelaufen — bitte erneut versuchen.",
              token: "Discord-Anmeldung fehlgeschlagen.",
              nouser: "Discord-Benutzer nicht gefunden.",
              exception: "Anmeldung fehlgeschlagen.",
            };
            return `<div class="notice notice-error" style="margin-bottom:16px">${map[err] || "Anmeldung fehlgeschlagen."}</div>`;
          })()}
          <div class="discord-login">
            <p class="discord-login-intro">Team-Mitglieder mit passender DOJ-Rolle melden sich direkt über Discord an.</p>
            <a href="/auth/login" class="discord-login-btn">
              <svg viewBox="0 0 127.14 96.36" width="22" height="22" aria-hidden="true"><path fill="#fff" d="M107.7 8.07A105.15 105.15 0 0 0 81.47 0a72.06 72.06 0 0 0-3.36 6.83 97.68 97.68 0 0 0-29.11 0A72.37 72.37 0 0 0 45.64 0a105.89 105.89 0 0 0-26.25 8.09C2.79 32.65-1.71 56.6.54 80.21a105.73 105.73 0 0 0 32.17 16.15 77.7 77.7 0 0 0 6.89-11.11 68.42 68.42 0 0 1-10.85-5.18c.91-.66 1.8-1.34 2.66-2a75.57 75.57 0 0 0 64.32 0c.87.71 1.76 1.39 2.66 2a68.68 68.68 0 0 1-10.87 5.19 77 77 0 0 0 6.89 11.1 105.25 105.25 0 0 0 32.19-16.14c2.64-27.38-4.51-51.11-18.9-72.15ZM42.45 65.69C36.18 65.69 31 60 31 53s5-12.74 11.43-12.74S54 46 53.89 53s-5.05 12.69-11.44 12.69Zm42.24 0C78.41 65.69 73.25 60 73.25 53s5-12.74 11.44-12.74S96.23 46 96.12 53s-5.04 12.69-11.43 12.69Z"/></svg>
              Mit Discord anmelden
            </a>
          </div>
          <div class="auth-divider"><span>oder Benutzername</span></div>
          <div class="tabs">
            <button class="tab ${state.authTab === "login" ? "active" : ""}" data-tab="login">Anmelden</button>
            <button class="tab ${state.authTab === "request" ? "active" : ""}" data-tab="request">Zugang beantragen</button>
          </div>
          ${state.authTab === "login" ? renderLoginForm() : renderRequestForm()}
        </div>
      </div>
    </div>
  `;
}

function renderLoginForm() {
  return `
    <form id="login-form">
      <div class="field"><label>Benutzername</label><input type="text" name="username" autocomplete="username" required /></div>
      <div class="field"><label>Passwort</label><input type="password" name="password" autocomplete="current-password" required /></div>
      <button class="btn btn-gold" style="width:100%" type="submit">Anmelden</button>
    </form>
  `;
}

function renderRequestForm() {
  return `
    <p class="notice notice-info">Beantrage Zugang. Ein Administrator prüft deinen Antrag und schaltet ihn frei.</p>
    <form id="request-form">
      <div class="field"><label>Gewünschter Benutzername</label><input type="text" name="username" required pattern="[a-z0-9._-]{3,32}" title="3-32 Zeichen: a-z 0-9 . _ -" /></div>
      <div class="field"><label>Passwort (mind. 8 Zeichen)</label><input type="password" name="password" required minlength="8" /></div>
      <div class="field"><label>Begründung / Rolle im DOJ</label><textarea name="reason" placeholder="z.B. Staatsanwalt, Richter, Sachbearbeiter…" style="min-height:90px"></textarea></div>
      <button class="btn btn-gold" style="width:100%" type="submit">Antrag senden</button>
    </form>
  `;
}

// ---------- Laws (reader-like with inline editor) ----------

function renderLaws() {
  const q = state.filter.toLowerCase().trim();
  const filtered = q
    ? state.laws.filter(
        (l) => l.title.toLowerCase().includes(q) || (l.text || "").toLowerCase().includes(q)
      )
    : state.laws;
  const groups = new Map();
  for (const l of filtered) {
    const cat = l.category || "Verwaltung";
    if (!groups.has(cat)) groups.set(cat, []);
    groups.get(cat).push(l);
  }
  const orderedCats = [
    ...CATEGORIES.filter((c) => groups.has(c)),
    ...[...groups.keys()].filter((c) => !CATEGORIES.includes(c)),
  ];

  const sidebar = `
    <aside class="sidebar">
      <div style="display:flex;gap:6px;margin-bottom:10px">
        <input class="search" id="search" type="text" placeholder="Suchen…" value="${escapeHtml(state.filter)}" autocomplete="off" style="margin-bottom:0;flex:1" />
        <button class="btn btn-gold" id="new-law" title="Neues Gesetz">+</button>
      </div>
      ${orderedCats
        .map(
          (cat) => `
        <div class="cat">${escapeHtml(cat)}</div>
        ${groups
          .get(cat)
          .map(
            (l) =>
              `<a class="law-link${state.current === l.slug ? " active" : ""}" href="#${encodeURIComponent(l.slug)}" data-slug="${escapeHtml(l.slug)}">${escapeHtml(l.title)}</a>`
          )
          .join("")}
      `
        )
        .join("")}
    </aside>
  `;

  const law = state.current ? state.laws.find((l) => l.slug === state.current) : null;

  const article = law
    ? renderArticle(law)
    : `<article class="article"><div class="placeholder">Wähle ein Gesetz aus dem Verzeichnis links — oder lege ein neues an (+).</div></article>`;

  return `
    ${renderTopbar()}
    ${renderNotice()}
    <main class="shell">
      <div class="layout">${sidebar}${article}</div>
    </main>
  `;
}

function renderArticle(law) {
  const isAdmin = state.user.role === "admin";
  const editing = state.editing;
  const updated = law.updatedAt
    ? new Date(law.updatedAt).toLocaleDateString("de-DE", { day: "2-digit", month: "long", year: "numeric" })
    : "—";

  const toolbar = editing
    ? `
      <div class="edit-toolbar">
        <select id="cat-select">
          ${CATEGORIES.map((c) => `<option ${c === (law.category || "Verwaltung") ? "selected" : ""}>${c}</option>`).join("")}
        </select>
        <span class="toolbar-divider"></span>
        <button class="fmt" data-cmd="bold" title="Fett (Strg+B)"><b>B</b></button>
        <button class="fmt" data-cmd="italic" title="Kursiv (Strg+I)"><i>I</i></button>
        <button class="fmt" data-cmd="underline" title="Unterstrichen"><u>U</u></button>
        <span class="toolbar-divider"></span>
        <button class="fmt" data-block="h2" title="Überschrift">H2</button>
        <button class="fmt" data-block="h3" title="Unter-Überschrift">H3</button>
        <button class="fmt" data-block="p" title="Absatz">¶</button>
        <span class="toolbar-divider"></span>
        <button class="fmt" data-cmd="insertUnorderedList" title="Liste">• Liste</button>
        <button class="fmt" data-cmd="insertOrderedList" title="Nummeriert">1. Liste</button>
        <span class="toolbar-divider"></span>
        <button class="fmt" data-table="insert" title="Tabelle einfügen">⊞ Tabelle</button>
        <button class="fmt" data-table="row-after" title="Zeile darunter">+ Zeile</button>
        <button class="fmt" data-table="col-after" title="Spalte rechts">+ Spalte</button>
        <button class="fmt" data-table="row-del" title="Zeile löschen">− Zeile</button>
        <button class="fmt" data-table="col-del" title="Spalte löschen">− Spalte</button>
        <span class="toolbar-divider"></span>
        <button class="fmt" data-cmd="removeFormat" title="Formatierung entfernen">×</button>
        <span style="flex:1"></span>
        <button class="btn btn-ghost" id="cancel-edit">Verwerfen</button>
        <button class="btn btn-gold" id="save-law">Speichern</button>
      </div>
    `
    : `
      <div class="edit-toolbar">
        <span class="tag">${escapeHtml(law.category || "Verwaltung")}</span>
        <small style="color:var(--muted)">${law.wordCount || 0} Wörter · zuletzt ${updated}${law.updatedBy ? ` von ${escapeHtml(law.updatedBy)}` : ""}</small>
        <span style="flex:1"></span>
        <button class="btn btn-gold" id="edit-law">✎ Bearbeiten</button>
        <button class="btn btn-ghost" id="dup-law" title="Als Vorlage duplizieren">⧉ Duplizieren</button>
        ${isAdmin ? `<button class="btn btn-danger" id="del-law">Löschen</button>` : ""}
      </div>
    `;

  const titleEl = editing
    ? `<h2 contenteditable="true" id="edit-title" spellcheck="false">${escapeHtml(law.title)}</h2>`
    : `<h2>${escapeHtml(law.title)}</h2>`;

  const bodyEl = editing
    ? `<div class="article-body editable" id="edit-body" contenteditable="true" spellcheck="true">${law.html || "<p>Inhalt eingeben…</p>"}</div>`
    : `<div class="article-body">${law.html || "<p><em>Kein Inhalt hinterlegt.</em></p>"}</div>`;

  return `
    <article class="article">
      ${toolbar}
      <div class="article-head">
        <div class="cat-tag">${escapeHtml(law.category || "Verwaltung")}</div>
        ${titleEl}
        <div class="article-meta">Slug: ${escapeHtml(law.slug)}</div>
      </div>
      ${bodyEl}
    </article>
  `;
}

// ---------- Requests / Users / Account (unchanged structure) ----------

function renderProposals() {
  return `
    ${renderTopbar()}
    ${renderNotice()}
    <main class="shell">
      <div class="toolbar">
        <h2 style="margin:0;font-family:var(--serif)">Änderungs­vorschläge</h2>
        <small style="color:var(--muted)">${pendingProposalCount()} ausstehend · ${state.proposals.length} gesamt</small>
      </div>
      <div class="card">
        <table class="table">
          <thead><tr><th>Art</th><th>Gesetz / Titel</th><th>Eingereicht von</th><th>Unternehmen/Job</th><th>Begründung</th><th>Status</th><th>Datum</th><th></th></tr></thead>
          <tbody>
            ${state.proposals.map((p) => `
              <tr>
                <td><span class="tag ${p.kind}">${escapeHtml({edit:"Änderung",new:"Neu",delete:"Löschen"}[p.kind] || p.kind)}</span></td>
                <td><strong>${escapeHtml(p.proposedTitle || p.slug || "—")}</strong>${p.slug ? `<br><small style="color:var(--muted)">${escapeHtml(p.slug)}</small>` : ""}</td>
                <td>${escapeHtml(p.proposerName)}</td>
                <td>${p.proposerContact ? escapeHtml(p.proposerContact) : `<small style="color:var(--muted)">—</small>`}</td>
                <td><small>${escapeHtml(p.summary || "—").slice(0, 140)}</small></td>
                <td><span class="tag ${p.status}">${escapeHtml(p.status)}</span>${p.reviewedBy ? `<br><small>von ${escapeHtml(p.reviewedBy)}</small>` : ""}</td>
                <td><small>${new Date(p.createdAt).toLocaleString("de-DE")}</small></td>
                <td class="row-actions">
                  <button class="btn btn-ghost view-prop" data-id="${p.id}">Ansehen</button>
                </td>
              </tr>
            `).join("") || `<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">Keine Vorschläge.</td></tr>`}
          </tbody>
        </table>
      </div>
    </main>
    ${state.proposalDetail ? renderProposalDetail() : ""}
  `;
}

// Block-level diff highlighting: mark new/changed blocks vs reference HTML
function diffHighlight(currentHtml, referenceHtml, markClass) {
  if (!currentHtml) return "";
  if (!referenceHtml) return wrapInnerWithMark(currentHtml, markClass);
  const blockRe = /(<(?:h[1-6]|p|li|tr|td)[^>]*>[\s\S]*?<\/(?:h[1-6]|p|li|tr|td)>)/gi;
  const refSet = new Set();
  let m;
  while ((m = blockRe.exec(referenceHtml))) refSet.add(normalizeBlock(m[1]));
  blockRe.lastIndex = 0;
  return currentHtml.replace(blockRe, (block) =>
    refSet.has(normalizeBlock(block)) ? block : wrapBlockInner(block, markClass)
  );
}
function normalizeBlock(html) {
  return html.replace(/<[^>]+>/g, "").replace(/\s+/g, " ").trim().toLowerCase();
}
function wrapBlockInner(block, cls) {
  return block.replace(
    /^(<(\w+)[^>]*>)([\s\S]*?)(<\/\2>)$/i,
    (_, open, _t, inner, close) => `${open}<mark class="${cls}">${inner}</mark>${close}`
  );
}
function wrapInnerWithMark(html, cls) {
  return `<mark class="${cls}">${html}</mark>`;
}

function renderProposalDetail() {
  const p = state.proposalDetail;
  const isAdmin = state.user.role === "admin";
  const canDecide = isAdmin && p.status === "pending";
  return `
    <div class="modal-bg" id="prop-bg">
      <div class="modal" style="max-width:1200px">
        <div class="modal-head">
          <h3>${escapeHtml({edit:"Änderungs­vorschlag",new:"Neues Gesetz",delete:"Löschungs­vorschlag"}[p.kind])}: ${escapeHtml(p.proposedTitle || p.slug || "—")}</h3>
          <button class="btn btn-ghost" id="close-prop-detail">✕</button>
        </div>
        <div class="modal-body">
          <p><strong>Eingereicht von:</strong> ${escapeHtml(p.proposerName)}${p.proposerContact ? ` <span class="tag">${escapeHtml(p.proposerContact)}</span>` : ""}</p>
          <p><strong>Begründung:</strong> ${escapeHtml(p.summary)}</p>
          <hr style="border:none;border-top:1px solid var(--border);margin:14px 0" />
          ${p.kind === "delete" ? `
            <p class="notice notice-error">⚠ Vorschlag: Gesetz <strong>${escapeHtml(p.slug)}</strong> komplett löschen.</p>
          ` : `
            <h4 style="font-family:var(--serif);margin:8px 0">Diff-Vergleich <small style="font-weight:normal;color:var(--muted)">— neue/geänderte Absätze sind <mark class="diff-add">grün</mark>, entfernte <mark class="diff-del">rot</mark> markiert</small></h4>
            <div class="diff-split">
              <div class="diff-pane">
                <div class="diff-pane-head">Aktuell</div>
                <div class="diff-pane-body">${safeHtml(diffHighlight(p.currentHtml || "", p.proposedHtml || "", "diff-del")) || "<em>(neu)</em>"}</div>
              </div>
              <div class="diff-pane">
                <div class="diff-pane-head" style="background:rgba(28,124,58,0.1);color:var(--success)">Vorgeschlagen</div>
                <div class="diff-pane-body">${safeHtml(diffHighlight(p.proposedHtml || "", p.currentHtml || "", "diff-add"))}</div>
              </div>
            </div>
          `}
          ${p.reviewedBy ? `<p style="margin-top:14px"><small style="color:var(--muted)">Geprüft am ${new Date(p.reviewedAt).toLocaleString("de-DE")} von ${escapeHtml(p.reviewedBy)}${p.reviewNote ? ` — Notiz: ${escapeHtml(p.reviewNote)}` : ""}</small></p>` : ""}
        </div>
        <div class="modal-foot">
          <button class="btn btn-ghost" id="close-prop-detail2">Schließen</button>
          ${canDecide ? `
            <button class="btn btn-danger" id="reject-prop">Ablehnen</button>
            <button class="btn btn-success" id="approve-prop">Annehmen &amp; übernehmen</button>
          ` : ""}
        </div>
      </div>
    </div>
  `;
}

function renderRequests() {
  return `
    ${renderTopbar()}
    ${renderNotice()}
    <main class="shell">
      <div class="toolbar">
        <h2 style="margin:0;font-family:var(--serif)">Zugangs-Anträge</h2>
        <small style="color:var(--muted)">${state.requests.filter(r=>r.status==='pending').length} ausstehend</small>
      </div>
      <div class="card">
        <table class="table">
          <thead><tr><th>Benutzer</th><th>Begründung</th><th>Status</th><th>Eingegangen</th><th></th></tr></thead>
          <tbody>
            ${state.requests
              .map(
                (r) => `
              <tr>
                <td><strong>${escapeHtml(r.username)}</strong></td>
                <td><small>${escapeHtml(r.reason || "—")}</small></td>
                <td><span class="tag ${r.status}">${escapeHtml(r.status)}</span>${r.reviewedBy ? `<br><small>von ${escapeHtml(r.reviewedBy)}</small>` : ""}</td>
                <td><small>${new Date(r.createdAt).toLocaleString("de-DE")}</small></td>
                <td class="row-actions">
                  ${r.status === "pending" ? `
                    <button class="btn btn-success approve-req" data-id="${r.id}">Annehmen</button>
                    <button class="btn btn-danger reject-req" data-id="${r.id}">Ablehnen</button>
                  ` : ""}
                </td>
              </tr>`
              )
              .join("") || `<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:24px">Keine Anträge.</td></tr>`}
          </tbody>
        </table>
      </div>
    </main>
  `;
}

function renderUsers() {
  return `
    ${renderTopbar()}
    ${renderNotice()}
    <main class="shell">
      <div class="toolbar"><h2 style="margin:0;font-family:var(--serif)">Benutzer</h2></div>
      <div class="card">
        <table class="table">
          <thead><tr><th>Benutzername</th><th>Rolle</th><th>Erstellt</th><th>Letzte Anmeldung</th><th></th></tr></thead>
          <tbody>
            ${state.users
              .map(
                (u) => `
              <tr>
                <td><strong>${escapeHtml(u.username)}</strong></td>
                <td><span class="tag ${u.role === 'admin' ? 'admin' : ''}">${escapeHtml(u.role)}</span></td>
                <td><small>${new Date(u.createdAt).toLocaleDateString("de-DE")}</small></td>
                <td><small>${u.lastLogin ? new Date(u.lastLogin).toLocaleString("de-DE") : "nie"}</small></td>
                <td class="row-actions">
                  ${u.id !== state.user.id ? `<button class="btn btn-danger del-user" data-id="${u.id}">Entfernen</button>` : `<span class="tag">du</span>`}
                </td>
              </tr>`
              )
              .join("")}
          </tbody>
        </table>
      </div>
    </main>
  `;
}

function renderAccount() {
  return `
    ${renderTopbar()}
    ${renderNotice()}
    <main class="shell" style="max-width:600px">
      <div class="toolbar"><h2 style="margin:0;font-family:var(--serif)">Konto</h2></div>
      <div class="card">
        <p><strong>Benutzer:</strong> ${escapeHtml(state.user.username)} <span class="tag ${state.user.role === 'admin' ? 'admin' : ''}">${escapeHtml(state.user.role)}</span></p>
        <hr style="border:none;border-top:1px solid var(--border);margin:18px 0" />
        <h3 style="font-family:var(--serif);margin-top:0">Passwort ändern</h3>
        <form id="pw-form">
          <div class="field"><label>Aktuelles Passwort</label><input type="password" name="current" required /></div>
          <div class="field"><label>Neues Passwort (mind. 8)</label><input type="password" name="next" required minlength="8" /></div>
          <button class="btn btn-gold" type="submit">Aktualisieren</button>
        </form>
      </div>
    </main>
  `;
}

// ---------- Bußgeldkatalog-Editor ----------

function catFieldCells(e) {
  const v = (k) => escapeHtml(e && e[k] != null ? e[k] : "");
  return `
    <td><input class="cat-f" data-f="paragraph" value="${v("paragraph")}" placeholder="§.." /></td>
    <td><input class="cat-f" data-f="bedeutung" value="${v("bedeutung")}" placeholder="Bedeutung" /></td>
    <td><input class="cat-f" data-f="strafe" value="${v("strafe")}" placeholder="$.." /></td>
    <td><input class="cat-f" data-f="he" value="${v("he")}" placeholder="HE" /></td>
    <td><input class="cat-f" data-f="sonstiges" value="${v("sonstiges")}" placeholder="—" /></td>`;
}

function catEditRow(code, e) {
  return `
    <tr class="cat-edit-row">
      ${catFieldCells(e)}
      <td class="cat-row-actions">
        <button class="btn btn-sm btn-gold cat-save" data-code="${escapeHtml(code)}"${e ? ` data-id="${e.id}"` : ""} title="Speichern">✓</button>
        <button class="btn btn-sm cat-cancel" title="Abbrechen">✕</button>
      </td>
    </tr>`;
}

function catViewRow(e) {
  const c = (k) => escapeHtml(e[k] || "");
  return `
    <tr>
      <td>${c("paragraph")}</td>
      <td>${c("bedeutung")}</td>
      <td>${c("strafe")}</td>
      <td>${c("he")}</td>
      <td>${c("sonstiges")}</td>
      <td class="cat-row-actions">
        <button class="btn btn-sm cat-edit" data-id="${e.id}" title="Bearbeiten">✎</button>
        <button class="btn btn-sm btn-danger cat-del" data-id="${e.id}" title="Löschen">🗑</button>
      </td>
    </tr>`;
}

function renderCatalogSection(sec, isAdmin) {
  const newKey = "new:" + sec.code;
  const rows = (sec.entries || [])
    .map((e) => (state.catalogEdit === e.id ? catEditRow(sec.code, e) : catViewRow(e)))
    .join("");
  const addingHere = state.catalogEdit === newKey;
  return `
    <div class="card cat-section">
      <div class="cat-sec-head">
        <h3>[${escapeHtml(sec.code)}] ${escapeHtml(sec.title)}</h3>
        <div class="cat-sec-actions">
          <button class="btn btn-sm cat-sec-rename" data-code="${escapeHtml(sec.code)}" data-title="${escapeHtml(sec.title)}">Umbenennen</button>
          ${isAdmin ? `<button class="btn btn-sm btn-danger cat-sec-del" data-code="${escapeHtml(sec.code)}">Sektion löschen</button>` : ""}
        </div>
      </div>
      <div class="table-wrap">
        <table class="cat-table">
          <thead><tr><th>§</th><th>Bedeutung</th><th>Strafe</th><th>HE</th><th>Sonstiges</th><th></th></tr></thead>
          <tbody>
            ${rows || (addingHere ? "" : `<tr><td colspan="6" class="cat-empty">Noch keine Einträge.</td></tr>`)}
            ${addingHere ? catEditRow(sec.code, null) : ""}
          </tbody>
        </table>
      </div>
      ${addingHere ? "" : `<button class="btn btn-sm cat-add" data-code="${escapeHtml(sec.code)}">+ Eintrag</button>`}
    </div>`;
}

function renderCatalog() {
  const isAdmin = state.user.role === "admin";
  const sections = state.catalog || [];
  return `
    ${renderTopbar()}
    ${renderNotice()}
    <main class="shell">
      <div class="card">
        <h2 style="font-family:var(--serif);margin:0 0 6px">Bußgeldkatalog bearbeiten</h2>
        <p class="cat-hint">Änderungen wirken sofort auf die öffentliche Katalog-Ansicht und den Bußgeldrechner.</p>
        <form id="cat-new-section" class="cat-newsec">
          <input name="code" placeholder="Kürzel (z.B. StGb)" maxlength="20" required />
          <input name="title" placeholder="Titel (z.B. Strafgesetzbuch)" maxlength="120" required />
          <button class="btn btn-gold" type="submit">Sektion anlegen</button>
        </form>
      </div>
      ${sections.map((sec) => renderCatalogSection(sec, isAdmin)).join("")}
    </main>`;
}

// ---------- Render dispatch ----------

function render() {
  let html = "";
  if (!state.user) html = renderLogin();
  else if (state.view === "laws") html = renderLaws();
  else if (state.view === "katalog") html = renderCatalog();
  else if (state.view === "proposals") html = renderProposals();
  else if (state.view === "requests") html = renderRequests();
  else if (state.view === "users") html = renderUsers();
  else if (state.view === "account") html = renderAccount();
  root().innerHTML = html;
  bind();
}

// ---------- Bindings ----------

function bind() {
  // Auth
  document.querySelectorAll(".tab").forEach((t) =>
    t.addEventListener("click", () => { state.authTab = t.dataset.tab; render(); })
  );
  const lf = $("#login-form"); if (lf) lf.addEventListener("submit", onLogin);
  const rf = $("#request-form"); if (rf) rf.addEventListener("submit", onRequest);

  // Top nav
  document.querySelectorAll("[data-nav]").forEach((b) =>
    b.addEventListener("click", async () => {
      if (state.editing && !confirm("Änderungen verwerfen?")) return;
      state.editing = false;
      state.dirty = false;
      const v = b.dataset.nav;
      state.view = v;
      try {
        if (v === "laws") await loadLaws();
        else if (v === "katalog") { state.catalogEdit = null; await loadCatalog(); }
        else if (v === "proposals") await loadProposals();
        else if (v === "requests") await loadRequests();
        else if (v === "users") await loadUsers();
      } catch (e) { notice("error", e.message); }
      render();
    })
  );
  const lo = $("#logout"); if (lo) lo.addEventListener("click", onLogout);

  // Laws view
  document.querySelectorAll(".law-link").forEach((a) =>
    a.addEventListener("click", (e) => {
      e.preventDefault();
      if (state.editing && state.dirty && !confirm("Ungespeicherte Änderungen verwerfen?")) return;
      state.current = a.dataset.slug;
      state.editing = false;
      state.dirty = false;
      render();
    })
  );
  const search = $("#search");
  if (search) search.addEventListener("input", (e) => {
    state.filter = e.target.value;
    // Re-render only sidebar to keep article state
    render();
    // restore focus
    const s = $("#search");
    if (s) { s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
  });

  const nl = $("#new-law");
  if (nl) nl.addEventListener("click", onNewLaw);

  const el = $("#edit-law");
  if (el) el.addEventListener("click", () => { state.editing = true; state.dirty = false; render(); });
  const ce = $("#cancel-edit");
  if (ce) ce.addEventListener("click", () => {
    if (state.dirty && !confirm("Änderungen verwerfen?")) return;
    state.editing = false;
    state.dirty = false;
    render();
  });
  const sl = $("#save-law");
  if (sl) sl.addEventListener("click", saveLaw);
  const dl = $("#del-law");
  if (dl) dl.addEventListener("click", deleteLaw);
  const du = $("#dup-law");
  if (du) du.addEventListener("click", duplicateLaw);

  // Edit toolbar formatting
  document.querySelectorAll(".fmt").forEach((b) =>
    b.addEventListener("mousedown", (e) => {
      e.preventDefault(); // keep selection
      const cmd = b.dataset.cmd;
      const block = b.dataset.block;
      const tbl = b.dataset.table;
      const body = $("#edit-body");
      if (body) body.focus();
      if (tbl) handleTable(tbl, body);
      else if (block) document.execCommand("formatBlock", false, block);
      else if (cmd) document.execCommand(cmd, false, null);
      state.dirty = true;
    })
  );

  // Mark dirty on any input in editable
  const body = $("#edit-body");
  const title = $("#edit-title");
  if (body) {
    body.addEventListener("input", () => { state.dirty = true; });
    body.addEventListener("keydown", onEditorKey);
  }
  if (title) {
    title.addEventListener("input", () => { state.dirty = true; });
    title.addEventListener("keydown", (e) => { if (e.key === "Enter") { e.preventDefault(); $("#edit-body")?.focus(); } });
  }

  // Proposals list
  document.querySelectorAll(".view-prop").forEach((b) =>
    b.addEventListener("click", async () => {
      try {
        await loadProposalDetail(Number(b.dataset.id));
        render();
      } catch (e) { notice("error", e.message); }
    })
  );
  // Proposal detail modal
  const closeProp = () => { state.proposalDetail = null; render(); };
  $("#close-prop-detail")?.addEventListener("click", closeProp);
  $("#close-prop-detail2")?.addEventListener("click", closeProp);
  $("#prop-bg")?.addEventListener("click", (e) => { if (e.target.id === "prop-bg") closeProp(); });
  $("#approve-prop")?.addEventListener("click", () => decideProp("approve"));
  $("#reject-prop")?.addEventListener("click", () => decideProp("reject"));

  // Requests
  document.querySelectorAll(".approve-req").forEach((b) =>
    b.addEventListener("click", () => reviewReq(b.dataset.id, "approve"))
  );
  document.querySelectorAll(".reject-req").forEach((b) =>
    b.addEventListener("click", () => reviewReq(b.dataset.id, "reject"))
  );

  // Users
  document.querySelectorAll(".del-user").forEach((b) =>
    b.addEventListener("click", async () => {
      if (!confirm("Benutzer wirklich entfernen?")) return;
      try {
        await api(`/api/admin/users/${b.dataset.id}`, { method: "DELETE" });
        await loadUsers();
        notice("success", "Entfernt.");
        render();
      } catch (e) { notice("error", e.message); }
    })
  );

  // Password change
  const pw = $("#pw-form");
  if (pw) pw.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(pw);
    try {
      await api("/api/admin/change-password", {
        method: "POST",
        body: JSON.stringify({ current: fd.get("current"), next: fd.get("next") }),
      });
      notice("success", "Passwort aktualisiert. Bitte neu anmelden.");
      setTimeout(() => { state.user = null; state.view = "login"; render(); }, 1200);
    } catch (e) { notice("error", e.message); }
  });

  // ----- Bußgeldkatalog-Editor -----
  const cns = $("#cat-new-section");
  if (cns) cns.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(cns);
    try {
      await api("/api/admin/catalog/sections", {
        method: "POST",
        body: JSON.stringify({ code: fd.get("code"), title: fd.get("title") }),
      });
      await loadCatalog();
      notice("success", "Sektion angelegt.");
      render();
    } catch (err) { notice("error", err.message); }
  });

  document.querySelectorAll(".cat-sec-rename").forEach((b) =>
    b.addEventListener("click", async () => {
      const title = prompt("Neuer Titel:", b.dataset.title);
      if (title == null || !title.trim()) return;
      try {
        await api(`/api/admin/catalog/sections/${encodeURIComponent(b.dataset.code)}`, {
          method: "PUT", body: JSON.stringify({ title: title.trim() }),
        });
        await loadCatalog();
        notice("success", "Umbenannt.");
        render();
      } catch (err) { notice("error", err.message); }
    })
  );

  document.querySelectorAll(".cat-sec-del").forEach((b) =>
    b.addEventListener("click", async () => {
      if (!confirm(`Sektion [${b.dataset.code}] samt allen Einträgen löschen?`)) return;
      try {
        await api(`/api/admin/catalog/sections/${encodeURIComponent(b.dataset.code)}`, { method: "DELETE" });
        await loadCatalog();
        notice("success", "Sektion gelöscht.");
        render();
      } catch (err) { notice("error", err.message); }
    })
  );

  document.querySelectorAll(".cat-add").forEach((b) =>
    b.addEventListener("click", () => { state.catalogEdit = "new:" + b.dataset.code; render(); })
  );
  document.querySelectorAll(".cat-edit").forEach((b) =>
    b.addEventListener("click", () => { state.catalogEdit = Number(b.dataset.id); render(); })
  );
  document.querySelectorAll(".cat-cancel").forEach((b) =>
    b.addEventListener("click", () => { state.catalogEdit = null; render(); })
  );
  document.querySelectorAll(".cat-del").forEach((b) =>
    b.addEventListener("click", async () => {
      if (!confirm("Eintrag löschen?")) return;
      try {
        await api(`/api/admin/catalog/entries/${b.dataset.id}`, { method: "DELETE" });
        await loadCatalog();
        notice("success", "Eintrag gelöscht.");
        render();
      } catch (err) { notice("error", err.message); }
    })
  );
  document.querySelectorAll(".cat-save").forEach((b) =>
    b.addEventListener("click", () => saveCatalogEntry(b))
  );
}

async function saveCatalogEntry(btn) {
  const tr = btn.closest("tr");
  const payload = {};
  tr.querySelectorAll(".cat-f").forEach((inp) => { payload[inp.dataset.f] = inp.value.trim(); });
  if (!payload.bedeutung) { notice("error", "Bedeutung erforderlich."); return; }
  const id = btn.dataset.id;
  try {
    if (id) {
      await api(`/api/admin/catalog/entries/${id}`, { method: "PUT", body: JSON.stringify(payload) });
    } else {
      payload.code = btn.dataset.code;
      await api("/api/admin/catalog/entries", { method: "POST", body: JSON.stringify(payload) });
    }
    state.catalogEdit = null;
    await loadCatalog();
    notice("success", "Gespeichert.");
    render();
  } catch (err) { notice("error", err.message); }
}

function onEditorKey(e) {
  // Ctrl/Cmd+S → save
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") {
    e.preventDefault();
    saveLaw();
  }
  // Plain Enter inside contentEditable already creates <div> — convert to <p> default
  // Browsers default formatBlock often is "div" — set to "p"
}

// ---------- Handlers ----------

async function onLogin(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  try {
    await api("/api/login", {
      method: "POST",
      body: JSON.stringify({ username: fd.get("username"), password: fd.get("password") }),
    });
    await loadMe();
    if (state.view === "laws") await loadLaws();
    render();
  } catch (err) { notice("error", err.message); }
}

async function onRequest(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  try {
    await api("/api/request-access", {
      method: "POST",
      body: JSON.stringify({
        username: fd.get("username"),
        password: fd.get("password"),
        reason: fd.get("reason"),
      }),
    });
    notice("success", "Antrag eingereicht. Du wirst freigeschaltet, sobald ein Admin geprüft hat.");
    state.authTab = "login";
    render();
  } catch (err) { notice("error", err.message); }
}

async function onLogout() {
  await api("/api/logout", { method: "POST" }).catch(() => {});
  state.user = null;
  state.view = "login";
  state.current = null;
  state.editing = false;
  render();
}

function onNewLaw() {
  const wrap = document.createElement("div");
  wrap.className = "modal-bg";
  wrap.innerHTML = `
    <div class="modal" style="max-width:520px">
      <div class="modal-head">
        <h3>Neues Gesetz anlegen</h3>
        <button class="btn btn-ghost" data-close>✕</button>
      </div>
      <div class="modal-body">
        <div class="field">
          <label>Titel *</label>
          <input type="text" id="nl-title" autofocus required placeholder="z.B. Glücksspielgesetz [GlüG]" />
        </div>
        <div class="field">
          <label>Kategorie</label>
          <select id="nl-cat">${CATEGORIES.map((c) => `<option ${c === "Verwaltung" ? "selected" : ""}>${c}</option>`).join("")}</select>
        </div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" data-close>Abbrechen</button>
        <button class="btn btn-gold" id="nl-create">Anlegen &amp; bearbeiten</button>
      </div>
    </div>
  `;
  document.body.appendChild(wrap);
  const close = () => wrap.remove();
  wrap.querySelectorAll("[data-close]").forEach((b) => b.addEventListener("click", close));
  wrap.addEventListener("click", (e) => { if (e.target === wrap) close(); });
  wrap.querySelector("#nl-title").focus();
  wrap.querySelector("#nl-title").addEventListener("keydown", (e) => {
    if (e.key === "Enter") { e.preventDefault(); wrap.querySelector("#nl-create").click(); }
  });
  wrap.querySelector("#nl-create").addEventListener("click", async () => {
    const title = wrap.querySelector("#nl-title").value.trim();
    const category = wrap.querySelector("#nl-cat").value;
    if (!title) { notice("error", "Titel fehlt"); return; }
    try {
      const { slug } = await api("/api/admin/laws", {
        method: "POST",
        body: JSON.stringify({
          title,
          category,
          html: `<h2>${escapeHtml(title)}</h2><p>Inhalt eingeben…</p>`,
          text: title,
        }),
      });
      close();
      await loadLaws();
      state.current = slug;
      state.editing = true;
      state.dirty = false;
      render();
    } catch (e) { notice("error", e.message); }
  });
}

async function saveLaw() {
  if (!state.current) return;
  const title = $("#edit-title")?.innerText.trim();
  const html = $("#edit-body")?.innerHTML || "";
  const category = $("#cat-select")?.value;
  if (!title) return notice("error", "Titel fehlt.");
  const tmp = document.createElement("div");
  tmp.innerHTML = html;
  const text = tmp.textContent || "";
  try {
    await api(`/api/admin/laws/${encodeURIComponent(state.current)}`, {
      method: "PUT",
      body: JSON.stringify({ title, category, html, text }),
    });
    state.editing = false;
    state.dirty = false;
    await loadLaws();
    notice("success", "Gespeichert.");
    render();
  } catch (e) { notice("error", e.message); }
}

async function duplicateLaw() {
  if (!state.current) return;
  try {
    const { slug } = await api(`/api/admin/laws/${encodeURIComponent(state.current)}/duplicate`, {
      method: "POST",
    });
    await loadLaws();
    state.current = slug;
    state.editing = true;
    state.dirty = false;
    notice("success", "Gesetz dupliziert. Du bearbeitest jetzt die Kopie.");
    render();
  } catch (e) { notice("error", e.message); }
}

// ---------- Table tools (contentEditable) ----------

function handleTable(action, body) {
  const sel = window.getSelection();
  if (action === "insert") {
    openTableInsertModal((rows, cols, headerRow) => insertTable(rows, cols, headerRow));
    return;
  }
  const cell = sel?.anchorNode && getAncestor(sel.anchorNode, ["TD", "TH"]);
  if (!cell) {
    notice("error", "Cursor in eine Tabellenzelle setzen.");
    return;
  }
  const row = cell.parentElement;
  const table = getAncestor(cell, ["TABLE"]);
  const cellIdx = Array.from(row.children).indexOf(cell);

  if (action === "row-after") {
    const newRow = document.createElement("tr");
    Array.from(row.children).forEach(() => {
      const td = document.createElement("td");
      td.innerHTML = "&nbsp;";
      newRow.appendChild(td);
    });
    row.after(newRow);
  } else if (action === "row-del") {
    if (table.rows.length <= 1) { notice("error", "Letzte Zeile kann nicht gelöscht werden."); return; }
    row.remove();
  } else if (action === "col-after") {
    Array.from(table.rows).forEach((r) => {
      const tag = r.children[cellIdx]?.tagName === "TH" ? "th" : "td";
      const c = document.createElement(tag);
      c.innerHTML = "&nbsp;";
      r.children[cellIdx]?.after(c);
    });
  } else if (action === "col-del") {
    if (table.rows[0].children.length <= 1) { notice("error", "Letzte Spalte kann nicht gelöscht werden."); return; }
    Array.from(table.rows).forEach((r) => r.children[cellIdx]?.remove());
  }
}

function getAncestor(node, tags) {
  let n = node;
  while (n && n !== document.body) {
    if (n.nodeType === 1 && tags.includes(n.tagName)) return n;
    n = n.parentNode;
  }
  return null;
}

function insertTable(rows, cols, headerRow) {
  let html = "<table><tbody>";
  for (let r = 0; r < rows; r++) {
    html += "<tr>";
    for (let c = 0; c < cols; c++) {
      const tag = headerRow && r === 0 ? "th" : "td";
      html += `<${tag}>${headerRow && r === 0 ? `Spalte ${c + 1}` : "&nbsp;"}</${tag}>`;
    }
    html += "</tr>";
  }
  html += "</tbody></table><p>&nbsp;</p>";
  document.execCommand("insertHTML", false, html);
  state.dirty = true;
}

function openTableInsertModal(onConfirm) {
  const wrap = document.createElement("div");
  wrap.className = "modal-bg";
  wrap.innerHTML = `
    <div class="modal" style="max-width:420px">
      <div class="modal-head"><h3>Tabelle einfügen</h3><button class="btn btn-ghost" data-close>✕</button></div>
      <div class="modal-body">
        <div class="field-row">
          <div class="field"><label>Zeilen</label><input type="number" id="t-rows" min="1" max="50" value="3" /></div>
          <div class="field"><label>Spalten</label><input type="number" id="t-cols" min="1" max="20" value="3" /></div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" id="t-header" checked style="width:auto" />
          Erste Zeile als Kopfzeile (TH)
        </label>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" data-close>Abbrechen</button>
        <button class="btn btn-gold" id="t-ok">Einfügen</button>
      </div>
    </div>
  `;
  document.body.appendChild(wrap);
  const close = () => wrap.remove();
  wrap.querySelectorAll("[data-close]").forEach((b) => b.addEventListener("click", close));
  wrap.addEventListener("click", (e) => { if (e.target === wrap) close(); });
  wrap.querySelector("#t-ok").addEventListener("click", () => {
    const rows = Math.max(1, Number(wrap.querySelector("#t-rows").value));
    const cols = Math.max(1, Number(wrap.querySelector("#t-cols").value));
    const header = wrap.querySelector("#t-header").checked;
    close();
    // re-focus body
    $("#edit-body")?.focus();
    onConfirm(rows, cols, header);
  });
}

async function deleteLaw() {
  if (!state.current) return;
  if (!confirm(`"${state.current}" wirklich löschen?`)) return;
  try {
    await api(`/api/admin/laws/${encodeURIComponent(state.current)}`, { method: "DELETE" });
    state.current = null;
    await loadLaws();
    notice("success", "Gelöscht.");
    render();
  } catch (e) { notice("error", e.message); }
}

async function decideProp(action) {
  if (!state.proposalDetail) return;
  if (action === "reject") {
    openNoteModal(
      "Vorschlag ablehnen",
      "Optionale Notiz zur Ablehnung (für die Akte):",
      async (note) => {
        await sendDecision(action, note);
      }
    );
    return;
  }
  // Approve confirmation
  openConfirmModal(
    "Vorschlag übernehmen",
    "Der Vorschlag wird sofort auf das Gesetz angewendet und in der Änderungs­historie veröffentlicht.",
    async () => {
      await sendDecision("approve", "");
    }
  );
}

async function sendDecision(action, note) {
  try {
    await api(`/api/admin/proposals/${state.proposalDetail.id}/${action}`, {
      method: "POST",
      body: JSON.stringify({ note: note || "" }),
    });
    state.proposalDetail = null;
    await loadProposals();
    if (action === "approve") await loadLaws().catch(() => {});
    notice("success", action === "approve" ? "Vorschlag übernommen." : "Vorschlag abgelehnt.");
    render();
  } catch (e) { notice("error", e.message); }
}

// ---------- Generic in-app modals (replace prompt/confirm) ----------

function openNoteModal(title, label, onConfirm) {
  const wrap = document.createElement("div");
  wrap.className = "modal-bg";
  wrap.innerHTML = `
    <div class="modal" style="max-width:520px">
      <div class="modal-head"><h3>${escapeHtml(title)}</h3><button class="btn btn-ghost" data-close>✕</button></div>
      <div class="modal-body">
        <div class="field">
          <label>${escapeHtml(label)}</label>
          <textarea id="note-input" autofocus style="min-height:90px"></textarea>
        </div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" data-close>Abbrechen</button>
        <button class="btn btn-danger" id="note-ok">Bestätigen</button>
      </div>
    </div>
  `;
  document.body.appendChild(wrap);
  const close = () => wrap.remove();
  wrap.querySelectorAll("[data-close]").forEach((b) => b.addEventListener("click", close));
  wrap.addEventListener("click", (e) => { if (e.target === wrap) close(); });
  wrap.querySelector("#note-input").focus();
  wrap.querySelector("#note-ok").addEventListener("click", async () => {
    const v = wrap.querySelector("#note-input").value;
    close();
    await onConfirm(v);
  });
}

function openConfirmModal(title, msg, onConfirm) {
  const wrap = document.createElement("div");
  wrap.className = "modal-bg";
  wrap.innerHTML = `
    <div class="modal" style="max-width:480px">
      <div class="modal-head"><h3>${escapeHtml(title)}</h3><button class="btn btn-ghost" data-close>✕</button></div>
      <div class="modal-body"><p>${escapeHtml(msg)}</p></div>
      <div class="modal-foot">
        <button class="btn btn-ghost" data-close>Abbrechen</button>
        <button class="btn btn-gold" id="confirm-ok">Übernehmen</button>
      </div>
    </div>
  `;
  document.body.appendChild(wrap);
  const close = () => wrap.remove();
  wrap.querySelectorAll("[data-close]").forEach((b) => b.addEventListener("click", close));
  wrap.addEventListener("click", (e) => { if (e.target === wrap) close(); });
  wrap.querySelector("#confirm-ok").addEventListener("click", async () => {
    close();
    await onConfirm();
  });
}

async function reviewReq(id, action) {
  try {
    await api(`/api/admin/requests/${id}/${action}`, { method: "POST" });
    await loadRequests();
    notice("success", action === "approve" ? "Freigeschaltet." : "Abgelehnt.");
    render();
  } catch (e) { notice("error", e.message); }
}

// ---------- Init ----------

(async function init() {
  await loadMe();
  if (state.user && state.view === "laws") {
    try { await Promise.all([loadLaws(), loadProposals()]); } catch {}
  }
  render();

  // Warn on tab close with unsaved changes
  window.addEventListener("beforeunload", (e) => {
    if (state.editing && state.dirty) {
      e.preventDefault();
      e.returnValue = "";
    }
  });
})();
