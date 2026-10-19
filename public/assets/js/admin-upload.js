/**
 * MBW Admin — upload images produit (Sprint B).
 * Drag & drop multi-fichiers + capture caméra (mobile) + upload AJAX
 * + réordonnancement des vignettes par glisser-déposer.
 * Aucune dépendance. Dépend du wrapper fetch CSRF de admin.js (window.MBW_CSRF).
 */
(function () {
  'use strict';

  var zone = document.getElementById('img-dropzone');
  if (!zone) return;

  var productId = zone.getAttribute('data-product-id') || '';
  var uploadUrl = zone.getAttribute('data-upload-url') || '';
  var sortUrl = zone.getAttribute('data-sort-url') || '';
  var primaryTpl = zone.getAttribute('data-primary-url-template') || '';
  var deleteTpl = zone.getAttribute('data-delete-url-template') || '';
  var grid = document.getElementById('img-grid');
  var inputCamera = document.getElementById('img-input-camera');
  var inputFiles = document.getElementById('img-input-files');
  var bar = zone.querySelector('.admin-dropzone__bar');
  var progress = zone.querySelector('.admin-dropzone__progress');
  var busy = false;
  var dragging = null;

  function urlFrom(tpl, iid) {
    return tpl.replace('{iid}', String(iid));
  }

  /* ---------------------------------------------------------- Upload */

  function uploadFiles(fileList) {
    if (busy) return;
    var files = Array.prototype.slice.call(fileList || []).filter(function (f) {
      return f && f.type && /^image\/(jpeg|png|webp)$/.test(f.type);
    });
    if (!files.length) {
      toast('Formats acceptés : JPEG, PNG ou WebP.', 'err');
      return;
    }
    if (files.length > 8) {
      files = files.slice(0, 8);
      toast('8 images maximum par lot.', 'err');
    }
    var tooBig = files.filter(function (f) { return f.size > 2 * 1024 * 1024; });
    if (tooBig.length) {
      files = files.filter(function (f) { return f.size <= 2 * 1024 * 1024; });
      toast(tooBig.length + ' image(s) dépassent 2 Mo et ont été ignorée(s).', 'err');
      if (!files.length) return;
    }

    busy = true;
    zone.classList.add('is-loading');
    showProgress(true, 8);

    var fd = new FormData();
    files.forEach(function (f) { fd.append('images[]', f, f.name || 'photo.jpg'); });

    fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
      .then(function (r) { return r.json().catch(function () { return { success: r.ok, message: '' }; }); })
      .then(function (json) {
        showProgress(false);
        zone.classList.remove('is-loading');
        busy = false;
        if (json.success) {
          renderImages(json.data && json.data.images);
          toast(json.message || 'Image(s) ajoutée(s).', 'ok');
        } else {
          toast(json.message || 'Échec de l\'upload.', 'err');
        }
      })
      .catch(function () {
        showProgress(false);
        zone.classList.remove('is-loading');
        busy = false;
        toast('Erreur réseau pendant l\'upload.', 'err');
      });
  }

  function toast(msg, type) {
    if (typeof window.mbwToast === 'function') window.mbwToast(msg, type);
  }

  function showProgress(on, pct) {
    if (!progress) return;
    progress.hidden = !on;
    if (bar && typeof pct === 'number') bar.style.width = pct + '%';
  }

  /* ------------------------------------------------- Drag & drop zone */

  ['dragenter', 'dragover'].forEach(function (ev) {
    zone.addEventListener(ev, function (e) {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.add('is-dragover');
    });
  });
  ['dragleave', 'drop'].forEach(function (ev) {
    zone.addEventListener(ev, function (e) {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.remove('is-dragover');
    });
  });
  zone.addEventListener('drop', function (e) {
    var dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length) uploadFiles(dt.files);
  });

  zone.querySelectorAll('[data-pick]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var which = btn.getAttribute('data-pick');
      var input = which === 'camera' ? inputCamera : inputFiles;
      if (input) input.click();
    });
  });
  [inputCamera, inputFiles].forEach(function (input) {
    if (!input) return;
    input.addEventListener('change', function () {
      if (input.files && input.files.length) uploadFiles(input.files);
      input.value = '';
    });
  });

  /* ------------------------------------------------- Rendu des vignettes */

  function renderImages(images) {
    if (!grid || !images) return;
    grid.innerHTML = '';
    images.forEach(function (img, i) {
      grid.appendChild(makeThumb(img, i === 0));
    });
    var count = document.getElementById('img-count');
    if (count) count.textContent = images.length + ' photo(s)';
  }

  function makeThumb(img, isPrimary) {
    var el = document.createElement('div');
    el.className = 'admin-imggrid__item' + (isPrimary ? ' is-primary' : '');
    el.setAttribute('draggable', 'true');
    el.setAttribute('data-id', String(img.id));

    var image = document.createElement('img');
    image.src = img.thumb || img.url;
    image.alt = '';
    image.loading = 'lazy';
    el.appendChild(image);

    if (isPrimary) {
      var badge = document.createElement('span');
      badge.className = 'admin-imggrid__star';
      badge.textContent = '★';
      badge.title = 'Image principale';
      el.appendChild(badge);
    }

    var actions = document.createElement('div');
    actions.className = 'admin-imggrid__actions';

    if (!isPrimary && primaryTpl) {
      var formP = document.createElement('form');
      formP.method = 'post';
      formP.action = urlFrom(primaryTpl, img.id);
      var bP = document.createElement('button');
      bP.type = 'submit';
      bP.title = 'Mettre en principale';
      bP.textContent = '★';
      formP.appendChild(bP);
      actions.appendChild(formP);
    }

    if (deleteTpl) {
      var formD = document.createElement('form');
      formD.method = 'post';
      formD.action = urlFrom(deleteTpl, img.id);
      formD.setAttribute('data-confirm', 'Supprimer cette image ?');
      var bD = document.createElement('button');
      bD.type = 'submit';
      bD.title = 'Supprimer';
      bD.className = 'is-danger';
      bD.textContent = '✕';
      formD.appendChild(bD);
      actions.appendChild(formD);
    }

    el.appendChild(actions);
    attachThumbDnD(el);
    return el;
  }

  /* ------------------------------------- Réordonnancement DnD vignettes */

  function attachThumbDnD(el) {
    el.addEventListener('dragstart', function (e) {
      dragging = el;
      el.classList.add('is-dragging');
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', el.getAttribute('data-id') || ''); } catch (err) { /* Safari */ }
      }
    });
    el.addEventListener('dragend', function () {
      el.classList.remove('is-dragging');
      dragging = null;
      Array.prototype.forEach.call(grid ? grid.children : [], function (c) {
        c.classList.remove('is-over');
      });
    });
    el.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (dragging && dragging !== el) el.classList.add('is-over');
    });
    el.addEventListener('dragleave', function () {
      el.classList.remove('is-over');
    });
    el.addEventListener('drop', function (e) {
      e.preventDefault();
      e.stopPropagation();
      el.classList.remove('is-over');
      if (!dragging || dragging === el || !grid) return;
      var items = Array.prototype.slice.call(grid.children);
      var from = items.indexOf(dragging);
      var to = items.indexOf(el);
      if (from < 0 || to < 0 || from === to) return;
      if (from < to) {
        grid.insertBefore(dragging, el.nextSibling);
      } else {
        grid.insertBefore(dragging, el);
      }
      saveOrder();
    });
  }

  if (grid) {
    Array.prototype.forEach.call(grid.children, attachThumbDnD);
  }

  function saveOrder() {
    if (!grid || !sortUrl) return;
    var ids = Array.prototype.map.call(grid.children, function (c) {
      return parseInt(c.getAttribute('data-id'), 10) || 0;
    }).filter(Boolean);
    fetch(sortUrl, {
      method: 'POST',
      body: JSON.stringify({ ids: ids }),
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
    })
      .then(function (r) { return r.json().catch(function () { return { success: r.ok }; }); })
      .then(function (json) {
        if (json.success) {
          // Recaler la classe primaire sur le 1er
          Array.prototype.forEach.call(grid.children, function (c, i) {
            c.classList.toggle('is-primary', i === 0);
            var star = c.querySelector('.admin-imggrid__star');
            if (i === 0 && !star) {
              var s = document.createElement('span');
              s.className = 'admin-imggrid__star';
              s.textContent = '★';
              c.appendChild(s);
            } else if (i !== 0 && star) {
              star.remove();
            }
          });
          toast('Ordre des images mis à jour.', 'ok');
        } else {
          toast(json.message || 'Impossible d\'enregistrer l\'ordre.', 'err');
        }
      })
      .catch(function () { toast('Erreur réseau (ordre).', 'err'); });
  }
})();
