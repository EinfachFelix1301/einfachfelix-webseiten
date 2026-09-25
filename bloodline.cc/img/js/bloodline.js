/* ==========================================================================
   BLOODLINE — MEDIA GALLERY
   bloodline.js — Frontend-Verhalten (kein Framework, kein Build)
   --------------------------------------------------------------------------
   Alle Server-Aufrufe laufen über BL.api() und sind hier bewusst als
   Platzhalter markiert (TODO), damit du sie 1:1 auf api.php mappen kannst.
   ========================================================================== */

(function () {
  'use strict';

  var BL = window.BL = {};

  /* --- Konfiguration ---------------------------------------------------- */
  BL.config = {
    apiUrl: 'api.php',
    imgBase: '/img',
    path: '',                 // aktueller Ordner relativ (z.B. phone/wallpaper)
    maxBytes: 1024 * 1024 * 1024,
    // Erlaubte Endungen — vom PHP-Template (ALLOWED_EXTENSIONS) via BL_CFG überschrieben.
    allowedExt: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'pdf', 'zip', 'rar', '7z',
                 'txt', 'json', 'xml', 'mp4', 'mp3', 'wav', 'ogg', 'webm', 'mov', 'avi',
                 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv']
  };
  // Vom PHP-Template injiziert (window.BL_CFG)
  if (window.BL_CFG) { Object.keys(window.BL_CFG).forEach(function (k) { BL.config[k] = window.BL_CFG[k]; }); }

  /* --- Helfer ----------------------------------------------------------- */
  BL.$ = function (sel, root) { return (root || document).querySelector(sel); };
  BL.$$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  BL.esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };

  /* Gleiche Regel wie sanitize_upload_stem() in config.php */
  BL.sanitize = function (name) {
    var dot = name.lastIndexOf('.');
    var stem = dot > 0 ? name.slice(0, dot) : name;
    var ext = dot > 0 ? name.slice(dot + 1).toLowerCase() : 'png';
    var safe = stem.replace(/[^a-zA-Z0-9_-]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    return (safe || 'file') + '.' + ext;
  };

  /* Relativer Pfad einer Datei im aktuellen Ordner (fuer api.php) */
  BL.rel = function (name) { return (BL.config.path ? BL.config.path + '/' : '') + name; };

  /* Anträge-Badge in der Sidebar aktualisieren */
  BL.updateReqBadge = function (n) {
    if (n == null) return;
    var b = BL.$('.bl-nav .bl-badge');
    if (b) { b.textContent = n; b.hidden = n <= 0; }
  };

  BL.formatSize = function (bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return Math.round(bytes / 1024) + ' KB';
    return (bytes / 1048576).toFixed(2).replace('.', ',') + ' MB';
  };

  /* --- Dateityp-Erkennung + Vorschau-Icons ------------------------------ */
  BL.IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
  BL.VIDEO_EXT = ['mp4', 'webm', 'mov', 'avi'];

  BL.extOf = function (name) {
    var dot = String(name || '').lastIndexOf('.');
    return dot > 0 ? name.slice(dot + 1).toLowerCase() : '';
  };
  BL.isImageExt = function (ext) { return BL.IMAGE_EXT.indexOf(ext) !== -1; };
  BL.isVideoExt = function (ext) { return BL.VIDEO_EXT.indexOf(ext) !== -1; };

  /* FontAwesome-Icon je Dateityp (für Nicht-Bild/Video-Vorschau) */
  BL.fileIcon = function (ext) {
    if (['zip', 'rar', '7z'].indexOf(ext) !== -1) return 'fa-file-zipper';
    if (['mp3', 'wav', 'ogg'].indexOf(ext) !== -1) return 'fa-file-audio';
    if (ext === 'pdf') return 'fa-file-pdf';
    if (['doc', 'docx'].indexOf(ext) !== -1) return 'fa-file-word';
    if (['xls', 'xlsx', 'csv'].indexOf(ext) !== -1) return 'fa-file-excel';
    if (['ppt', 'pptx'].indexOf(ext) !== -1) return 'fa-file-powerpoint';
    if (['txt', 'json', 'xml'].indexOf(ext) !== -1) return 'fa-file-lines';
    return 'fa-file';
  };

  BL.toast = function (msg) {
    var el = BL.$('#bl-toast');
    if (!el) return;
    BL.$('span', el).textContent = msg;
    el.hidden = false;
    clearTimeout(BL._toastTimer);
    BL._toastTimer = setTimeout(function () { el.hidden = true; }, 2400);
  };

  BL.copy = function (text) {
    if (navigator.clipboard) navigator.clipboard.writeText(text);
    BL.toast('Link kopiert: ' + text);
  };

  /* --- API ------------------------------------------------------------- */
  /* TODO: an die Actions in api.php anschließen. */
  BL.api = function (action, payload) {
    var body = new FormData();
    body.append('action', action);
    Object.keys(payload || {}).forEach(function (k) {
      var v = payload[k];
      if (v instanceof File) body.append(k, v);
      else if (Array.isArray(v)) body.append(k, JSON.stringify(v));
      else body.append(k, v);
    });
    return fetch(BL.config.apiUrl, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); });
  };

  /* --- Dropzone -------------------------------------------------------- */
  BL.dropzone = function (zone, onFiles) {
    if (!zone) return;
    var input = BL.$('input[type=file]', zone);
    zone.addEventListener('click', function () { if (input) input.click(); });
    if (input) {
      input.addEventListener('change', function () { onFiles(input.files); input.value = ''; });
    }
    ['dragenter', 'dragover'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-over'); });
    });
    ['dragleave', 'dragend'].forEach(function (ev) {
      zone.addEventListener(ev, function () { zone.classList.remove('is-over'); });
    });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      zone.classList.remove('is-over');
      onFiles(e.dataTransfer.files);
    });
  };

  /* --- Warteschlange --------------------------------------------------- */
  function Queue(listEl, allowExt) {
    this.items = [];
    this.el = listEl;
    // Erlaubte Endungen dieser Queue (Landing = nur Bilder, Panel = alle)
    this.allowExt = allowExt || BL.config.allowedExt;
  }

  Queue.prototype.add = function (fileList) {
    var self = this;
    Array.prototype.slice.call(fileList || []).forEach(function (file) {
      var ext = BL.extOf(file.name);
      if (self.allowExt.indexOf(ext) === -1) {
        BL.toast('Nicht unterstützt: ' + file.name);
        return;
      }
      if (file.size > BL.config.maxBytes) {
        BL.toast('Zu groß: ' + file.name);
        return;
      }
      // Object-URL nur für Bild/Video (Vorschau) — Rest bekommt Icon.
      var preview = (BL.isImageExt(ext) || BL.isVideoExt(ext)) ? URL.createObjectURL(file) : null;
      self.items.push({
        file: file,
        ext: ext,
        safeName: BL.sanitize(file.name),
        url: preview
      });
    });
    this.render();
  };

  Queue.prototype.remove = function (i) {
    if (this.items[i].url) URL.revokeObjectURL(this.items[i].url);
    this.items.splice(i, 1);
    this.render();
  };

  Queue.prototype.clear = function () {
    this.items.forEach(function (it) { if (it.url) URL.revokeObjectURL(it.url); });
    this.items = [];
    this.render();
  };

  Queue.prototype.render = function () {
    if (!this.el) return;
    var self = this;
    this.el.hidden = this.items.length === 0;
    this.el.innerHTML = this.items.map(function (it, i) {
      var thumb;
      if (it.url && BL.isImageExt(it.ext)) {
        thumb = '<div class="bl-queue-thumb" style="background-image:url(&quot;' + it.url + '&quot;)"></div>';
      } else if (it.url && BL.isVideoExt(it.ext)) {
        thumb = '<div class="bl-queue-thumb"><video src="' + it.url + '" muted playsinline preload="metadata"></video></div>';
      } else {
        thumb = '<div class="bl-queue-thumb bl-queue-thumb--file"><i class="fas ' + BL.fileIcon(it.ext) + '"></i></div>';
      }
      return '' +
        '<div class="bl-queue-item">' +
          thumb +
          '<div class="bl-queue-body">' +
            '<b>' + BL.esc(it.safeName) + '</b>' +
            '<span>' + BL.esc(it.file.name) + ' · ' + BL.formatSize(it.file.size) + '</span>' +
          '</div>' +
          '<div class="bl-queue-state">Bereit</div>' +
          '<i class="fas fa-xmark bl-x" data-q="' + i + '"></i>' +
        '</div>';
    }).join('');
    BL.$$('[data-q]', this.el).forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        self.remove(parseInt(btn.dataset.q, 10));
      });
    });
  };

  BL.Queue = Queue;

  /* ======================================================================
     LANDING — öffentliches Einreichen (immer Antrag, immer fester Ordner)
     ====================================================================== */
  BL.initLanding = function () {
    var form = BL.$('#bl-guest-form');
    if (!form) return;

    var queue = new Queue(BL.$('#bl-guest-queue'), BL.IMAGE_EXT);
    BL.dropzone(BL.$('#bl-guest-drop'), function (files) { queue.add(files); });

    BL.$('#bl-guest-submit').addEventListener('click', function () {
      if (!queue.items.length) { BL.toast('Bitte zuerst ein Bild auswählen'); return; }
      var name = BL.$('#bl-guest-name').value.trim();
      if (!name) { BL.toast('Bitte deinen Namen im Server angeben'); return; }
      var note = BL.$('#bl-guest-note').value;
      var btn = BL.$('#bl-guest-submit');
      btn.disabled = true;

      var jobs = queue.items.map(function (it) {
        return BL.api('submit_request', { file: it.file, name: name, note: note });
      });
      Promise.all(jobs).then(function (results) {
        var ok = results.filter(function (r) { return r && r.success; }).length;
        btn.disabled = false;
        if (!ok) { BL.toast((results[0] && results[0].message) || 'Fehler beim Einreichen'); return; }
        form.hidden = true;
        BL.$('#bl-guest-done').hidden = false;
        queue.clear();
        BL.$('#bl-guest-note').value = '';
      }).catch(function () { btn.disabled = false; BL.toast('Netzwerkfehler'); });
    });

    BL.$('#bl-guest-again').addEventListener('click', function () {
      BL.$('#bl-guest-done').hidden = true;
      form.hidden = false;
    });
  };

  /* ======================================================================
     PANEL — Galerie, Anträge, Whitelist
     ====================================================================== */
  BL.initPanel = function () {
    var panel = BL.$('.bl-app');
    if (!panel) return;

    /* --- Deploy-/Version-Check: läuft neuer API-Code? ------------------ */
    BL.api('ping', {}).then(function (r) {
      var ok = r && r.features && r.features.dupcheck === true;
      console.log('[Bloodline] api_version=', (r && r.api_version) || 'UNBEKANNT (altes api.php)', '| dupcheck=', !!ok);
      if (ok) return;
      // Altes Backend -> sichtbares, bleibendes Banner
      if (BL.$('#bl-verbanner')) return;
      var b = document.createElement('div');
      b.id = 'bl-verbanner';
      b.style.cssText = 'position:fixed;left:0;right:0;top:0;z-index:9999;background:#c0261f;color:#fff;'
        + 'padding:10px 16px;font:600 13px system-ui;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.4)';
      b.textContent = '⚠ Server fährt ALTES api.php (api_version: '
        + ((r && r.api_version) || 'fehlt') + '). Duplikat-Check inaktiv → api.php neu hochladen + PHP-OpCache leeren.';
      document.body.appendChild(b);
    }).catch(function () {});

    /* --- Bestätigen-Modal (Löschen) ---------------------------------- */
    var cfm = BL.$('#bl-confirm'), cfmOk = BL.$('#bl-confirm-ok'),
        cfmTitle = BL.$('#bl-confirm-title'), cfmText = BL.$('#bl-confirm-text'), cfmCb = null;
    function closeCfm() { if (cfm) cfm.hidden = true; cfmCb = null; }
    function askConfirm(title, text, cb) {
      if (!cfm) { if (window.confirm(text || title)) cb(); return; }
      cfmTitle.textContent = title;
      cfmText.textContent = text || '';
      cfmCb = cb;
      cfm.hidden = false;
      setTimeout(function () { cfmOk.focus(); }, 30);
    }
    if (cfm) {
      cfm.addEventListener('click', function (e) { if (e.target === cfm) closeCfm(); });
      BL.$$('[data-confirm-close]', cfm).forEach(function (el) {
        if (el !== cfm) el.addEventListener('click', closeCfm);
      });
      cfmOk.addEventListener('click', function () { var c = cfmCb; closeCfm(); if (c) c(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !cfm.hidden) closeCfm(); });
    }

    /* --- Duplikat-Dialog (Überschreiben / Überspringen / Abbrechen) ---- */
    var dup = BL.$('#bl-dup'), dupList = BL.$('#bl-dup-list'), dupCb = null;
    function closeDup(choice) {
      if (dup) dup.hidden = true;
      var c = dupCb; dupCb = null;
      if (c) c(choice || 'cancel');
    }
    function askDup(items, cb) {
      // items = Queue-Items (mit .file, .ext, .url). Fallback akzeptiert auch reine Namen.
      var norm = items.map(function (it) {
        return (typeof it === 'string') ? { file: { name: it }, ext: BL.extOf(it), url: null } : it;
      });
      if (!dup) { cb(window.confirm(norm.length + ' Datei(en) existieren bereits. OK = überschreiben, Abbrechen = überspringen.') ? 'overwrite' : 'skip'); return; }
      dupCb = cb;
      dupList.innerHTML = norm.map(function (it) {
        // Vorschau der ALTEN (bereits vorhandenen) Datei vom Server; Fallback auf lokale Vorschau.
        var src = it.existingUrl || it.url;
        var thumb;
        if (src && BL.isImageExt(it.ext)) {
          thumb = '<span class="bl-dup-thumb" style="background-image:url(&quot;' + src + '&quot;)"></span>';
        } else if (src && BL.isVideoExt(it.ext)) {
          thumb = '<span class="bl-dup-thumb"><video src="' + src + '" muted playsinline preload="metadata"></video></span>';
        } else {
          thumb = '<span class="bl-dup-thumb bl-dup-thumb--file"><i class="fas ' + BL.fileIcon(it.ext) + '"></i></span>';
        }
        return '<li>' + thumb + '<span class="bl-dup-name">' + BL.esc(it.file.name) + '</span></li>';
      }).join('');
      BL.$('#bl-dup-count').textContent = norm.length;
      dup.hidden = false;
    }
    if (dup) {
      dup.addEventListener('click', function (e) { if (e.target === dup) closeDup('cancel'); });
      BL.$$('[data-dup]', dup).forEach(function (btn) {
        btn.addEventListener('click', function () { closeDup(btn.dataset.dup); });
      });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !dup.hidden) closeDup('cancel'); });
    }

    /* --- Eingabe-Dialog (ersetzt window.prompt) ----------------------- */
    var pmt = BL.$('#bl-prompt'), pmtInput = BL.$('#bl-prompt-input'),
        pmtTitle = BL.$('#bl-prompt-title'), pmtText = BL.$('#bl-prompt-text'),
        pmtOk = BL.$('#bl-prompt-ok'), pmtIc = BL.$('#bl-prompt-ic'), pmtCb = null, pmtAllowEmpty = false;
    function closePmt() { if (pmt) pmt.hidden = true; pmtCb = null; }
    function submitPmt() {
      var val = pmtInput.value.trim(); var c = pmtCb;
      if (!val && !pmtAllowEmpty) { pmtInput.focus(); return; }
      closePmt(); if (c) c(val);
    }
    /* askPrompt(opts, cb) — opts: {title, text, value, icon, ok, allowEmpty} */
    function askPrompt(opts, cb) {
      opts = opts || {};
      if (!pmt) { var v = window.prompt(opts.title || '', opts.value || ''); if (v !== null && (v || opts.allowEmpty)) cb(v); return; }
      pmtCb = cb;
      pmtAllowEmpty = !!opts.allowEmpty;
      pmtTitle.textContent = opts.title || 'Eingabe';
      if (opts.text) { pmtText.textContent = opts.text; pmtText.hidden = false; } else { pmtText.hidden = true; }
      if (pmtIc) pmtIc.className = 'fas ' + (opts.icon || 'fa-pen');
      pmtOk.innerHTML = '<i class="fas fa-check"></i>' + BL.esc(opts.ok || 'OK');
      pmtInput.value = opts.value || '';
      pmt.hidden = false;
      setTimeout(function () { pmtInput.focus(); pmtInput.select(); }, 30);
    }
    if (pmt) {
      pmt.addEventListener('click', function (e) { if (e.target === pmt) closePmt(); });
      BL.$$('[data-prompt-close]', pmt).forEach(function (el) { el.addEventListener('click', closePmt); });
      pmtOk.addEventListener('click', submitPmt);
      pmtInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); submitPmt(); }
        if (e.key === 'Escape') closePmt();
      });
    }

    /* --- Ansichten ---------------------------------------------------- */
    var labels = { gallery: 'Dateibrowser', requests: 'Freigaben', users: 'Einstellungen' };

    function showView(name) {
      BL.$$('.bl-view').forEach(function (v) { v.hidden = v.dataset.view !== name; });
      BL.$$('.bl-nav a').forEach(function (a) { a.classList.toggle('is-active', a.dataset.go === name); });
      BL.$('#bl-view-label').textContent = labels[name] || '';
      var cr = BL.$('#bl-crumbs'); if (cr) cr.hidden = name !== 'gallery';
      var ht = BL.$('#bl-head-tools'); if (ht) ht.hidden = name !== 'gallery';
    }
    // Nav-Links navigieren echt (Reload) -> Galerie zurueck auf Root + Live-Daten.
    // Aktive Ansicht kommt aus ?view= (vom Server gesetzt).

    /* --- Auswahl im Grid ---------------------------------------------- */
    var selbar = BL.$('#bl-selbar');

    function selected() { return BL.$$('.bl-item.is-selected'); }

    function syncSel() {
      var n = selected().length;
      selbar.hidden = n === 0;
      if (n) BL.$('#bl-sel-count').textContent = n + ' ausgewählt';
    }

    BL.$$('.bl-item-check').forEach(function (chk) {
      chk.addEventListener('click', function (e) {
        e.stopPropagation();
        chk.closest('.bl-item').classList.toggle('is-selected');
        syncSel();
      });
    });

    BL.$('#bl-sel-clear').addEventListener('click', function () {
      selected().forEach(function (i) { i.classList.remove('is-selected'); });
      syncSel();
    });

    BL.$('#bl-sel-copy').addEventListener('click', function () {
      var links = selected().map(function (i) { return i.dataset.url; });
      if (navigator.clipboard) navigator.clipboard.writeText(links.join('\n'));
      BL.toast(links.length + ' Links kopiert');
    });

    BL.$('#bl-sel-delete').addEventListener('click', function () {
      var items = selected();
      askConfirm(items.length + ' Dateien löschen?', items.length + ' Datei(en) werden endgültig gelöscht. Das kann nicht rückgängig gemacht werden.', function () {
        var paths = items.map(function (i) { return BL.rel(i.dataset.name); });
        BL.api('delete_multiple', { paths: paths }).then(function (r) {
          if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
          items.forEach(function (i) { i.remove(); });
          syncSel();
          BL.toast(items.length + ' Dateien gelöscht');
        });
      });
    });

    /* --- Datei-Aktionen ---------------------------------------------- */
    BL.$$('[data-act]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var item = btn.closest('.bl-item');
        var name = item.dataset.name;
        if (btn.dataset.act === 'copy') BL.copy(item.dataset.url);
        if (btn.dataset.act === 'open') window.open(item.dataset.url, '_blank');
        if (btn.dataset.act === 'rename') {
          var curStem = name.replace(/\.[^.]+$/, '');
          askPrompt({ title: 'Umbenennen', text: name, value: curStem, icon: 'fa-i-cursor', ok: 'Umbenennen' }, function (next) {
            var safe = BL.sanitize(next);
            BL.api('rename_image', { old_path: BL.rel(name), new_name: safe.replace(/\.[^.]+$/, '') }).then(function (r) {
              if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
              BL.toast(name + ' → ' + (r.newName || safe));
              setTimeout(function () { location.reload(); }, 600);
            });
          });
        }
        if (btn.dataset.act === 'delete') {
          askConfirm('Bild löschen?', '„' + name + '" wird endgültig gelöscht.', function () {
            BL.api('delete_image', { path: BL.rel(name) }).then(function (r) {
              if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
              item.remove();
              syncSel();
              BL.toast(name + ' gelöscht');
            });
          });
        }
      });
    });

    /* --- Bild-Vorschau (Lightbox) ------------------------------------ */
    var lb = BL.$('#bl-lightbox');
    if (lb) {
      var lbImg = BL.$('#bl-lb-img'), lbName = BL.$('#bl-lb-name'),
          lbUrl = BL.$('#bl-lb-url'), lbOpen = BL.$('#bl-lb-open'), lbCopy = BL.$('#bl-lb-copy');
      var lbCurrent = '';
      function openLb(url, name) {
        lbCurrent = url;
        lbImg.src = url;
        lbName.textContent = name;
        lbUrl.textContent = url;
        lbOpen.href = url;
        lb.hidden = false;
      }
      function closeLb() { lb.hidden = true; lbImg.src = ''; }
      BL.$$('.bl-item-thumb').forEach(function (img) {
        img.addEventListener('click', function () {
          var item = img.closest('.bl-item');
          openLb(item.dataset.url, item.dataset.name);
        });
      });
      BL.$$('[data-lb-close]', lb).forEach(function (el) { el.addEventListener('click', closeLb); });
      lbCopy.addEventListener('click', function () { BL.copy(lbCurrent); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !lb.hidden) closeLb(); });
    }

    /* --- Suche + Sortierung ------------------------------------------ */
    var search = BL.$('#bl-search');
    if (search) {
      search.addEventListener('input', function () {
        var q = search.value.trim().toLowerCase();
        var shown = 0;
        BL.$$('.bl-item').forEach(function (i) {
          var hit = !q || i.dataset.name.toLowerCase().indexOf(q) !== -1;
          i.hidden = !hit;
          if (hit) shown++;
        });
        BL.$('#bl-image-count').textContent = shown;
      });
    }

    BL.$$('.bl-sort').forEach(function (s) {
      s.addEventListener('click', function () {
        BL.$$('.bl-sort').forEach(function (o) { o.classList.remove('is-active'); });
        s.classList.add('is-active');
        var grid = BL.$('#bl-grid');
        var key = s.dataset.sort;
        BL.$$('.bl-item', grid)
          .sort(function (a, b) {
            if (key === 'name') return a.dataset.name.localeCompare(b.dataset.name);
            if (key === 'newest') return b.dataset.date.localeCompare(a.dataset.date);
            return parseInt(b.dataset.bytes, 10) - parseInt(a.dataset.bytes, 10);
          })
          .forEach(function (el) { grid.appendChild(el); });
      });
    });

    /* --- Upload-Modal (Admin: direkt, ohne Antrag) ------------------- */
    var modal = BL.$('#bl-upload-modal');
    var queue = new Queue(BL.$('#bl-upload-queue'));

    function openModal(targetLabel) {
      BL.$('#bl-upload-target').textContent = 'Ziel: ' + (targetLabel || currentDir());
      modal.hidden = false;
    }

    function currentDir() {
      var crumbs = BL.$$('#bl-crumbs a').map(function (a) { return a.textContent.trim(); });
      return BL.config.imgBase + (crumbs.length ? '/' + crumbs.join('/') : '');
    }

    BL.$('#bl-upload-open').addEventListener('click', function () { openModal(); });
    BL.$$('[data-close-modal]').forEach(function (el) {
      el.addEventListener('click', function () { modal.hidden = true; });
    });
    BL.$('.bl-modal-box', modal).addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') modal.hidden = true;
    });

    BL.dropzone(BL.$('#bl-upload-drop'), function (files) { queue.add(files); });
    BL.$('#bl-upload-clear').addEventListener('click', function () { queue.clear(); });

    /* Lädt Items hoch (je 1 Anfrage/Datei). overwrite=true erzwingt Ersetzen.
       Auflösung: { uploaded:Int, dupItems:[queue-item, …], failed:Int } */
    function doUpload(items, overwrite) {
      var jobs = items.map(function (it) {
        return BL.api('upload', {
          'files[]': it.file,
          path: BL.config.path,
          overwrite: overwrite ? '1' : ''
        }).then(function (r) { return { it: it, r: r }; })
          .catch(function () { return { it: it, r: null }; });
      });
      return Promise.all(jobs).then(function (results) {
        var out = { uploaded: 0, dupItems: [], failed: 0, stale: false };
        results.forEach(function (x) {
          var r = x.r;
          // Altes Backend erkennt keine Duplikate (Feld fehlt) -> warnen statt still überschreiben.
          if (r && r.success && !('duplicates' in r)) out.stale = true;
          if (r && r.duplicates && r.duplicates.length) {
            var d = r.duplicates[0];
            if (d && typeof d === 'object') { x.it.existingUrl = d.url; x.it.existingName = d.existing; }
            out.dupItems.push(x.it);
          }
          else if (r && r.uploaded && r.uploaded.length) out.uploaded += r.uploaded.length;
          else out.failed++;
        });
        return out;
      });
    }

    function finishUpload() {
      queue.clear();
      modal.hidden = true;
      setTimeout(function () { location.reload(); }, 700);
    }

    BL.$('#bl-upload-go').addEventListener('click', function () {
      if (!queue.items.length) { BL.toast('Noch keine Datei ausgewählt'); return; }
      var go = BL.$('#bl-upload-go');
      go.disabled = true;

      doUpload(queue.items, false).then(function (res) {
        if (res.stale) {
          go.disabled = false;
          BL.toast('⚠ Server veraltet: api.php neu hochladen + PHP-Cache leeren (Duplikat-Check inaktiv)');
          finishUpload();
          return;
        }
        if (!res.dupItems.length) {
          go.disabled = false;
          BL.toast(res.uploaded + ' Datei(en) hochgeladen'
            + (res.failed ? ' · ' + res.failed + ' fehlgeschlagen' : ''));
          finishUpload();
          return;
        }
        // Duplikate gefunden -> fragen (mit Vorschau)
        askDup(res.dupItems, function (choice) {
          if (choice === 'cancel' || choice === 'skip') {
            go.disabled = false;
            BL.toast(res.uploaded + ' hochgeladen · ' + res.dupItems.length
              + (choice === 'skip' ? ' übersprungen' : ' abgebrochen'));
            finishUpload();
            return;
          }
          // overwrite: nur die Duplikate erneut, diesmal erzwingen
          doUpload(res.dupItems, true).then(function (res2) {
            go.disabled = false;
            BL.toast((res.uploaded + res2.uploaded) + ' Datei(en) hochgeladen');
            finishUpload();
          });
        });
      }).catch(function () { go.disabled = false; BL.toast('Upload-Fehler'); });
    });

    /* --- Drop auf Grid + Ordnerkarten -------------------------------- */
    var body = BL.$('#bl-gallery');
    var note = BL.$('#bl-dropnote');

    ['dragenter', 'dragover'].forEach(function (ev) {
      body.addEventListener(ev, function (e) {
        if (!e.dataTransfer || e.dataTransfer.types.indexOf('Files') === -1) return;
        e.preventDefault();
        note.hidden = false;
        BL.$('#bl-dropnote-target').textContent = currentDir();
      });
    });
    body.addEventListener('dragleave', function (e) {
      if (e.relatedTarget && body.contains(e.relatedTarget)) return;
      note.hidden = true;
    });
    body.addEventListener('drop', function (e) {
      e.preventDefault();
      note.hidden = true;
      queue.add(e.dataTransfer.files);
      openModal();
    });

    BL.$$('.bl-folder').forEach(function (card) {
      card.addEventListener('dragover', function (e) { e.preventDefault(); card.classList.add('is-over'); });
      card.addEventListener('dragleave', function () { card.classList.remove('is-over'); });
      card.addEventListener('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        card.classList.remove('is-over');
        note.hidden = true;
        queue.add(e.dataTransfer.files);
        openModal(BL.config.imgBase + '/' + card.dataset.folder);
      });
    });

    var folderNew = BL.$('#bl-folder-new');
    if (folderNew) folderNew.addEventListener('click', function () {
      askPrompt({ title: 'Neuer Ordner', text: 'Name des neuen Ordners', icon: 'fa-folder-plus', ok: 'Anlegen' }, function (name) {
        BL.api('create_folder', { path: BL.config.path, name: name }).then(function (r) {
          if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
          BL.toast('Ordner angelegt');
          setTimeout(function () { location.reload(); }, 600);
        });
      });
    });

    /* --- Ordner löschen ---------------------------------------------- */
    BL.$$('[data-folder-del]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var card = btn.closest('.bl-folder');
        var folder = card.dataset.folder;
        var name = folder.split('/').pop();
        askConfirm('Ordner löschen?', '„' + name + '" wird mit allen enthaltenen Bildern endgültig gelöscht.', function () {
          BL.api('delete_folder', { path: folder }).then(function (r) {
            if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
            card.remove();
            BL.toast('Ordner „' + name + '" gelöscht');
          });
        });
      });
    });

    /* --- Anträge ----------------------------------------------------- */
    BL.$$('.bl-tab').forEach(function (tab) {
      tab.addEventListener('click', function () {
        BL.$$('.bl-tab').forEach(function (t) { t.classList.remove('is-active'); });
        tab.classList.add('is-active');
        var f = tab.dataset.filter;
        var shown = 0;
        BL.$$('.bl-req').forEach(function (r) {
          var hit = r.dataset.status === f;
          r.hidden = !hit;
          if (hit) shown++;
        });
        BL.$('#bl-reqs-empty').hidden = shown > 0;
      });
    });

    function setReqStatus(card, status, byText) {
      card.dataset.status = status;
      card.classList.toggle('is-open', status === 'open');
      var pill = BL.$('.bl-pill', card);
      pill.className = 'bl-pill' + (status === 'open' ? ' bl-pill--open' : status === 'approved' ? ' bl-pill--ok' : '');
      pill.textContent = status === 'open' ? 'Offen' : status === 'approved' ? 'Freigegeben' : 'Abgelehnt';
      BL.$('.bl-req-actions', card).hidden = status !== 'open';
      var done = BL.$('.bl-req-done', card);
      done.hidden = status === 'open';
      if (byText) BL.$('span', done).textContent = byText;
      card.hidden = BL.$('.bl-tab.is-active').dataset.filter !== status;
    }

    BL.$$('[data-req]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var card = btn.closest('.bl-req');
        var file = card.dataset.file;
        var act = btn.dataset.req;
        var id = card.dataset.id;
        if (act === 'approve') {
          BL.api('antrag_approve', { id: id }).then(function (r) {
            if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
            setReqStatus(card, 'approved', 'Freigegeben von ' + BL.config.me);
            BL.updateReqBadge(r.pendingCount);
            BL.toast(file + ' freigegeben → ' + (r.url || card.dataset.folder));
          });
        }
        if (act === 'reject') {
          askPrompt({ title: 'Antrag ablehnen', text: 'Grund (optional, wird gespeichert):', icon: 'fa-ban', ok: 'Ablehnen', allowEmpty: true }, function (reason) {
            BL.api('antrag_reject', { id: id, reason: reason }).then(function (r) {
              if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
              setReqStatus(card, 'rejected', 'Abgelehnt von ' + BL.config.me);
              BL.updateReqBadge(r.pendingCount);
              BL.toast(file + ' abgelehnt');
            });
          });
        }
        if (act === 'reopen') {
          BL.api('antrag_reopen', { id: id }).then(function (r) {
            if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
            setReqStatus(card, 'open', '');
            BL.updateReqBadge(r.pendingCount);
            BL.toast('Antrag wieder offen');
          });
        }
      });
    });

    /* --- Einstellungen: Zielordner ----------------------------------- */
    var foldersSave = BL.$('#bl-set-folders-save');
    if (foldersSave) {
      foldersSave.addEventListener('click', function () {
        BL.api('save_antrag_folders', {
          pending_folder: BL.$('#bl-set-pending').value,
          target_folders: BL.$('#bl-set-targets').value
        }).then(function (r) {
          BL.toast((r && r.message) || (r && r.success ? 'Gespeichert' : 'Fehler'));
        });
      });
    }

    /* --- Einstellungen: Rollen --------------------------------------- */
    BL.$$('[data-role-save]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var card = btn.closest('.bl-role');
        var key = card.dataset.role;
        var perms = BL.$$('input[data-perm]', card).filter(function (c) { return c.checked; }).map(function (c) { return c.dataset.perm; });
        BL.api('save_role', { key: key, label: BL.$('.bl-role-head b', card).textContent, color: BL.$('.bl-role-dot', card).style.background, perms: perms }).then(function (r) {
          BL.toast((r && r.message) || 'Fehler');
        });
      });
    });
    BL.$$('[data-role-del]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var card = btn.closest('.bl-role');
        askConfirm('Rolle löschen?', '„' + card.dataset.role + '" wird endgültig gelöscht.', function () {
          BL.api('delete_role', { key: card.dataset.role }).then(function (r) {
            if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
            BL.toast('Rolle gelöscht');
            setTimeout(function () { location.reload(); }, 500);
          });
        });
      });
    });
    var roleAdd = BL.$('#bl-role-add');
    if (roleAdd) {
      roleAdd.addEventListener('click', function () {
        var key = BL.$('#bl-role-key').value.trim();
        var label = BL.$('#bl-role-label').value.trim();
        if (!key || !label) { BL.toast('Key und Name nötig'); return; }
        BL.api('save_role', { key: key, label: label, color: BL.$('#bl-role-color').value, perms: ['view'] }).then(function (r) {
          if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
          BL.toast('Rolle angelegt');
          setTimeout(function () { location.reload(); }, 500);
        });
      });
    }

    /* --- Einstellungen: User + Rollen -------------------------------- */
    BL.$$('.bl-role-chips .bl-chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        var cb = BL.$('input', chip);
        setTimeout(function () { chip.classList.toggle('on', cb.checked); }, 0);
      });
    });
    var userAdd = BL.$('#bl-user-add');
    if (userAdd) {
      userAdd.addEventListener('click', function () {
        var input = BL.$('#bl-user-id');
        var id = input.value.replace(/[^0-9]/g, '');
        if (!id) { BL.toast('Bitte eine gültige Discord-ID eingeben'); return; }
        BL.api('set_user_roles', { user_id: id, roles: ['user'] }).then(function (r) {
          if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
          BL.toast('ID ' + id + ' freigeschaltet');
          input.value = '';
          setTimeout(function () { location.reload(); }, 600);
        });
      });
    }
    BL.$$('[data-user-save]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = btn.closest('.bl-row');
        var roles = BL.$$('input[data-role-chip]', row).filter(function (c) { return c.checked; }).map(function (c) { return c.dataset.roleChip; });
        BL.api('set_user_roles', { user_id: row.dataset.id, roles: roles }).then(function (r) {
          BL.toast((r && r.message) || 'Fehler');
        });
      });
    });
    BL.$$('[data-user-del]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = btn.closest('.bl-row');
        var name = BL.$('.bl-row-name b', row).textContent;
        askConfirm('User entfernen?', name + ' verliert den Zugriff.', function () {
          BL.api('remove_user', { user_id: row.dataset.id }).then(function (r) {
            if (!r || !r.success) { BL.toast((r && r.message) || 'Fehler'); return; }
            row.remove();
            BL.toast(name + ' entfernt');
          });
        });
      });
    });

    /* --- Start ------------------------------------------------------- */
    var _v = new URLSearchParams(location.search).get('view');
    showView(['requests', 'users'].indexOf(_v) >= 0 ? _v : 'gallery');
    syncSel();
  };

  document.addEventListener('DOMContentLoaded', function () {
    BL.config.me = document.body.dataset.me || 'Admin';
    BL.initLanding();
    BL.initPanel();
  });
})();
