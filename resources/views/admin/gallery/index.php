<?php
/** @var array $galleryPhotos */
/** @var array $events */
/** @var array<string,int> $pendingImages */
/** @var array<int,int> $duplicates */
$total = count($galleryPhotos);
?>
<div class="page-header">
  <h1><?= __('nav.gallery') ?></h1>
</div>

<?php if (!empty($pendingImages)): ?>
  <div class="alert alert-warning mb-6" style="display:block;">
    <strong><?= __('images.pending_title', ['count' => (string) array_sum($pendingImages)]) ?></strong>
    <p class="text-sm mb-4 mt-2"><?= __('images.pending_body') ?></p>
    <p class="text-sm mb-4">
      <?php foreach ($pendingImages as $kind => $n): ?>
        <span class="badge badge-indigo"><?= __('images.kind_' . $kind) ?>: <?= (int) $n ?></span>
      <?php endforeach; ?>
    </p>
    <form method="post" action="<?= base_url('admin/images/optimize') ?>" style="margin:0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary btn-sm"><?= __('images.optimize_button') ?></button>
    </form>
  </div>
<?php endif; ?>

<div class="card mb-6">
  <div class="card-header"><h3><?= __('gallery.upload_title') ?></h3></div>
  <p class="form-hint mb-4"><?= __('settings.gallery_hint') ?></p>

  <form id="galleryUploadForm" method="post" action="<?= base_url('admin/gallery') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group">
        <label><?= __('gallery.choose_photos') ?></label>
        <input type="file" id="galleryFiles" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required>
      </div>
      <div class="form-group">
        <label><?= __('gallery.album_label') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
        <select name="event_id" class="form-control">
          <option value=""><?= __('gallery.no_album') ?></option>
          <?php foreach ($events as $ev): ?>
            <option value="<?= (int) $ev['id'] ?>"><?= e($ev['name_th']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="flex:0 0 auto;">
        <button type="submit" class="btn btn-primary" id="galleryUploadBtn"><?= __('settings.gallery_add_button') ?></button>
      </div>
    </div>
    <p class="form-hint"><?= __('gallery.size_hint') ?></p>
    <ul id="galleryProgress" class="mt-2" style="list-style:none;padding:0;margin:0;font-size:13px;"></ul>
  </form>
</div>

<?php
$mainCount = $total >= 8 ? 3 : 1; // the home mosaic's big tile shows (and rotates through) these first photos
?>
<?php if ($galleryPhotos): ?>
  <div class="card">
    <div class="card-header"><h3><?= __('gallery.all_photos', ['count' => (string) $total]) ?></h3></div>
    <p class="form-hint mb-4"><?= __('gallery.order_hint_drag') ?></p>
    <div class="gallery-admin-grid" id="galleryGrid" data-reorder-url="<?= base_url('admin/gallery/reorder') ?>" data-main-count="<?= (int) $mainCount ?>">
      <?php foreach ($galleryPhotos as $i => $photo): ?>
        <?php $isMain = $i < $mainCount; ?>
        <div class="gallery-admin-card<?= $isMain ? ' is-main' : '' ?>" draggable="true" data-id="<?= (int) $photo['id'] ?>">
          <div class="gallery-admin-thumb">
            <img src="<?= upload_url($photo['thumb_path'] ?: $photo['image_path']) ?>" alt="" loading="lazy" draggable="false">
            <span class="gallery-admin-pos">#<?= $i + 1 ?></span>
            <span class="gallery-admin-main-badge"<?= $isMain ? '' : ' hidden' ?>>★ <?= __('gallery.main_photo') ?></span>
            <form method="post" action="<?= base_url('admin/gallery/' . $photo['id'] . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>" class="gallery-admin-delete">
              <?= csrf_field() ?>
              <button type="submit" title="<?= e(__('common.delete')) ?>" aria-label="<?= e(__('common.delete')) ?>">&times;</button>
            </form>
          </div>
          <?php if (isset($duplicates[(int) $photo['id']])): ?>
            <div class="gallery-admin-dup">⚠ <?= __('gallery.duplicate_of', ['pos' => (string) (array_search($duplicates[(int) $photo['id']], array_map('intval', array_column($galleryPhotos, 'id')), true) + 1)]) ?></div>
          <?php endif; ?>
          <div class="gallery-admin-caption text-sm"><?= e($photo['caption'] ?? '') ?: '<span class="text-muted">' . e(__('gallery.no_caption')) . '</span>' ?></div>
          <details class="gallery-admin-edit">
            <summary><?= __('common.edit') ?></summary>
            <form method="post" action="<?= base_url('admin/gallery/' . $photo['id']) ?>">
              <?= csrf_field() ?>
              <input type="text" name="caption" class="form-control mb-2" maxlength="150" value="<?= e($photo['caption'] ?? '') ?>" placeholder="<?= e(__('settings.gallery_caption_placeholder')) ?>">
              <select name="event_id" class="form-control mb-2">
                <option value=""><?= __('gallery.no_album') ?></option>
                <?php foreach ($events as $ev): ?>
                  <option value="<?= (int) $ev['id'] ?>"<?= (int) ($photo['event_id'] ?? 0) === (int) $ev['id'] ? ' selected' : '' ?>><?= e($ev['name_th']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;"><?= __('common.save') ?></button>
            </form>
            <div class="gallery-admin-arrows">
              <form method="post" action="<?= base_url('admin/gallery/' . $photo['id'] . '/move') ?>"><?= csrf_field() ?><input type="hidden" name="direction" value="up"><button type="submit" class="btn btn-secondary btn-sm"<?= $i === 0 ? ' disabled' : '' ?> title="<?= e(__('gallery.move_earlier')) ?>">&larr;</button></form>
              <form method="post" action="<?= base_url('admin/gallery/' . $photo['id'] . '/move') ?>"><?= csrf_field() ?><input type="hidden" name="direction" value="down"><button type="submit" class="btn btn-secondary btn-sm"<?= $i === $total - 1 ? ' disabled' : '' ?> title="<?= e(__('gallery.move_later')) ?>">&rarr;</button></form>
            </div>
          </details>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<script>
(function () {
  var form = document.getElementById('galleryUploadForm');
  var input = document.getElementById('galleryFiles');
  var btn = document.getElementById('galleryUploadBtn');
  var list = document.getElementById('galleryProgress');
  var MAX_SIDE = 2000;           // browsers shrink big phone photos first so each request stays small
  var SHRINK_ABOVE = 1.5 * 1024 * 1024;

  function shrink(file) {
    if (file.size <= SHRINK_ABOVE || !window.createImageBitmap) return Promise.resolve(file);
    return createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
      var scale = Math.min(1, MAX_SIDE / Math.max(bmp.width, bmp.height));
      var canvas = document.createElement('canvas');
      canvas.width = Math.round(bmp.width * scale);
      canvas.height = Math.round(bmp.height * scale);
      canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
      return new Promise(function (resolve) {
        canvas.toBlob(function (blob) { resolve(blob || file); }, 'image/jpeg', 0.9);
      });
    }).catch(function () { return file; });
  }

  function addRow(name) {
    var li = document.createElement('li');
    li.textContent = '⏳ ' + name;
    list.appendChild(li);
    return li;
  }

  form.addEventListener('submit', function (e) {
    if (!input.files || !input.files.length || !window.fetch || !window.FormData) return; // plain submit fallback
    e.preventDefault();
    btn.disabled = true;
    var files = Array.prototype.slice.call(input.files);
    var token = form.querySelector('input[name="_csrf"]').value;
    var eventId = form.querySelector('select[name="event_id"]').value;
    var failed = 0;

    files.reduce(function (chain, file) {
      return chain.then(function () {
        var row = addRow(file.name);
        return shrink(file).then(function (blob) {
          var fd = new FormData();
          fd.append('_csrf', token);
          fd.append('event_id', eventId);
          fd.append('photo', blob, file.name.replace(/\.\w+$/, '') + (blob === file ? '' : '.jpg'));
          return fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        }).then(function (res) { return res.json(); }).then(function (data) {
          if (data.ok) { row.textContent = '✅ ' + file.name; } else { failed++; row.textContent = '❌ ' + file.name + ' — ' + (data.error || ''); }
        }).catch(function () { failed++; row.textContent = '❌ ' + file.name; });
      });
    }, Promise.resolve()).then(function () {
      if (!failed) { window.location.reload(); } else { btn.disabled = false; }
    });
  });
})();
</script>

<script>
(function () {
  var grid = document.getElementById('galleryGrid');
  if (!grid) return;
  var token = document.querySelector('input[name="_csrf"]').value;
  var dragging = null;

  function renumber() {
    var mainCount = parseInt(grid.getAttribute('data-main-count'), 10);
    Array.prototype.forEach.call(grid.children, function (card, i) {
      card.querySelector('.gallery-admin-pos').textContent = '#' + (i + 1);
      var main = i < mainCount;
      card.classList.toggle('is-main', main);
      card.querySelector('.gallery-admin-main-badge').hidden = !main;
    });
  }

  grid.addEventListener('dragstart', function (e) {
    dragging = e.target.closest('.gallery-admin-card');
    if (!dragging) return;
    dragging.classList.add('is-dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', dragging.getAttribute('data-id'));
  });
  grid.addEventListener('dragover', function (e) {
    if (!dragging) return;
    e.preventDefault();
    var over = e.target.closest('.gallery-admin-card');
    if (!over || over === dragging) return;
    var rect = over.getBoundingClientRect();
    var after = (e.clientX - rect.left) > rect.width / 2;
    grid.insertBefore(dragging, after ? over.nextSibling : over);
    renumber();
  });
  grid.addEventListener('dragend', function () {
    if (!dragging) return;
    dragging.classList.remove('is-dragging');
    dragging = null;
    var fd = new FormData();
    fd.append('_csrf', token);
    Array.prototype.forEach.call(grid.children, function (card) { fd.append('order[]', card.getAttribute('data-id')); });
    fetch(grid.getAttribute('data-reorder-url'), { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .catch(function () { window.location.reload(); });
  });
})();
</script>
