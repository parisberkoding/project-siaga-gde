/* ==========================================================================
   SIAGA — Documentation Hub
   diagrams.js — pemuat Mermaid dari CDN + tema diagram palet Geodipa.
   Diagram ditulis sebagai teks di dalam elemen <pre class="mermaid">…</pre>
   sehingga kode sumber diagram ikut terversioning bersama dokumentasi.
   ========================================================================== */
(function () {
  'use strict';

  var CDN = 'https://cdn.jsdelivr.net/npm/mermaid@10.9.4/dist/mermaid.min.js';
  var FALLBACK = 'https://unpkg.com/mermaid@10.9.4/dist/mermaid.min.js';

  /* Tema diagram: turunan palet Geodipa, latar putih/terang. */
  var THEME = {
    fontFamily: "'Poppins', ui-sans-serif, system-ui, sans-serif",
    fontSize: '14px',
    background: '#FFFFFF',
    primaryColor: '#EAF1FA',
    primaryTextColor: '#16202E',
    primaryBorderColor: '#0B50A1',
    secondaryColor: '#F2F9E8',
    secondaryTextColor: '#16202E',
    secondaryBorderColor: '#8DC63F',
    tertiaryColor: '#F8FAFC',
    tertiaryTextColor: '#33465C',
    tertiaryBorderColor: '#E4E9F0',
    lineColor: '#64748B',
    textColor: '#16202E',
    mainBkg: '#EAF1FA',
    nodeBorder: '#0B50A1',
    clusterBkg: '#F8FAFC',
    clusterBorder: '#C7DCF5',
    titleColor: '#0B50A1',
    edgeLabelBackground: '#FFFFFF',
    actorBkg: '#EAF1FA',
    actorBorder: '#0B50A1',
    actorTextColor: '#16202E',
    actorLineColor: '#94A3B8',
    signalColor: '#33465C',
    signalTextColor: '#16202E',
    labelBoxBkgColor: '#F2F9E8',
    labelBoxBorderColor: '#8DC63F',
    labelTextColor: '#3F6120',
    loopTextColor: '#33465C',
    noteBkgColor: '#FFFDE8',
    noteBorderColor: '#D8CE3A',
    noteTextColor: '#7A6A00',
    activationBkgColor: '#F2F9E8',
    activationBorderColor: '#8DC63F',
    sequenceNumberColor: '#FFFFFF',
    classText: '#16202E',
    attributeBackgroundColorOdd: '#FFFFFF',
    attributeBackgroundColorEven: '#F8FAFC',
    /* Palet pie/ lainnya */
    pie1: '#8DC63F', pie2: '#0B50A1', pie3: '#ED1B2F', pie4: '#FFF56B',
    pie5: '#7AB832', pie6: '#2E7BD1', pie7: '#F7C6CC', pie8: '#D8CE3A'
  };

  function cfg() {
    return {
      startOnLoad: false,
      theme: 'base',
      darkMode: false,
      securityLevel: 'loose',
      themeVariables: THEME,
      flowchart: {
        useMaxWidth: true, htmlLabels: true, curve: 'basis',
        padding: 16, nodeSpacing: 46, rankSpacing: 58, diagramPadding: 12
      },
      sequence: {
        useMaxWidth: true, wrap: true, diagramMarginX: 26, diagramMarginY: 20,
        actorMargin: 74, width: 158, height: 52, boxMargin: 12,
        messageMargin: 42, mirrorActors: false, showSequenceNumbers: false
      },
      er: { useMaxWidth: true, entityPadding: 18, fontSize: 13, minEntityWidth: 96 },
      class: { useMaxWidth: true, padding: 16 },
      gantt: { useMaxWidth: true },
      state: { useMaxWidth: true }
    };
  }

  function fail(reason) {
    document.querySelectorAll('.mermaid').forEach(function (el) {
      if (el.dataset.rendered === 'ok') return;
      var box = el.closest('.fig') || el.parentElement;
      var msg = document.createElement('div');
      msg.className = 'callout callout--warn';
      msg.style.margin = '0';
      msg.innerHTML = '<span class="callout__icon">&#9888;</span><div class="callout__body">' +
        '<strong>Diagram belum dapat dirender</strong>' +
        'Perlu koneksi internet untuk memuat pustaka Mermaid dari CDN. ' +
        'Kode sumber diagram tetap tersedia pada blok <em>Kode Diagram</em> di bawah gambar.' +
        '<br><small style="opacity:.8">' + (reason || '') + '</small></div>';
      box.appendChild(msg);
    });
  }

  /* Diagram dengan rasio sangat lebar dirender pada ukuran terbaca dan
     dibiarkan dapat digeser mendatar, agar tidak mengecil tak terbaca. */
  function polish() {
    document.querySelectorAll('.fig .mermaid svg').forEach(function (svg) {
      var vb = (svg.getAttribute('viewBox') || '').split(/[\s,]+/).map(Number);
      if (vb.length < 4 || !vb[2] || !vb[3]) return;
      var fig = svg.closest('.fig');
      if (!fig || fig.dataset.wide) return;
      if ((vb[2] / vb[3]) < 2) return;
      fig.dataset.wide = '1';
      fig.classList.add('fig--wide');
      var w = Math.min(Math.max(1800, vb[2] * 0.8), 2600);
      svg.style.maxWidth = 'none';
      svg.style.width = w + 'px';
      var hint = document.createElement('div');
      hint.className = 'fig-wide-hint';
      hint.innerHTML = '<i class="bi bi-arrows-move"></i> Diagram lebar &mdash; geser ke samping atau klik diagram untuk memperbesar';
      fig.appendChild(hint);
    });
  }

  function render() {
    try {
      window.mermaid.initialize(cfg());
      var nodes = document.querySelectorAll('.mermaid');
      if (!nodes.length) return;
      window.mermaid.run({ querySelector: '.mermaid', suppressErrors: true })
        .then(function () {
          nodes.forEach(function (n) {
            if (n.querySelector('svg')) n.dataset.rendered = 'ok';
          });
          polish();
        })
        .catch(function (e) { fail(e && e.message); });
    } catch (e) { fail(e && e.message); }
  }

  function load(src, next, onErr) {
    var s = document.createElement('script');
    s.src = src; s.async = true; s.onload = next; s.onerror = onErr;
    document.head.appendChild(s);
  }

  function boot() {
    if (!document.querySelector('.mermaid')) return;
    if (window.mermaid) return render();
    load(CDN, render, function () {
      load(FALLBACK, render, function () { fail('CDN Mermaid tidak dapat diakses.'); });
    });
  }

  window.SIAGA_DIAGRAMS = { render: render, theme: THEME };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else { boot(); }
})();
