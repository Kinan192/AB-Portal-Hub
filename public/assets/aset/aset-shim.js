/*
 * Aset IT: what the module scripts expect from the design's store.js and site.js, for the portal pages.
 * window.RS: the signed-in account (from <body data-user-name>); no image storage, so attachments keep
 * only their name, type and size. window.rsRenderIcons: Lucide icons (icons.js) for every [data-i],
 * also for markup added later. window.siteToast(title, desc, variant): the module's toast.
 */
(function () {
  'use strict';

  var name = (document.body && document.body.getAttribute('data-user-name')) || 'Admin';
  window.RS = window.RS || {
    currentUser: function () { return { name: name }; },
    storeImage: function () { return Promise.reject(new Error('Penyimpanan gambar tidak tersedia.')); }
  };

  var ICONS = window.RS_ICONS || {};
  function svgFor(key) {
    var inner = ICONS[key];
    return inner ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + inner + '</svg>' : null;
  }
  function renderIcon(el) {
    var key = el.getAttribute('data-i');
    if (el.getAttribute('data-i-done') === key) return;
    var svg = key && svgFor(key);
    if (!svg) return;
    el.innerHTML = svg;
    el.classList.add('rs-ic');
    el.setAttribute('aria-hidden', 'true');
    el.setAttribute('data-i-done', key);
  }
  function renderIcons(root) {
    if (!root || root.nodeType !== 1) return;
    if (root.hasAttribute('data-i')) renderIcon(root);
    root.querySelectorAll('[data-i]').forEach(renderIcon);
  }
  window.rsRenderIcons = renderIcons;
  renderIcons(document.body);
  new MutationObserver(function (muts) {
    muts.forEach(function (m) {
      if (m.type === 'attributes') { renderIcon(m.target); return; }
      m.addedNodes.forEach(function (n) { if (n.nodeType === 1) renderIcons(n); });
    });
  }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-i'] });

  var TOAST = {
    success: { color: 'var(--rs-ok)', bg: 'var(--rs-ok-bg)', icon: 'circle-check' },
    info: { color: 'var(--rs-warn)', bg: 'var(--rs-warn-bg)', icon: 'info' },
    error: { color: 'var(--rs-err)', bg: 'var(--rs-err-bg)', icon: 'circle-alert' }
  };
  var timer = null;
  function ensureToast() {
    var t = document.getElementById('site-toast');
    if (t) return t;
    t = document.createElement('div');
    t.id = 'site-toast';
    t.className = 'rs-toast';
    t.setAttribute('role', 'status');
    t.setAttribute('aria-live', 'polite');
    t.innerHTML = '<div class="as-toast"><span id="site-toast-iconbg" class="as-toast__icon"><span id="site-toast-icon" data-i="circle-check"></span></span>' +
      '<div class="as-toast__body"><h4 id="site-toast-title"></h4><p id="site-toast-desc"></p><div class="rs-toast-time" id="site-toast-time"></div></div>' +
      '<button type="button" id="site-toast-close" aria-label="Tutup"><span data-i="x"></span></button></div>';
    document.body.appendChild(t);
    renderIcons(t);
    document.getElementById('site-toast-close').addEventListener('click', function () { t.classList.remove('is-show'); });
    return t;
  }
  window.siteToast = function (title, desc, variant) {
    var t = ensureToast();
    var v = TOAST[variant] || TOAST.success;
    t.firstChild.style.setProperty('--rs-toast', v.color);
    var bg = document.getElementById('site-toast-iconbg');
    bg.style.background = v.bg;
    bg.style.color = v.color;
    document.getElementById('site-toast-icon').setAttribute('data-i', v.icon);
    document.getElementById('site-toast-title').textContent = title || '';
    var d = document.getElementById('site-toast-desc');
    d.textContent = desc || '';
    d.hidden = !desc;
    var now = new Date();
    document.getElementById('site-toast-time').textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ' WIB';
    t.classList.add('is-show');
    clearTimeout(timer);
    timer = setTimeout(function () { t.classList.remove('is-show'); }, 4500);
  };
})();
