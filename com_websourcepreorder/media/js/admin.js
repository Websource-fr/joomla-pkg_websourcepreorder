/* Websource Précommandes — administration : sélecteur de produit et sélection des e-mails */
(function () {
  'use strict';
  function ready(fn) { if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }

  ready(function () {
    /* Sélecteur de produit HikaShop */
    document.querySelectorAll('.wspo-pp').forEach(function (box) {
      var q = box.querySelector('.wspo-pp__q'), hid = box.querySelector('input[type=hidden]'),
          res = box.querySelector('.wspo-pp__results'), url = box.getAttribute('data-url'), t;
      q.addEventListener('input', function () {
        clearTimeout(t);
        var term = q.value.trim();
        if (term.length < 2) { res.hidden = true; return; }
        t = setTimeout(function () {
          fetch(url + '&q=' + encodeURIComponent(term), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (rows) {
              res.innerHTML = '';
              rows.forEach(function (r) {
                var b = document.createElement('button');
                b.type = 'button'; b.className = 'list-group-item list-group-item-action';
                b.textContent = '#' + r.id + ' ' + r.name;
                b.addEventListener('click', function () { hid.value = r.id; q.value = '#' + r.id + ' ' + r.name; res.hidden = true; });
                res.appendChild(b);
              });
              res.hidden = !rows.length;
            }).catch(function () {});
        }, 250);
      });
      document.addEventListener('click', function (e) { if (!box.contains(e.target)) { res.hidden = true; } });
    });

    /* E-mails : affiche le gabarit du type et de la langue choisis */
    var selK = document.getElementById('wspo-mail-key'), selL = document.getElementById('wspo-mail-lang');
    if (selK && selL) {
      var show = function () {
        document.querySelectorAll('.wspo-mail').forEach(function (m) {
          m.hidden = !(m.getAttribute('data-key') === selK.value && m.getAttribute('data-lang') === selL.value);
        });
        document.getElementById('wspo-test-key').value = selK.value;
        document.getElementById('wspo-test-lang').value = selL.value;
      };
      selK.addEventListener('change', show); selL.addEventListener('change', show); show();
    }
  });
})();
