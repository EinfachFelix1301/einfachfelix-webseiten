// Public reader — regelwerk.bloodline.cc
// Tabs: Regelwerk (with diff highlights + propose) | Regelwerkänderungen (changelog)

const state = {
  tab: "laws",
  laws: [],
  changelog: null,
  pendingProposals: [],
  current: null,
  filter: "",
  proposing: false,
  proposeKind: "edit", // edit | new
};

const CATEGORY_ORDER = ["Allgemeine Regeln","Roleplay Regeln","Fraktionsregelwerk"];
const HIDDEN_CATEGORIES = new Set();
const HIDDEN_SLUGS = new Set();
const RECENT_DAYS = 30;

const $ = (sel, root = document) => root.querySelector(sel);

function escapeHtml(s) {
  return String(s ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;","<": "&lt;",">": "&gt;",'"': "&quot;","'": "&#39;",
  })[c]);
}

async function api(path, opts = {}) {
  const res = await fetch(path, {
    ...opts,
    headers: { "content-type": "application/json", ...(opts.headers || {}) },
  });
  let data = null;
  try { data = await res.json(); } catch {}
  if (!res.ok) throw new Error((data && data.error) || `HTTP ${res.status}`);
  return data;
}

async function loadLaws() {
  const { laws } = await api("/api/laws");
  state.laws = laws || [];
}

async function loadChangelog() {
  state.changelog = await api("/api/changelog");
}

async function loadPendingProposals() {
  try {
    const { proposals } = await api("/api/pending-proposals");
    state.pendingProposals = proposals || [];
  } catch { state.pendingProposals = []; }
}

function pendingForSlug(slug) {
  return state.pendingProposals.filter((p) => p.slug === slug || (!p.slug && p.kind === "new"));
}

// ---------- Diff highlight ----------

// Tokenize HTML by paragraph-level blocks, then mark new/changed paragraphs.
function highlightDiff(currentHtml, prevHtml, prevUpdatedAt) {
  if (!prevHtml || !currentHtml) return currentHtml;
  // Split by block-level tags. Compare block by block via normalized text content.
  const blockRe = /(<(?:h[1-6]|p|li|tr|td)[^>]*>[\s\S]*?<\/(?:h[1-6]|p|li|tr|td)>)/gi;
  const prevBlocks = new Set();
  let m;
  while ((m = blockRe.exec(prevHtml))) {
    prevBlocks.add(normalize(m[1]));
  }
  blockRe.lastIndex = 0;
  return currentHtml.replace(blockRe, (block) => {
    if (prevBlocks.has(normalize(block))) return block;
    // New / changed block — wrap inner with mark
    return wrapInnerWithMark(block);
  });
}
function normalize(html) {
  return html.replace(/<[^>]+>/g, "").replace(/\s+/g, " ").trim().toLowerCase();
}
function wrapInnerWithMark(block) {
  // <h2>Text</h2> → <h2><mark class="recent-change">Text</mark></h2>
  return block.replace(
    /^(<(\w+)[^>]*>)([\s\S]*?)(<\/\2>)$/i,
    (_, open, _tag, inner, close) =>
      `${open}<mark class="recent-change">${inner}</mark>${close}`
  );
}

// ---------- Render ----------

function renderTabs() {
  document.querySelectorAll("[data-tab]").forEach((b) =>
    b.classList.toggle("active", b.dataset.tab === state.tab)
  );
}

function render() {
  renderTabs();
  if (state.tab === "laws") renderLawsView();
  else if (state.tab === "changelog") renderChangelogView();
  if (state.proposing) renderProposeModal();
}

function renderLawsView() {
  const main = $("#main");
  const q = state.filter.toLowerCase().trim();
  const visibleLaws = state.laws.filter((l) =>
    !HIDDEN_CATEGORIES.has(l.category) && !HIDDEN_SLUGS.has(l.slug)
  );
  const filtered = q
    ? visibleLaws.filter((l) => l.title.toLowerCase().includes(q) || (l.text || "").toLowerCase().includes(q))
    : visibleLaws;
  const groups = new Map();
  for (const law of filtered) {
    const cat = law.category || "Verwaltung";
    if (HIDDEN_CATEGORIES.has(cat)) continue;
    if (!groups.has(cat)) groups.set(cat, []);
    groups.get(cat).push(law);
  }
  const orderedCats = [
    ...CATEGORY_ORDER.filter((c) => groups.has(c)),
    ...[...groups.keys()].filter((c) => !CATEGORY_ORDER.includes(c)),
  ];

  const recentCutoff = Date.now() - RECENT_DAYS * 86400_000;

  const sidebar = `
    <aside class="sidebar">
      <input class="search" id="search" type="text" placeholder="Regel suchen…" value="${escapeHtml(state.filter)}" autocomplete="off" />
      ${orderedCats
        .map(
          (cat) => `
        <div class="cat">${escapeHtml(cat)}</div>
        ${groups.get(cat)
          .map((l) => {
            const recent = l.updatedAt && l.updatedAt > recentCutoff;
            return `<a class="law-link${state.current === l.slug ? " active" : ""}" href="#${encodeURIComponent(l.slug)}" data-slug="${escapeHtml(l.slug)}">${escapeHtml(l.title)}${recent ? ` <span class="dot-recent" title="Kürzlich geändert"></span>` : ""}</a>`;
          })
          .join("")}
      `
        )
        .join("")}
      <button class="btn btn-gold" id="propose-new" style="width:100%;margin-top:14px">+ Neue Regel vorschlagen</button>
    </aside>
  `;

  const law = state.current ? state.laws.find((l) => l.slug === state.current) : null;
  let article;
  if (!law) {
    article = `<article class="article"><div class="placeholder">Wähle eine Regel aus dem Verzeichnis links.</div></article>`;
  } else {
    const updated = law.updatedAt
      ? new Date(law.updatedAt).toLocaleDateString("de-DE", { day: "2-digit", month: "long", year: "numeric" })
      : "—";
    const isRecent = law.updatedAt && law.updatedAt > recentCutoff;
    const html = isRecent && law.prevHtml ? highlightDiff(law.html, law.prevHtml, law.prevUpdatedAt) : law.html;
    const pending = pendingForSlug(law.slug).filter((p) => p.slug === law.slug);
    const pendingBanner = pending.length
      ? `<div class="proposal-banner">
           <div class="proposal-banner-icon">📬</div>
           <div class="proposal-banner-text">
             <strong>${pending.length} offene${pending.length === 1 ? "r" : ""} Änderungs­vorschlag${pending.length === 1 ? "" : "vorschläge"}</strong>
             <small>Eingereicht von ${pending.map((p) => escapeHtml(p.proposerName)).join(", ")} — wird vom Team geprüft.</small>
           </div>
         </div>`
      : "";
    article = `
      <article class="article">
        <div class="article-head">
          <div class="cat-tag">${escapeHtml(law.category || "Verwaltung")}</div>
          <h2>${escapeHtml(law.title)}</h2>
          <div class="article-meta">
            Zuletzt aktualisiert: ${escapeHtml(updated)} · ${law.wordCount || 0} Wörter
            ${isRecent ? ' · <span style="color:var(--gold-300)">⬤ kürzlich geändert</span>' : ""}
          </div>
        </div>
        ${pendingBanner}
        <div class="article-actions">
          <button class="btn btn-ghost" id="propose-edit">✎ Änderung vorschlagen</button>
        </div>
        <div class="article-body">${html || "<p><em>Kein Inhalt hinterlegt.</em></p>"}</div>
      </article>
    `;
  }

  main.innerHTML = `<div class="layout">${sidebar}${article}</div>`;
  enhanceLawTOCAnchors(main);
}

// Erkennt TOC-Strukturen (Tabellen + Listen) und wandelt sie in klickbare Karten/Buttons
// mit Smooth-Scroll auf Section-Header innerhalb desselben Artikels.
function enhanceLawTOCAnchors(root) {
  const body = root.querySelector(".article-body");
  if (!body) return;

  // 1. Heading-Map bauen: text-normalisiert + §CODE -> id
  const slug = (s) => (s || "").toLowerCase().replace(/[^a-z0-9äöüß]+/g, "-").replace(/^-|-$/g, "");
  const norm = (s) => (s || "").toLowerCase().replace(/\s+/g, " ").trim();
  const headingByText = new Map();
  const headingByCode = new Map();
  const stripLeading = (s) => (s || "")
    .replace(/^\s*§\s*\d+[a-z]?\s*(Abs\.?\s*\d+)?\s*[.\-:]?\s*/i, "")
    .replace(/^\s*\d+\s*[.\)]\s*/, "")
    .replace(/^\s*[IVXLCDM]+\s*[.\)]\s*/i, "")
    .trim();
  body.querySelectorAll("h1, h2, h3, h4, h5, h6").forEach((h) => {
    const text = h.textContent || "";
    if (!h.id) h.id = "sec-" + slug(text).slice(0, 60);
    headingByText.set(norm(text), h.id);
    // Stripped: ohne §-Prefix / Nummer
    const stripped = stripLeading(text);
    if (stripped && stripped !== text) headingByText.set(norm(stripped), h.id);
    const m = text.match(/§\s*([A-Za-zÄÖÜäöüß0-9]+)/);
    if (m) headingByCode.set(m[1].toUpperCase(), h.id);
    // numeric prefix (z.B. "1. Das Strafverfahren") - speichern OHNE prefix
    const np = text.match(/^\s*\d+\.\s*(.+)$/);
    if (np) headingByText.set(norm(np[1]), h.id);
  });

  // Helper: zu Zelltext passende Heading-Id finden
  const findId = (text) => {
    const t = norm(text);
    if (!t) return null;
    if (headingByText.has(t)) return headingByText.get(t);
    // (CODE) am Ende
    const m1 = text.match(/\(([A-Za-zÄÖÜäöüß]+)\)\s*$/);
    if (m1 && headingByCode.has(m1[1].toUpperCase())) return headingByCode.get(m1[1].toUpperCase());
    // Numerischer Prefix
    const m2 = text.match(/^\s*\d+\.\s*(.+)$/);
    if (m2 && headingByText.has(norm(m2[1]))) return headingByText.get(norm(m2[1]));
    // Strip §-Prefix
    const stripped = stripLeading(text);
    if (stripped && headingByText.has(norm(stripped))) return headingByText.get(norm(stripped));
    // Substring-Suche: list-Text in heading enthalten (z.B. "Grundregeln" in "§ 1 Grundregeln")
    const needle = norm(stripLeading(text));
    if (needle && needle.length >= 4) {
      for (const [hText, hId] of headingByText.entries()) {
        if (hText.includes(needle)) return hId;
      }
    }
    return null;
  };

  // 2. TOC-Tabellen erkennen und in Karten-Grid umwandeln
  body.querySelectorAll("table").forEach((tbl) => {
    const rows = [...tbl.querySelectorAll("tr")];
    if (rows.length < 2) return;
    // Heuristik: jede Zeile hat 1-2 Zellen mit kurzem Text und matched ein Heading
    const items = [];
    let totalCells = 0, tocCells = 0;
    rows.forEach((r) => {
      const cells = [...r.children];
      cells.forEach((c) => {
        totalCells++;
        const text = (c.textContent || "").trim();
        if (!text || text.length > 80) return;
        const id = findId(text);
        if (id) { tocCells++; items.push({ text, id }); }
      });
    });
    if (totalCells === 0 || tocCells / totalCells < 0.5 || items.length < 2) return;
    // Dedup + numerisch sortieren wenn Items mit "1. xx" Pattern
    const seen = new Set();
    const dedup = items.filter((i) => { if (seen.has(i.id)) return false; seen.add(i.id); return true; })
      .map((i) => {
        const np = i.text.match(/^\s*(\d+)\.\s*(.+)$/);
        return { ...i, num: np ? parseInt(np[1], 10) : null };
      });
    if (dedup.every((e) => Number.isFinite(e.num))) {
      dedup.sort((a, b) => a.num - b.num);
    }
    const grid = document.createElement("nav");
    grid.className = "toc-grid";
    grid.setAttribute("aria-label", "Inhaltsverzeichnis");
    grid.innerHTML = dedup.map((i) => {
      const m = i.text.match(/\(([A-Za-zÄÖÜäöüß]+)\)\s*$/);
      const code = m ? m[1] : "";
      const label = code ? i.text.replace(/\s*\(([A-Za-zÄÖÜäöüß]+)\)\s*$/, "").trim() : i.text;
      return `<a class="toc-card" href="#${i.id}">
        ${code ? `<span class="toc-card-code">${escapeHtml(code)}</span>` : ""}
        <span class="toc-card-label">${escapeHtml(label)}</span>
        <span class="toc-card-arrow" aria-hidden="true">→</span>
      </a>`;
    }).join("");
    tbl.replaceWith(grid);
  });

  // 3. TOC-Listen (ol/ul) erkennen — Items mit nur Text/Link auf Headings
  body.querySelectorAll("ol, ul").forEach((list) => {
    if (list.parentElement && list.parentElement.closest("ol, ul, .toc-list, .toc-grid")) return;
    const items = [...list.querySelectorAll(":scope > li")];
    if (items.length < 3) return;
    const matched = items.map((li) => {
      const text = (li.textContent || "").trim();
      if (!text || text.length > 100) return null;
      const id = findId(text);
      return id ? { text, id } : null;
    });
    const hits = matched.filter(Boolean).length;
    if (hits / items.length < 0.6) return;
    // Items aufbauen, leere/unverknüpfte rausfiltern, numerisch sortieren
    const entries = items.map((li, idx) => {
      const m = matched[idx];
      if (!m) return null;
      const text = (li.textContent || "").trim();
      const np = text.match(/^\s*(\d+)\.\s*(.+)$/);
      const label = np ? np[2] : text;
      const num = np ? parseInt(np[1], 10) : (list.tagName === "OL" ? (idx + 1) : null);
      return { id: m.id, label, num };
    }).filter(Boolean);
    if (entries.length < 2) return;
    if (entries.every((e) => Number.isFinite(e.num))) {
      entries.sort((a, b) => a.num - b.num);
    }
    const wrap = document.createElement("nav");
    wrap.className = "toc-list" + (list.tagName === "OL" ? " toc-list-numbered" : "");
    wrap.setAttribute("aria-label", "Inhaltsverzeichnis");
    wrap.innerHTML = entries.map((e) => `
      <a class="toc-list-item" href="#${e.id}">
        ${Number.isFinite(e.num) ? `<span class="toc-list-num">${escapeHtml(String(e.num))}</span>` : ""}
        <span class="toc-list-label">${escapeHtml(e.label)}</span>
        <span class="toc-list-arrow" aria-hidden="true">→</span>
      </a>
    `).join("");
    list.replaceWith(wrap);
  });

  // 4. Bereits vorhandene interne <a href="#..."> ebenfalls aufhübschen wenn sie auf Headings zeigen
  body.querySelectorAll('a[href^="#"]').forEach((a) => {
    if (a.closest(".toc-grid, .toc-list")) return;
    if (!a.classList.contains("toc-jump")) a.classList.add("toc-jump");
  });

  // 4b. Auto-TOC: wenn kein TOC erkannt wurde + ≥3 Headings vorhanden, baue eines aus h2/h3
  if (!body.querySelector(".toc-grid, .toc-list")) {
    // Nur echte Section-Titel: kurzer Text, kein Satzzeichen-Ende (Sätze/Absätze raus,
    // z.B. fälschlich als <h3> markierte "(2) Wegen einer mit Strafe…"-Klausel).
    const isHeadingLike = (t) => {
      if (!t || t.length > 70) return false;
      if (/^\(\d+/.test(t)) return false;          // "(2) …" = Absatz, kein Titel
      if (/[.;:]$/.test(t)) return false;           // endet wie ein Satz
      const words = t.split(/\s+/).length;
      return words <= 9;                            // Titel sind kurz
    };
    const hs = [...body.querySelectorAll("h2, h3")]
      .filter((h) => isHeadingLike((h.textContent || "").trim()));
    if (hs.length >= 3) {
      const auto = document.createElement("nav");
      auto.className = "toc-list";
      auto.setAttribute("aria-label", "Inhaltsverzeichnis");
      auto.innerHTML = hs.map((h, idx) => {
        const text = (h.textContent || "").trim();
        const np = text.match(/^\s*(\d+)\.\s*(.+)$/) || text.match(/^\s*§\s*(\d+)[a-z]?\.?\s*(.+)$/i);
        const num = np ? np[1] : String(idx + 1);
        const label = np ? np[2] : text;
        return `<a class="toc-list-item" href="#${h.id}">
          <span class="toc-list-num">${escapeHtml(num)}</span>
          <span class="toc-list-label">${escapeHtml(label)}</span>
          <span class="toc-list-arrow" aria-hidden="true">→</span>
        </a>`;
      }).join("");
      body.insertBefore(auto, body.firstChild);
    }
  }

  // 5. Smooth-Scroll für alle internen Anker
  body.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener("click", (ev) => {
      const href = a.getAttribute("href") || "";
      const id = decodeURIComponent(href.slice(1));
      if (!id) return;
      let target = body.querySelector("#" + CSS.escape(id)) || document.getElementById(id);
      if (!target) {
        // Fallback: über Link-Text passendes Heading suchen (docx-Bookmarks wie #_Toc12345)
        const fid = findId(a.textContent || "");
        if (fid) target = body.querySelector("#" + CSS.escape(fid));
      }
      if (!target) return;
      ev.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
      target.classList.add("flash-target");
      setTimeout(() => target.classList.remove("flash-target"), 1400);
    });
  });
}

function renderKatalogView() {
  const main = $("#main");
  const data = window.BUSSGELDKATALOG || [];
  const q = state.filter.toLowerCase().trim();
  const sections = data
    .map((sec) => ({
      ...sec,
      entries: q
        ? sec.entries.filter((e) =>
            (e.paragraph + " " + e.bedeutung + " " + e.sonstiges).toLowerCase().includes(q)
          )
        : sec.entries,
    }))
    .filter((sec) => sec.entries.length > 0);

  const tabelle = window.LOS_SANTOS_TABELLE || [];
  const tabelleFiltered = q
    ? tabelle.filter((e) =>
        (e.rechtsgrundlage + " " + e.tatbestand + " " + e.anspruch + " " + (e.bemerkung || "")).toLowerCase().includes(q))
    : tabelle;

  const searchBar = `<div class="kat-search">
    <input class="search" id="search" type="text" placeholder="Delikt oder Paragraph suchen…" value="${escapeHtml(state.filter)}" autocomplete="off" />
  </div>`;

  const body = sections
    .map(
      (s) => `
    <section class="kat-section" id="kat-${escapeHtml(s.code)}">
      <h2 class="kat-h2"><span class="kat-code">[${escapeHtml(s.code)}]</span> ${escapeHtml(s.title)}</h2>
      <div class="kat-table-wrap">
        <table class="kat-table">
          <thead><tr>
            <th style="width:130px">§ Paragraph</th>
            <th>Bedeutung</th>
            <th style="width:160px">Geldstrafe</th>
            <th style="width:120px">Hafteinheiten</th>
            <th>Sonstige Sanktionen</th>
          </tr></thead>
          <tbody>
            ${s.entries
              .map(
                (e) => `<tr>
                  <td class="kat-par">${escapeHtml(e.paragraph)}</td>
                  <td>${escapeHtml(e.bedeutung)}</td>
                  <td class="kat-num">${escapeHtml(e.strafe || "—")}</td>
                  <td class="kat-num">${escapeHtml(e.he || "—")}</td>
                  <td class="kat-extra">${escapeHtml(e.sonstiges || "")}</td>
                </tr>`
              )
              .join("")}
          </tbody>
        </table>
      </div>
    </section>`
    )
    .join("");

  const topNavEntries = [
    ...sections.map((s) => ({ code: s.code, title: s.title, href: `#kat-${s.code}` })),
    ...(tabelleFiltered.length ? [{ code: "ZIV", title: "Schadensersatz", href: "#kat-schaden" }] : []),
  ];
  const topNav = topNavEntries.length
    ? `<nav class="kat-toc-top" aria-label="Kategorien">
        ${topNavEntries.map((s) => `
          <a class="kat-toc-btn" href="${escapeHtml(s.href)}" title="${escapeHtml(s.title)}">
            <span class="kat-toc-btn-code">${escapeHtml(s.code)}</span>
          </a>`).join("")}
       </nav>`
    : "";

  const schadenSection = tabelleFiltered.length
    ? `<section class="kat-section" id="kat-schaden">
        <h2 class="kat-h2"><span class="kat-code">[ZIV]</span> Schadensersatztabelle (zivilrechtliche Ansprüche)</h2>
        <p class="kat-section-intro">Höchstwerte für zivilrechtliche Schadenersatzansprüche. Berechnung erfolgt als Vielfaches des Höchst-Bußgeldes der jeweiligen Rechtsgrundlage.</p>
        <div class="kat-table-wrap">
          <table class="kat-table schaden-table">
            <thead><tr>
              <th style="width:140px">Rechtsgrundlage</th>
              <th>Tatbestand</th>
              <th style="width:230px">Anspruchshöhe</th>
              <th>Bemerkung</th>
            </tr></thead>
            <tbody>
              ${tabelleFiltered.map((e) => {
                const m = /^(.*?)(\d+)\s*-?\s*fachen\s+(.*)$/i.exec(e.anspruch || "");
                const anspruchHtml = m
                  ? `${escapeHtml(m[1].trim())} <span class="faktor-chip">${escapeHtml(m[2])}<small>×</small></span> ${escapeHtml(m[3].trim())}`
                  : escapeHtml(e.anspruch);
                return `<tr>
                <td class="kat-par">${escapeHtml(e.rechtsgrundlage)}</td>
                <td>${escapeHtml(e.tatbestand)}</td>
                <td class="kat-num">${anspruchHtml}</td>
                <td class="kat-extra">${escapeHtml(e.bemerkung || "")}</td>
              </tr>`;
              }).join("")}
            </tbody>
          </table>
        </div>
      </section>`
    : "";

  const article = `<article class="article">
    <div class="article-head">
      <div class="cat-tag">Katalog</div>
      <h2>Bußgeldkatalog von Los Santos</h2>
      <div class="article-meta">Hafteinheit (HE) = 1 Minute · Maximalhaft 45 Min · in besonders schweren Fällen bis 60 Min mit Genehmigung durch Polizei Rang 10+</div>
    </div>
    <div class="article-body">
      ${searchBar}
      ${topNav}
      ${body || (tabelleFiltered.length ? "" : `<p><em>Keine Einträge gefunden.</em></p>`)}
      ${schadenSection}
    </div>
  </article>`;

  main.innerHTML = article;
}

// ---------- Bußgeldrechner ----------

function _toNum(s) {
  const n = parseInt(String(s ?? "").replace(/[^\d]/g, ""), 10);
  return isNaN(n) ? 0 : n;
}

function renderRechnerView() {
  const main = $("#main");
  const data = window.BUSSGELDKATALOG || [];
  const all = [];
  data.forEach((sec) =>
    (sec.entries || []).forEach((e) =>
      all.push({
        code: sec.code,
        paragraph: e.paragraph,
        bedeutung: e.bedeutung,
        strafe: e.strafe || "—",
        heRaw: e.he || "—",
        geld: _toNum(e.strafe),
        he: _toNum(e.he),
      })
    )
  );

  const q = (state.rechnerQuery || "").toLowerCase().trim();
  const matches = q
    ? all
        .filter((e) =>
          (e.paragraph + " " + e.bedeutung + " " + e.code).toLowerCase().includes(q)
        )
        .slice(0, 40)
    : [];

  const sel = state.rechnerSel || [];
  const totalGeld = sel.reduce((s, i) => s + i.geld, 0);
  const totalHe = sel.reduce((s, i) => s + i.he, 0);

  const results = q
    ? matches.length
      ? matches
          .map(
            (e) => `
        <button class="rch-result rechner-add" data-par="${escapeHtml(e.paragraph)}" data-bed="${escapeHtml(e.bedeutung)}" data-geld="${e.geld}" data-he="${e.he}">
          <span class="rch-result-main"><span class="rch-code">[${escapeHtml(e.code)}]</span> <span class="rch-par">${escapeHtml(e.paragraph)}</span> ${escapeHtml(e.bedeutung)}</span>
          <span class="rch-result-vals"><span class="rch-geld">${escapeHtml(e.strafe)}</span><span class="rch-he">${escapeHtml(String(e.heRaw))} HE</span></span>
        </button>`
          )
          .join("")
      : `<p class="rch-empty">Keine Treffer für „${escapeHtml(state.rechnerQuery)}".</p>`
    : `<p class="rch-hint">Tippe ein Delikt oder einen Paragraphen ein, um Einträge zur Auswahl hinzuzufügen.</p>`;

  const selList = sel.length
    ? sel
        .map(
          (e, i) => `
      <li class="rch-sel-item">
        <span class="rch-sel-main"><span class="rch-par">${escapeHtml(e.paragraph)}</span> ${escapeHtml(e.bedeutung)}</span>
        <span class="rch-sel-vals">
          <span class="rch-geld">$${e.geld.toLocaleString("de-DE")}</span>
          <span class="rch-he">${e.he} HE</span>
          <button class="rch-del rechner-del" data-idx="${i}" title="Entfernen">✕</button>
        </span>
      </li>`
        )
        .join("")
    : `<li class="rch-sel-empty">Noch nichts ausgewählt.</li>`;

  main.innerHTML = `<article class="article">
    <div class="article-head">
      <div class="cat-tag">Rechner</div>
      <h2>Bußgeldrechner</h2>
      <div class="article-meta">Delikte zusammenstellen, Gesamtstrafe automatisch berechnen. Datengrundlage: offizieller Bußgeldkatalog. Hafteinheit (HE) = 1 Minute · Maximalhaft 45 Min.</div>
    </div>
    <div class="article-body">
      <div class="rch-grid">
        <div class="rch-search-col">
          <div class="kat-search">
            <input class="search" id="rechner-search" type="text" placeholder="Delikt oder Paragraph suchen…" value="${escapeHtml(state.rechnerQuery || "")}" autocomplete="off" />
          </div>
          <div class="rch-results">${results}</div>
        </div>
        <aside class="rch-cart">
          <div class="rch-cart-head">
            <h3>Auswahl</h3>
            ${sel.length ? `<button class="rch-clear" id="rechner-clear">Alle entfernen</button>` : ""}
          </div>
          <ul class="rch-sel-list">${selList}</ul>
          <div class="rch-total">
            <div class="rch-total-row"><span>Gesamtstrafe</span><span class="rch-total-geld">$${totalGeld.toLocaleString("de-DE")}</span></div>
            <div class="rch-total-row"><span>Hafteinheiten</span><span class="rch-total-he">${totalHe} HE</span></div>
          </div>
        </aside>
      </div>
    </div>
  </article>`;
}

function renderChangelogView() {
  const main = $("#main");
  const loading = !state.changelog;
  const { recentEdits = [], approvedProposals = [] } = state.changelog || {};
  // System / admin Einträge ausblenden — nur Community-Vorschläge & echte Personen
  const HIDDEN = new Set(["system", "admin", "System", "Admin", null, undefined, ""]);
  const items = recentEdits
    .filter((e) => !HIDDEN.has(e.updatedBy))
    .map((e) => ({
      when: e.updatedAt, title: e.title, slug: e.slug, by: e.updatedBy, category: e.category,
    }))
    .sort((a, b) => (b.when || 0) - (a.when || 0));

  // Gruppieren nach Tag (relativ: Heute / Gestern / Datum)
  const fmtDay = (ms) => {
    if (!ms) return "Unbekannt";
    const d = new Date(ms);
    const today = new Date(); today.setHours(0,0,0,0);
    const yest = new Date(today.getTime() - 86400000);
    const d0 = new Date(d); d0.setHours(0,0,0,0);
    if (d0.getTime() === today.getTime()) return "Heute";
    if (d0.getTime() === yest.getTime()) return "Gestern";
    return d.toLocaleDateString("de-DE", { weekday: "long", day: "2-digit", month: "long", year: "numeric" });
  };
  const fmtTime = (ms) => ms ? new Date(ms).toLocaleTimeString("de-DE", { hour: "2-digit", minute: "2-digit" }) : "—";

  const groups = [];
  items.forEach((it) => {
    const day = fmtDay(it.when);
    let g = groups.find((x) => x.day === day);
    if (!g) { g = { day, items: [] }; groups.push(g); }
    g.items.push(it);
  });

  const stats = `
    <div class="changelog-stats">
      <div class="cl-stat"><span class="cl-stat-num">${items.length}</span><span class="cl-stat-lbl">Änderungen</span></div>
      <div class="cl-stat"><span class="cl-stat-num">${groups.length}</span><span class="cl-stat-lbl">Tage</span></div>
      <div class="cl-stat"><span class="cl-stat-num">${new Set(items.map(i=>i.category).filter(Boolean)).size}</span><span class="cl-stat-lbl">Kategorien</span></div>
    </div>`;

  const list = loading
    ? `<div class="changelog-skeleton">
        ${Array.from({length:5}).map(() => `<div class="cl-sk-row"></div>`).join("")}
      </div>`
    : (items.length === 0
      ? `<div class="changelog-empty">
          <div class="cl-empty-icon">⚖</div>
          <strong>Keine Community-Änderungen bisher</strong>
          <p>Sobald Vorschläge angenommen werden, erscheinen sie hier.</p>
        </div>`
      : groups.map((g) => `
        <section class="cl-group">
          <header class="cl-group-head"><span>${escapeHtml(g.day)}</span><span class="cl-group-count">${g.items.length}</span></header>
          <ol class="cl-list">
            ${g.items.map((it) => `
              <li class="cl-item">
                <span class="cl-time">${escapeHtml(fmtTime(it.when))}</span>
                <a class="cl-link changelog-link" href="#${encodeURIComponent(it.slug)}" data-slug="${escapeHtml(it.slug)}">${escapeHtml(it.title)}</a>
                ${it.category ? `<span class="cl-cat">${escapeHtml(it.category)}</span>` : ""}
                <span class="cl-by">${escapeHtml(it.by)}</span>
              </li>
            `).join("")}
          </ol>
        </section>
      `).join(""));

  main.innerHTML = `
    <div class="changelog-shell">
      <div class="changelog-card">
        <header class="changelog-head">
          <h2>Regelwerkänderungen</h2>
          <p>Alle Anpassungen am Regelwerk, die durch Vorschläge der Community freigegeben wurden. Kürzlich geänderte Passagen sind in der Regel selbst goldfarben hervorgehoben.</p>
        </header>
        ${stats}
        ${list}
      </div>
    </div>
  `;
  main.querySelectorAll(".changelog-link").forEach((a) =>
    a.addEventListener("click", (e) => {
      e.preventDefault();
      state.tab = "laws";
      state.current = a.dataset.slug;
      history.replaceState(null, "", "#" + encodeURIComponent(a.dataset.slug));
      render();
    })
  );
}

function renderProposeModal() {
  const wrap = document.createElement("div");
  wrap.className = "modal-bg";
  wrap.id = "modal-bg";
  const law = state.proposeKind === "edit" && state.current
    ? state.laws.find((l) => l.slug === state.current)
    : null;
  if (state.proposeKind === "edit" && !law) { state.proposing = false; return; }

  const initialHtml = state.proposeKind === "edit"
    ? (law.html || "<p>Inhalt…</p>")
    : "<h2>Titel</h2><p>Inhalt eingeben…</p>";
  const initialTitle = state.proposeKind === "edit" ? law.title : "";
  const initialCat = state.proposeKind === "edit" ? (law.category || "Allgemeine Regeln") : "Allgemeine Regeln";

  wrap.innerHTML = `
    <div class="modal" style="max-width:1100px">
      <div class="modal-head">
        <h3>${state.proposeKind === "edit" ? `Änderung vorschlagen: ${escapeHtml(law.title)}` : "Neue Regel vorschlagen"}</h3>
        <button class="btn btn-ghost" id="close-prop">✕</button>
      </div>
      <div class="modal-body">
        <p class="notice notice-info">Dein Vorschlag wird vom Team geprüft. Erst nach manueller Freigabe durch einen Mitarbeiter wird er übernommen. Bis dahin sieht das Team deinen Vorschlag separat hervorgehoben.</p>

        <div class="field-row">
          <div class="field"><label>Dein Name *</label><input type="text" id="prop-name" maxlength="80" required placeholder="z.B. Max Mustermann" /></div>
          <div class="field"><label>Unternehmen / Job (optional)</label><input type="text" id="prop-contact" maxlength="120" placeholder="z.B. LSPD, LSMD, DOJ, Anwaltskanzlei…" /></div>
        </div>

        <div class="field-row">
          <div class="field">
            <label>Titel${state.proposeKind === "new" ? " *" : ""}</label>
            <input type="text" id="prop-title" value="${escapeHtml(initialTitle)}" ${state.proposeKind === "new" ? "required" : ""} />
          </div>
          <div class="field">
            <label>Kategorie</label>
            <select id="prop-cat">
              ${CATEGORY_ORDER.map((c) => `<option ${c === initialCat ? "selected" : ""}>${c}</option>`).join("")}
            </select>
          </div>
        </div>

        <label>Inhalt</label>
        <div class="wysiwyg-wrap">
          <div class="edit-toolbar wysiwyg-toolbar">
            <button class="fmt" data-cmd="bold" title="Fett (Strg+B)" type="button"><b>B</b></button>
            <button class="fmt" data-cmd="italic" title="Kursiv (Strg+I)" type="button"><i>I</i></button>
            <button class="fmt" data-cmd="underline" title="Unterstrichen" type="button"><u>U</u></button>
            <span class="toolbar-divider"></span>
            <button class="fmt" data-block="h2" title="Überschrift" type="button">H2</button>
            <button class="fmt" data-block="h3" title="Unter-Überschrift" type="button">H3</button>
            <button class="fmt" data-block="p" title="Absatz" type="button">¶</button>
            <span class="toolbar-divider"></span>
            <button class="fmt" data-cmd="insertUnorderedList" title="Liste" type="button">• Liste</button>
            <button class="fmt" data-cmd="insertOrderedList" title="Nummeriert" type="button">1. Liste</button>
            <span class="toolbar-divider"></span>
            <button class="fmt" data-cmd="removeFormat" title="Formatierung entfernen" type="button">×</button>
          </div>
          <div class="article-body editable wysiwyg-body" id="prop-body" contenteditable="true" spellcheck="true">${initialHtml}</div>
        </div>

        <div class="field" style="margin-top:14px">
          <label>Begründung *</label>
          <textarea id="prop-summary" placeholder="z.B. Tippfehler in §3 korrigiert / neue Regelung für…" style="min-height:90px" required></textarea>
        </div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" id="close-prop2">Abbrechen</button>
        <button class="btn btn-gold" id="submit-prop">Vorschlag einreichen</button>
      </div>
    </div>
  `;
  document.body.appendChild(wrap);

  // Wire up WYSIWYG toolbar
  const body = wrap.querySelector("#prop-body");
  wrap.querySelectorAll(".fmt").forEach((b) =>
    b.addEventListener("mousedown", (e) => {
      e.preventDefault();
      body.focus();
      const cmd = b.dataset.cmd;
      const block = b.dataset.block;
      if (block) document.execCommand("formatBlock", false, block);
      else if (cmd) document.execCommand(cmd, false, null);
    })
  );

  const close = () => { state.proposing = false; document.getElementById("modal-bg")?.remove(); };
  wrap.querySelector("#close-prop").addEventListener("click", close);
  wrap.querySelector("#close-prop2").addEventListener("click", close);
  wrap.addEventListener("click", (e) => { if (e.target === wrap) close(); });

  wrap.querySelector("#submit-prop").addEventListener("click", async () => {
    const payload = {
      kind: state.proposeKind,
      slug: state.proposeKind === "edit" ? state.current : null,
      proposerName: wrap.querySelector("#prop-name").value.trim(),
      proposerContact: wrap.querySelector("#prop-contact").value.trim(),
      proposedTitle: wrap.querySelector("#prop-title").value.trim() || null,
      proposedCategory: wrap.querySelector("#prop-cat").value,
      proposedHtml: body.innerHTML,
      summary: wrap.querySelector("#prop-summary").value.trim(),
    };
    if (!payload.proposerName) return showToast("error", "Bitte Namen eintragen.");
    if (!payload.summary) return showToast("error", "Bitte Begründung eintragen.");
    if (state.proposeKind === "new" && !payload.proposedTitle)
      return showToast("error", "Bitte Titel eintragen.");
    try {
      await api("/api/proposals", { method: "POST", body: JSON.stringify(payload) });
      close();
      await loadPendingProposals();
      render();
      showToast("success", "Vorschlag eingereicht. Danke!");
    } catch (e) { showToast("error", "Fehler: " + e.message); }
  });
}

// Toast notifications (replace alert)
function showToast(kind, msg) {
  const t = document.createElement("div");
  t.className = `toast toast-${kind}`;
  t.textContent = msg;
  document.body.appendChild(t);
  requestAnimationFrame(() => t.classList.add("toast-show"));
  setTimeout(() => {
    t.classList.remove("toast-show");
    setTimeout(() => t.remove(), 300);
  }, 3500);
}

// ---------- Events ----------

document.addEventListener("click", (e) => {
  const navBtn = e.target.closest("[data-tab]");
  if (navBtn) {
    state.tab = navBtn.dataset.tab;
    if (state.tab === "changelog" && !state.changelog) loadChangelog().then(render).catch(() => {});
    render();
    return;
  }
  // --- Bußgeldrechner ---
  const rAdd = e.target.closest(".rechner-add");
  if (rAdd) {
    if (!Array.isArray(state.rechnerSel)) state.rechnerSel = [];
    state.rechnerSel.push({
      paragraph: rAdd.dataset.par,
      bedeutung: rAdd.dataset.bed,
      geld: parseInt(rAdd.dataset.geld, 10) || 0,
      he: parseInt(rAdd.dataset.he, 10) || 0,
    });
    render();
    return;
  }
  const rDel = e.target.closest(".rechner-del");
  if (rDel) {
    (state.rechnerSel || []).splice(parseInt(rDel.dataset.idx, 10), 1);
    render();
    return;
  }
  if (e.target.closest("#rechner-clear")) {
    state.rechnerSel = [];
    render();
    return;
  }

  const link = e.target.closest(".law-link");
  if (link && link.dataset.slug) {
    e.preventDefault();
    state.current = link.dataset.slug;
    history.replaceState(null, "", "#" + encodeURIComponent(state.current));
    render();
    return;
  }
  if (e.target.id === "propose-edit") {
    state.proposeKind = "edit";
    state.proposing = true;
    render();
    return;
  }
  if (e.target.id === "propose-new") {
    state.proposeKind = "new";
    state.proposing = true;
    render();
    return;
  }
});

document.addEventListener("input", (e) => {
  if (e.target.id === "search") {
    state.filter = e.target.value;
    render();
    const s = $("#search");
    if (s) { s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
  } else if (e.target.id === "rechner-search") {
    state.rechnerQuery = e.target.value;
    render();
    const s = $("#rechner-search");
    if (s) { s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
  }
});

window.addEventListener("hashchange", () => {
  const slug = decodeURIComponent(location.hash.replace(/^#/, ""));
  if (slug && state.laws.find((l) => l.slug === slug)) {
    state.tab = "laws";
    state.current = slug;
    render();
  }
});

// ---------- Init ----------

(async function init() {
  await Promise.all([loadLaws(), loadPendingProposals()]);
  const hashSlug = decodeURIComponent(location.hash.replace(/^#/, ""));
  const valid = hashSlug && state.laws.find((l) => l.slug === hashSlug);
  state.current = valid
    ? hashSlug
    : (state.laws.find((l) => /grundgesetz/i.test(l.title)) || state.laws[0])?.slug;
  render();
})();
