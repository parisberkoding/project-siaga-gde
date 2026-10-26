/* ==========================================================================
   SIAGA — Documentation Hub
   docs.js — interaksi umum: navigasi sidebar, pencarian, tab, salin kode,
   tooltip langkah tutorial, zoom diagram, dan scrollspy.
   ========================================================================== */
(function () {
  'use strict';

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var base = (document.body && document.body.getAttribute('data-base')) || '';

  /* ------------------------------------------------------------------ *
   * 1. INDEKS PENCARIAN (dipakai kotak pencarian di topbar)
   * ------------------------------------------------------------------ */
  var INDEX = [
    { t: 'Beranda Dokumentasi SIAGA', p: 'index.html', d: 'Pilih Software Documentation atau Alur Tutorial', s: 'Landing' },

    { t: 'Software Documentation — Ikhtisar', p: 'software-documentation/index.html', d: 'Halaman pembuka SDD, daftar isi, ringkasan eksekutif', s: 'SDD' },
    { t: '1. Ruang Lingkup & Visi Produk', p: 'software-documentation/01-ruang-lingkup.html', d: 'Scope statement, problem statement, stakeholder, aktor', s: 'SDD · Fase 1' },
    { t: '1.1 Scope Statement SIAGA', p: 'software-documentation/01-ruang-lingkup.html#scope', d: 'Nama sistem, tujuan, audiens, batasan', s: 'SDD · Fase 1' },
    { t: '1.2 Problem Statement', p: 'software-documentation/01-ruang-lingkup.html#masalah', d: 'Latar belakang masalah administrasi GA', s: 'SDD · Fase 1' },
    { t: '1.3 Stakeholder & User Identification', p: 'software-documentation/01-ruang-lingkup.html#stakeholder', d: 'Primary, secondary, indirect stakeholder', s: 'SDD · Fase 1' },
    { t: '1.4 Aktor Sistem', p: 'software-documentation/01-ruang-lingkup.html#aktor', d: 'Daftar aktor & peran pada sistem', s: 'SDD · Fase 1' },
    { t: '2. Arsitektur & Teknologi', p: 'software-documentation/02-arsitektur-teknologi.html', d: 'Stack Laravel, modul, struktur direktori, deployment', s: 'SDD · Fase 1' },
    { t: '3. Model Use Case', p: 'software-documentation/03-model-use-case.html', d: 'Use case diagram, daftar kandidat use case, relasi include/extend', s: 'SDD · Fase 2-3' },
    { t: '3.1 Diagram Use Case Keseluruhan', p: 'software-documentation/03-model-use-case.html#diagram', d: 'Use case diagram tingkat sistem', s: 'SDD · Fase 2' },
    { t: '3.2 Katalog Use Case', p: 'software-documentation/03-model-use-case.html#katalog', d: 'Tabel use case, aktor, tujuan, prioritas', s: 'SDD · Fase 2' },
    { t: '3.3 Relasi include & extend', p: 'software-documentation/03-model-use-case.html#relasi', d: 'Pemfaktoran perilaku bersama & opsional', s: 'SDD · Fase 3' },
    { t: '4. Spesifikasi Use Case', p: 'software-documentation/04-spesifikasi-use-case.html', d: 'Precondition, postcondition, main/alternative/exception flow', s: 'SDD · Fase 4' },
    { t: '5. Desain Perilaku (Activity & Sequence)', p: 'software-documentation/05-desain-perilaku.html', d: 'Activity diagram & sequence diagram', s: 'SDD · Fase 5' },
    { t: '6. Desain Struktur (Class & ERD)', p: 'software-documentation/06-desain-struktur.html', d: 'Class diagram, ERD, kamus data', s: 'SDD · Fase 5' },
    { t: '7. Validasi Logika (Decision Table)', p: 'software-documentation/07-validasi-logika.html', d: 'Decision table & matriks keputusan', s: 'SDD · Fase 6' },
    { t: '8. Pengujian & Test Case', p: 'software-documentation/08-pengujian.html', d: 'Test case, coverage, prioritas risiko', s: 'SDD · Fase 7' },
    { t: '9. Traceability Matrix', p: 'software-documentation/09-traceability.html', d: 'Goal → Use Case → Diagram → Test Case, matriks RBAC', s: 'SDD · Fase 7' },
    { t: '10. Glosarium', p: 'software-documentation/10-glosarium.html', d: 'Istilah domain, alias, kategori', s: 'SDD · Fase 8' },
    { t: '11. PlantUML & Riwayat Perubahan', p: 'software-documentation/11-plantuml-changelog.html', d: 'Laporan PlantUML, changelog rilis', s: 'SDD · Fase 8' },

    { t: 'Alur Tutorial — Ikhtisar', p: 'alur-tutorial/index.html', d: 'Daftar 6 alur penggunaan SIAGA', s: 'Tutorial' },
    { t: 'Alur 1 — Permohonan BBM', p: 'alur-tutorial/01-permohonan-bbm.html', d: 'Requester mengajukan, admin approve, voucher, bukti transaksi', s: 'Tutorial' },
    { t: 'Alur 2 — Pengajuan SPD', p: 'alur-tutorial/02-spd.html', d: 'Surat tugas, LPJ, perpanjangan, validasi', s: 'Tutorial' },
    { t: 'Alur 3 — Cetak Surat & Laporan', p: 'alur-tutorial/03-cetak-surat.html', d: 'Cetak SPD, LPJ, bundel PDF, QR signature', s: 'Tutorial' },
    { t: 'Alur 4 — Dispatcher', p: 'alur-tutorial/04-dispatcher.html', d: 'Assign PIC, jadwal, monitoring, ekspor checklist', s: 'Tutorial' },
    { t: 'Alur 5 — Makan Siang (Lunch)', p: 'alur-tutorial/05-makan-siang.html', d: 'Booking mandiri, absen QR, forecast, distribusi vendor', s: 'Tutorial' },
    { t: 'Alur 6 — Routine Checking', p: 'alur-tutorial/06-checking.html', d: 'Daily, weekly, monthly, body checking + VPR', s: 'Tutorial' }
  ];

  function initSearch() {
    var input = $('#docSearch');
    var box = $('#docSearchResults');
    if (!input || !box) return;

    function render(q) {
      var key = (q || '').trim().toLowerCase();
      if (key.length < 2) { box.classList.remove('is-open'); box.innerHTML = ''; return; }
      var hits = INDEX.filter(function (i) {
        return (i.t + ' ' + i.d + ' ' + i.s).toLowerCase().indexOf(key) > -1;
      }).slice(0, 12);
      if (!hits.length) {
        box.innerHTML = '<div class="empty">Tidak ada hasil untuk &ldquo;' + escapeHtml(q) + '&rdquo;.</div>';
      } else {
        box.innerHTML = hits.map(function (i) {
          return '<a href="' + base + i.p + '"><b>' + escapeHtml(i.t) + '</b><small>' + escapeHtml(i.s) + ' &middot; ' + escapeHtml(i.d) + '</small></a>';
        }).join('');
      }
      box.classList.add('is-open');
    }

    input.addEventListener('input', function () { render(input.value); });
    input.addEventListener('focus', function () { render(input.value); });
    document.addEventListener('click', function (e) {
      if (!box.contains(e.target) && e.target !== input) box.classList.remove('is-open');
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') box.classList.remove('is-open');
      if (e.key === '/' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault(); input.focus();
      }
    });
  }

  function escapeHtml(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* ------------------------------------------------------------------ *
   * 2. SIDEBAR MOBILE
   * ------------------------------------------------------------------ */
  function initSidebar() {
    var sb = $('#docSidebar'), scrim = $('#docScrim'), btn = $('#docMenuBtn');
    if (!sb || !btn) return;
    function toggle(force) {
      var open = force != null ? force : !sb.classList.contains('is-open');
      sb.classList.toggle('is-open', open);
      if (scrim) scrim.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    btn.addEventListener('click', function () { toggle(); });
    if (scrim) scrim.addEventListener('click', function () { toggle(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggle(false); });
  }

  /* ------------------------------------------------------------------ *
   * 3. TAB
   * ------------------------------------------------------------------ */
  function initTabs() {
    $$('[data-tabs]').forEach(function (group) {
      var btns = $$('.tabs__btn', group);
      var panels = $$('.tabs__panel', group);
      btns.forEach(function (btn, idx) {
        btn.addEventListener('click', function () {
          btns.forEach(function (b) { b.classList.remove('is-active'); b.setAttribute('aria-selected', 'false'); });
          panels.forEach(function (p) { p.classList.remove('is-active'); });
          btn.classList.add('is-active'); btn.setAttribute('aria-selected', 'true');
          if (panels[idx]) panels[idx].classList.add('is-active');
          if (window.SIAGA_DIAGRAMS) setTimeout(function () { window.SIAGA_DIAGRAMS.render(); }, 40);
        });
      });
    });
  }

  /* ------------------------------------------------------------------ *
   * 4. SALIN KODE
   * ------------------------------------------------------------------ */
  function initCopy() {
    $$('[data-copy]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.getAttribute('data-copy');
        var src = document.getElementById(id);
        if (!src) return;
        var text = src.innerText;
        var done = function () {
          var old = btn.innerHTML;
          btn.innerHTML = '&#10003; Tersalin';
          setTimeout(function () { btn.innerHTML = old; }, 1600);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text, done); });
        } else { fallbackCopy(text, done); }
      });
    });
  }
  function fallbackCopy(text, done) {
    var ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); done(); } catch (e) { /* noop */ }
    document.body.removeChild(ta);
  }

  /* ------------------------------------------------------------------ *
   * 5. TOOLTIP LANGKAH TUTORIAL (pin <-> daftar langkah)
   * ------------------------------------------------------------------ */
  function initPins() {
    var pins = $$('.pin');
    if (!pins.length) return;

    function activate(step, scroll) {
      pins.forEach(function (p) {
        var on = p.getAttribute('data-step') === String(step);
        p.classList.toggle('is-open', on);
        if (p.__target) p.__target.classList.toggle('is-hot', on);
      });
      $$('.tut__steps li').forEach(function (li) {
        li.classList.toggle('is-active', li.getAttribute('data-step') === String(step));
      });
      $$('.tut__progress i').forEach(function (bar, idx) {
        bar.classList.toggle('on', idx < Number(step));
      });
      if (scroll) {
        var target = pins.filter(function (p) { return p.getAttribute('data-step') === String(step); })[0];
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }

    pins.forEach(function (pin) {
      var step = pin.getAttribute('data-step');
      pin.setAttribute('role', 'button');
      pin.setAttribute('tabindex', '0');
      if (!pin.getAttribute('aria-label')) pin.setAttribute('aria-label', 'Langkah ' + step);
      pin.addEventListener('click', function (e) { e.preventDefault(); activate(step, false); });
      pin.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(step, false); }
      });
    });

    $$('.tut__steps li[data-step]').forEach(function (li) {
      li.setAttribute('role', 'button');
      li.setAttribute('tabindex', '0');
      var go = function () {
        var step = li.getAttribute('data-step');
        activate(step, true);
        var pin = pins.filter(function (p) { return p.getAttribute('data-step') === String(step); })[0];
        if (pin && pin.closest('.clip')) pin.closest('.clip').scrollIntoView({ behavior: 'smooth', block: 'center' });
      };
      li.addEventListener('click', go);
      li.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
      });
    });

    /* Tombol sorot semua langkah */
    var allBtn = $('#pinShowAll');
    if (allBtn) {
      allBtn.addEventListener('click', function () {
        var open = allBtn.getAttribute('data-open') !== '1';
        allBtn.setAttribute('data-open', open ? '1' : '0');
        allBtn.innerHTML = open ? '<i class="bi bi-eye-slash"></i> Sembunyikan semua tooltip' : '<i class="bi bi-eye"></i> Tampilkan semua tooltip';
        $$('.pin__tip').forEach(function (t) {
          t.style.opacity = open ? '1' : '';
          t.style.visibility = open ? 'visible' : '';
        });
      });
    }
  }

  /* ------------------------------------------------------------------ *
   * 5b. PENEMPATAN PIN OTOMATIS (pin dipasangkan ke elemen target)
   *     Markup: <button class="pin" data-step="1" data-annot="1" data-pin-pos="right">
   *     Target : elemen mana pun di dalam .clip dengan data-annot="1"
   * ------------------------------------------------------------------ */
  function initAnchors() {
    var clips = $$('.clip[data-annot-scope], .clip');
    if (!clips.length) return;

    function place() {
      clips.forEach(function (clip) {
        var pins = $$('.pin[data-annot]', clip);
        if (!pins.length) return;
        var box = clip.getBoundingClientRect();
        pins.forEach(function (pin) {
          var key = pin.getAttribute('data-annot');
          var target = clip.querySelector('[data-annot="' + key + '"]');
          if (!target) return;
          var t = target.getBoundingClientRect();
          var pos = pin.getAttribute('data-pin-pos') || 'right';
          var OFF_X = 15, OFF_Y = 17;
          var x, y;
          if (pos === 'left') { x = t.left - box.left - OFF_X; }
          else if (pos === 'top') { x = t.left - box.left + t.width / 2; y = t.top - box.top - OFF_Y; }
          else if (pos === 'bottom') { x = t.left - box.left + t.width / 2; y = t.bottom - box.top + OFF_Y; }
          else if (pos === 'inside') { x = t.right - box.left - OFF_X; y = t.top - box.top + t.height / 2; }
          else { x = t.right - box.left + OFF_X; }
          if (y == null) y = t.top - box.top + t.height / 2;

          /* Jaga agar pin tetap berada di dalam area clip */
          x = Math.max(16, Math.min(box.width - 16, x));
          y = Math.max(16, Math.min(box.height - 16, y));
          pin.style.left = x + 'px';
          pin.style.top = y + 'px';
          /* Geser bubble tooltip agar tidak keluar dari kolom konten */
          pin.classList.toggle('pin--left', x > box.width * 0.62);
          pin.classList.toggle('pin--right', x < box.width * 0.24);
          pin.setAttribute('data-placed', '1');
        });
      });
    }

    place();
    window.addEventListener('resize', debounce(place, 150));
    window.addEventListener('load', place);
    setTimeout(place, 400);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(place);
  }

  function debounce(fn, wait) {
    var t;
    return function () {
      clearTimeout(t);
      var args = arguments, self = this;
      t = setTimeout(function () { fn.apply(self, args); }, wait);
    };
  }

  /* Sorot elemen target saat pin diaktifkan */
  function initHighlight() {
    $$('.pin[data-annot]').forEach(function (pin) {
      var clip = pin.closest('.clip');
      if (!clip) return;
      var key = pin.getAttribute('data-annot');
      var target = clip.querySelector('[data-annot="' + key + '"]');
      if (!target) return;
      var on = function () { target.classList.add('is-hot'); };
      var off = function () { target.classList.remove('is-hot'); };
      pin.addEventListener('mouseenter', on);
      pin.addEventListener('focus', on);
      pin.addEventListener('mouseleave', function () {
        if (!pin.classList.contains('is-open')) off();
      });
      pin.addEventListener('blur', function () {
        if (!pin.classList.contains('is-open')) off();
      });
      pin.addEventListener('click', function () { on(); });
      pin.__target = target;
      pin.__off = off;
    });
  }

  /* ------------------------------------------------------------------ *
   * 6. ZOOM DIAGRAM
   * ------------------------------------------------------------------ */
  function initZoom() {
    var figs = $$('.fig .mermaid');
    if (!figs.length) return;
    var overlay = document.createElement('div');
    overlay.className = 'zoom-overlay';
    overlay.innerHTML = '<button type="button" class="zoom-overlay__close" aria-label="Tutup">&times;</button><div class="zoom-overlay__box"></div>';
    document.body.appendChild(overlay);
    var box = $('.zoom-overlay__box', overlay);

    function close() { overlay.classList.remove('is-open'); box.innerHTML = ''; document.body.style.overflow = ''; }

    figs.forEach(function (fig) {
      fig.style.cursor = 'zoom-in';
      fig.title = 'Klik untuk memperbesar diagram';
      fig.addEventListener('click', function () {
        var svg = fig.querySelector('svg');
        if (!svg) return;
        box.innerHTML = svg.outerHTML;
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
      });
    });
    overlay.addEventListener('click', function (e) { if (e.target === overlay || e.target.classList.contains('zoom-overlay__close')) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  }

  /* ------------------------------------------------------------------ *
   * 7. SCROLLSPY SIDEBAR
   * ------------------------------------------------------------------ */
  function initSpy() {
    var links = $$('.sidebar .nav-link[href^="#"]');
    if (!links.length || !('IntersectionObserver' in window)) return;
    var map = {};
    var targets = [];
    links.forEach(function (a) {
      var id = a.getAttribute('href').slice(1);
      var el = document.getElementById(id);
      if (el) { map[id] = a; targets.push(el); }
    });
    if (!targets.length) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        links.forEach(function (l) { l.classList.remove('is-active'); });
        var a = map[en.target.id];
        if (a) a.classList.add('is-active');
      });
    }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });
    targets.forEach(function (t) { io.observe(t); });
  }

  /* ------------------------------------------------------------------ *
   * 8. TOMBOL CETAK
   * ------------------------------------------------------------------ */
  function initPrint() {
    $$('[data-print]').forEach(function (b) {
      b.addEventListener('click', function (e) { e.preventDefault(); window.print(); });
    });
  }

  /* ------------------------------------------------------------------ *
   * 9. TAHUN FOOTER
   * ------------------------------------------------------------------ */
  function initYear() {
    $$('[data-year]').forEach(function (el) { el.textContent = new Date().getFullYear(); });
  }

  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  ready(function () {
    initSearch(); initSidebar(); initTabs(); initCopy();
    initAnchors(); initHighlight(); initPins(); initZoom(); initSpy(); initPrint(); initYear();
  });
})();
