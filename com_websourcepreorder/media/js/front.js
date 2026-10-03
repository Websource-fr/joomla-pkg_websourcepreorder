/* Websource Précommandes — compte à rebours, bouton « Précommander », badges, formulaire d'alerte */
(function () {
  'use strict';
  var opts = (window.Joomla && Joomla.getOptions) ? (Joomla.getOptions('com_websourcepreorder') || {}) : {};
  var T = opts.i18n || {};
  var offset = opts.now ? (opts.now * 1000 - Date.now()) : 0; /* décalage horloge serveur / navigateur */

  function nowSec() { return Math.floor((Date.now() + offset) / 1000); }
  function unitLabel(key, n) { return n === 1 ? (T[key + '1'] || key) : (T[key] || key); }

  /* Décompose le temps restant selon le mode (auto, d, dh, dhm, dhms). */
  function parts(sec, mode) {
    var d = Math.floor(sec / 86400), h = Math.floor(sec % 86400 / 3600),
        m = Math.floor(sec % 3600 / 60), s = sec % 60, out = [];
    if (mode === 'auto') { mode = sec >= 2 * 86400 ? 'dh' : (sec >= 3600 ? 'hm' : 'ms'); }
    if (mode === 'd') { out.push(['d', Math.max(1, Math.ceil(sec / 86400))]); return out; }
    if (mode.indexOf('d') > -1) { out.push(['d', d]); }
    if (mode.indexOf('h') > -1) { out.push(['h', mode.indexOf('d') > -1 ? h : d * 24 + h]); }
    if (mode.indexOf('m') > -1) { out.push(['m', m]); }
    if (mode.indexOf('s') > -1) { out.push(['s', s]); }
    return out;
  }

  function pad(n, k) { return (k === 'd' || n > 99) ? String(n) : (n < 10 ? '0' + n : String(n)); }

  function renderCount(el) {
    var ts = parseInt(el.getAttribute('data-ts'), 10), mode = el.getAttribute('data-mode') || 'auto';
    var left = ts - nowSec();
    if (left <= 0) { el.innerHTML = '<span class="wspo-count__done">' + (T.done || '') + '</span>'; return; }
    var html = '', p = parts(left, mode);
    for (var i = 0; i < p.length; i++) {
      html += '<span class="wspo-count__unit"><b>' + pad(p[i][1], p[i][0]) + '</b><i>' + unitLabel(p[i][0], p[i][1]) + '</i></span>';
    }
    el.innerHTML = html;
  }

  /* Texte court dans le bandeau : « dans 12 j », « dans 5 h », « dans 20 min ». */
  function renderTickerLeft(el) {
    var left = parseInt(el.getAttribute('data-ts'), 10) - nowSec();
    if (left <= 0) { el.textContent = '· ' + (T.done || ''); return; }
    var d = Math.floor(left / 86400), h = Math.floor(left / 3600), m = Math.max(1, Math.floor(left / 60));
    el.textContent = '· ' + (d >= 1 ? d + ' ' + unitLabel('d', d) : (h >= 1 ? h + ' ' + unitLabel('h', h) : m + ' ' + unitLabel('m', m)));
  }

  function tick() {
    document.querySelectorAll('.wspo-count[data-ts]').forEach(renderCount);
    document.querySelectorAll('.wspo-ticker__left[data-ts]').forEach(renderTickerLeft);
  }

  /* Identifiant produit déduit d'un lien HikaShop (SEF « /product/12-nom » ou « cid=12 »). */
  function pidFromHref(href) {
    var m = (href || '').match(/\/product\/(?:updatecart\/add-\d+\/cid-)?(\d+)(?:-|\/|$)/) || (href || '').match(/[?&](?:cid|product_id)(?:\[\])?=(\d+)/);
    return m ? m[1] : '';
  }

  /* Boutons d'achat renommés : seulement pour les produits en précommande. */
  function relabelButtons() {
    var map = opts.map || {}, btn = opts.btn || '';
    if (!btn) { return; }
    document.querySelectorAll('[data-addtocart]').forEach(function (b) {
      if (b.getAttribute('data-wspo') === '1') { return; }
      var pid = b.getAttribute('data-addtocart');
      if (!pid || !Object.prototype.hasOwnProperty.call(map, pid)) { return; }
      var label = map[pid] || btn;
      b.setAttribute('data-wspo', '1');
      if (b.tagName === 'INPUT') { b.value = label; return; }
      var spans = b.querySelectorAll('span');
      if (spans.length) { spans[spans.length - 1].textContent = label; } else { b.textContent = label; }
    });
  }

  /* Badge « Précommande » sur les vignettes des listes de produits. */
  function badgeLists() {
    var map = opts.map || {}, flag = opts.flag || '';
    if (!flag) { return; }
    document.querySelectorAll('.hikashop_product').forEach(function (c) {
      if (c.getAttribute('data-wspo-badged') === '1') { return; }
      var a = c.querySelector('a[href*="/product/"], a[href*="cid="], a[href*="product_id="]');
      var pid = a ? pidFromHref(a.getAttribute('href')) : '';
      if (!pid) { var b = c.querySelector('[data-addtocart]'); pid = b ? b.getAttribute('data-addtocart') : ''; }
      if (!pid || !Object.prototype.hasOwnProperty.call(map, pid)) { return; }
      c.setAttribute('data-wspo-badged', '1');
      var target = c.querySelector('.hikashop_product_image') || c.querySelector('.hikashop_subcontainer') || c;
      var span = document.createElement('span');
      span.className = 'wspo-badge wspo-badge--list';
      span.textContent = flag;
      target.classList.add('wspo-has-badge');
      target.insertBefore(span, target.firstChild);
    });
  }

  /* Place le bloc précommande près du bouton d'achat de la fiche produit. */
  function placeSlot() {
    var slot = document.querySelector('[data-wspo-slot]');
    if (!slot || slot.getAttribute('data-wspo-moved')) { return; }
    var target = document.querySelector('#hikashop_product_right_part .hikashop_product_price_main, #hikashop_product_right_part form[name^="hikashop_product_form"], #hikashop_product_right_part');
    if (target && target.parentNode && !slot.contains(target)) {
      var anchor = document.querySelector('#hikashop_product_right_part form[name^="hikashop_product_form"]');
      if (anchor && anchor.parentNode) { anchor.parentNode.insertBefore(slot, anchor); }
      else { target.appendChild(slot); }
    }
    slot.setAttribute('data-wspo-moved', '1');
  }

  /* Notice panier : en tête de la page. */
  function placeCartNotice() {
    var n = document.querySelector('.wspo-cart-notice');
    if (!n || n.getAttribute('data-wspo-moved')) { return; }
    var t = document.querySelector('#hikashop_checkout_cart, .hikashop_cart_product_listing, [id^="hikashop_cart"], .hikashop_checkout_page');
    if (t && t.parentNode) { t.parentNode.insertBefore(n, t); }
    n.setAttribute('data-wspo-moved', '1');
  }

  /* Formulaire d'alerte : envoi sans rechargement, repli classique si le JS échoue. */
  function bindForms() {
    document.querySelectorAll('form[data-wspo-sub]').forEach(function (form) {
      if (form.getAttribute('data-wspo-bound')) { return; }
      form.setAttribute('data-wspo-bound', '1');
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var msg = form.querySelector('.wspo-sub__msg');
        if (!form.checkValidity()) { form.reportValidity(); return; }
        msg.className = 'wspo-sub__msg'; msg.textContent = T.sending || '…';
        var url = form.getAttribute('action') + '&format=json';
        fetch(url, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            var ok = j && j.success !== false;
            msg.className = 'wspo-sub__msg ' + (ok ? 'is-ok' : 'is-error');
            msg.textContent = (j && (j.message || (j.data && j.data.message))) || (ok ? '' : (T.error || ''));
            if (ok) { form.querySelectorAll('input[type=email]').forEach(function (i) { i.value = ''; }); }
          })
          .catch(function () { msg.className = 'wspo-sub__msg is-error'; msg.textContent = T.error || ''; });
      });
    });
  }

  function init() {
    placeSlot(); placeCartNotice(); relabelButtons(); badgeLists(); bindForms(); tick();
    setInterval(tick, 1000);
    /* HikaShop recharge parfois des blocs en AJAX (listes, panier) : on ré-applique. */
    var scheduled = false;
    new MutationObserver(function (muts) {
      var relevant = muts.some(function (m) { return !(m.target.closest && m.target.closest('.wspo-count, .wspo-ticker__left')); });
      if (scheduled || !relevant) { return; }
      scheduled = true;
      setTimeout(function () { scheduled = false; relabelButtons(); badgeLists(); placeCartNotice(); bindForms(); }, 150);
    }).observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
