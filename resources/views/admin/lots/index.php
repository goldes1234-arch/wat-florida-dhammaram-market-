<?php $counts = \App\Models\Lot::statusCountsForEvent((int) $event['id']); ?>
<div class="page-header">
  <h1><?= __('lot.list_title') ?> — <?= e($event['name_th']) ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/events/' . $event['id'] . '/edit') ?>" class="btn btn-secondary">&larr; <?= __('common.back') ?></a>
    <a href="<?= base_url('admin/events/' . $event['id'] . '/zones') ?>" class="btn btn-secondary">🏷️ <?= __('zone.list_title') ?></a>
    <a href="<?= base_url('admin/events/' . $event['id'] . '/lots/map') ?>" class="btn btn-secondary">📍 <?= __('lot.position_on_map') ?></a>
    <?php if ($lots): ?>
      <form method="post" action="<?= base_url('admin/events/' . $event['id'] . '/lots/delete-all') ?>" data-confirm="<?= e(__('lot.delete_all_confirm')) ?>" style="margin:0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger"><?= __('lot.delete_all_button') ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-4 mb-6">
  <div class="stat-card"><div class="stat-label"><?= __('lot.status_available') ?></div><div class="stat-value success"><?= $counts['available'] ?></div></div>
  <div class="stat-card"><div class="stat-label"><?= __('lot.status_pending_payment') ?></div><div class="stat-value accent"><?= $counts['pending_payment'] ?></div></div>
  <div class="stat-card"><div class="stat-label"><?= __('lot.status_booked') ?></div><div class="stat-value primary"><?= $counts['booked'] ?></div></div>
  <div class="stat-card"><div class="stat-label"><?= __('lot.status_disabled') ?></div><div class="stat-value"><?= $counts['disabled'] ?></div></div>
</div>

<?php if (!$zones): ?>
  <div class="alert alert-warning">
    🏷️ <span><?= __('lot.no_zones_hint') ?> <a href="<?= base_url('admin/events/' . $event['id'] . '/zones') ?>"><?= __('lot.no_zones_hint_link') ?></a></span>
  </div>
<?php endif; ?>

<div class="grid grid-cols-3 mb-6">
  <div class="card">
    <div class="card-header"><h3><?= __('lot.add') ?></h3></div>
    <form method="post" action="<?= base_url('admin/events/' . $event['id'] . '/lots') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group">
        <label><?= __('lot.code') ?></label>
        <input type="text" name="code" class="form-control" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('lot.zone') ?></label>
          <select name="zone_id" class="form-control">
            <option value=""><?= __('lot.no_zone') ?></option>
            <?php foreach ($zones as $zone): ?>
              <option value="<?= (int) $zone['id'] ?>"><?= e($zone['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label><?= __('lot.price') ?></label>
          <input type="number" step="0.01" min="0" name="price" class="form-control" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('lot.grid_row') ?></label>
          <input type="number" min="1" name="grid_row" class="form-control">
        </div>
        <div class="form-group">
          <label><?= __('lot.grid_col') ?></label>
          <input type="number" min="1" name="grid_col" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label><?= __('lot.photo') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
        <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
        <p class="form-hint"><?= __('lot.photo_hint') ?></p>
      </div>
      <button type="submit" class="btn btn-primary"><?= __('lot.add') ?></button>
    </form>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('lot.bulk_add_title') ?></h3></div>
    <form method="post" action="<?= base_url('admin/events/' . $event['id'] . '/lots/bulk') ?>">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('lot.bulk_prefix') ?></label>
          <input type="text" name="prefix" class="form-control" placeholder="A-">
        </div>
        <div class="form-group">
          <label><?= __('lot.bulk_start') ?></label>
          <input type="number" min="1" name="start_number" class="form-control" required>
        </div>
        <div class="form-group">
          <label><?= __('lot.bulk_end') ?></label>
          <input type="number" min="1" name="end_number" class="form-control" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('lot.zone') ?></label>
          <select name="zone_id" class="form-control">
            <option value=""><?= __('lot.no_zone') ?></option>
            <?php foreach ($zones as $zone): ?>
              <option value="<?= (int) $zone['id'] ?>"><?= e($zone['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label><?= __('lot.bulk_price') ?></label>
          <input type="number" step="0.01" min="0" name="price" class="form-control" required>
        </div>
      </div>
      <p class="form-hint mb-4"><?= __('lot.bulk_hint') ?></p>
      <button type="submit" class="btn btn-accent"><?= __('lot.bulk_add_title') ?></button>
    </form>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('lot.grid_add_title') ?></h3></div>
    <form method="post" action="<?= base_url('admin/events/' . $event['id'] . '/lots/grid') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label><?= __('lot.grid_prefix') ?></label>
        <input type="text" name="grid_prefix" class="form-control" placeholder="F">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('lot.grid_rows') ?></label>
          <input type="number" min="1" max="50" name="rows" class="form-control" required>
        </div>
        <div class="form-group">
          <label><?= __('lot.grid_columns') ?></label>
          <input type="number" min="1" max="50" name="columns" class="form-control" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('lot.zone') ?></label>
          <select name="zone_id" class="form-control">
            <option value=""><?= __('lot.no_zone') ?></option>
            <?php foreach ($zones as $zone): ?>
              <option value="<?= (int) $zone['id'] ?>"><?= e($zone['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label><?= __('lot.bulk_price') ?></label>
          <input type="number" step="0.01" min="0" name="price" class="form-control" required>
        </div>
      </div>
      <p class="form-hint mb-4"><?= __('lot.grid_hint') ?></p>
      <button type="submit" class="btn btn-accent"><?= __('lot.grid_add_title') ?></button>
    </form>
  </div>
</div>

<?php if (!$lots): ?>
  <div class="empty-state"><div class="empty-icon">🎪</div><?= __('lot.none') ?></div>
<?php else: ?>
  <p class="form-hint mb-2"><?= __('lot.inline_edit_hint') ?> <span id="lotsTableStatus" class="text-sm text-muted"></span></p>
  <form id="bulkDeleteLotsForm" method="post" action="<?= base_url('admin/events/' . $event['id'] . '/lots/delete-selected') ?>" data-confirm="<?= e(__('lot.delete_selected_confirm')) ?>">
    <?= csrf_field() ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAllLots"></th>
            <th></th>
            <th><?= __('lot.code') ?></th>
            <th><?= __('lot.zone') ?></th>
            <th><?= __('lot.price') ?></th>
            <th><?= __('common.status') ?></th>
            <th><?= __('common.actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lots as $lot): ?>
            <?php $deletable = in_array($lot['status'], ['available', 'disabled'], true); ?>
            <tr>
              <td>
                <?php if ($deletable): ?>
                  <input type="checkbox" name="ids[]" value="<?= (int) $lot['id'] ?>" class="lot-row-checkbox">
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($lot['photo'])): ?>
                  <img src="<?= upload_url($lot['photo']) ?>" class="thumb-sm" alt="">
                <?php else: ?>
                  <div class="thumb-sm skeleton-block" style="font-size:16px;">🎪</div>
                <?php endif; ?>
              </td>
              <td>
                <span class="inline-edit-cell" data-lot-id="<?= (int) $lot['id'] ?>" data-field="code" data-value="<?= e($lot['code']) ?>">
                  <strong><?= e($lot['code']) ?></strong>
                </span>
              </td>
              <td>
                <span class="inline-edit-cell" data-lot-id="<?= (int) $lot['id'] ?>" data-field="zone_id" data-value="<?= (int) ($lot['zone_id'] ?? 0) ?>">
                  <?= e($lot['zone_name'] ?? __('lot.no_zone')) ?>
                </span>
              </td>
              <td>
                <span class="inline-edit-cell" data-lot-id="<?= (int) $lot['id'] ?>" data-field="price" data-value="<?= e((string) $lot['price']) ?>">
                  <?= money((float) $lot['price']) ?>
                </span>
              </td>
              <td><span class="<?= lot_status_badge_class($lot['status']) ?>"><?= lot_status_label($lot['status']) ?></span></td>
              <td style="display:flex;gap:8px;">
                <a href="<?= base_url('admin/lots/' . $lot['id'] . '/edit') ?>" class="btn btn-secondary btn-sm"><?= __('common.edit') ?></a>
                <?php if ($deletable): ?>
                  <form method="post" action="<?= base_url('admin/lots/' . $lot['id'] . '/delete') ?>" data-confirm="<?= e(__('lot.delete_confirm')) ?>" style="margin:0;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger btn-sm"><?= __('common.delete') ?></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      <button type="submit" id="deleteSelectedLotsBtn" class="btn btn-danger" disabled><?= __('lot.delete_selected_button') ?></button>
      <p class="form-hint mb-0"><?= __('lot.delete_selected_hint') ?></p>
    </div>
  </form>

  <script>
  (function () {
    var inlineUpdateUrlBase = <?= json_encode(base_url('admin/lots/')) ?>;
    var csrfToken = <?= json_encode(\App\Core\Csrf::token()) ?>;
    var statusEl = document.getElementById('lotsTableStatus');
    var msgSaved = <?= json_encode(__('lot.inline_saved'), JSON_UNESCAPED_UNICODE) ?>;
    var msgError = <?= json_encode(__('lot.inline_save_error'), JSON_UNESCAPED_UNICODE) ?>;
    var lblNoZone = <?= json_encode(__('lot.no_zone'), JSON_UNESCAPED_UNICODE) ?>;
    var zones = <?= json_encode(array_map(function ($z) { return ['id' => (int) $z['id'], 'name' => $z['name']]; }, $zones), JSON_UNESCAPED_UNICODE) ?>;

    function buildEditor(cell) {
      var field = cell.getAttribute('data-field');
      var value = cell.getAttribute('data-value');
      var input;

      if (field === 'zone_id') {
        input = document.createElement('select');
        input.className = 'inline-edit-input';
        var noneOpt = document.createElement('option');
        noneOpt.value = '';
        noneOpt.textContent = lblNoZone;
        input.appendChild(noneOpt);
        zones.forEach(function (z) {
          var opt = document.createElement('option');
          opt.value = z.id;
          opt.textContent = z.name;
          if (String(z.id) === value) opt.selected = true;
          input.appendChild(opt);
        });
      } else {
        input = document.createElement('input');
        input.className = 'inline-edit-input';
        input.type = field === 'price' ? 'number' : 'text';
        if (field === 'price') { input.step = '0.01'; input.min = '0'; }
        input.value = value;
      }
      return input;
    }

    function startEdit(cell) {
      if (cell.querySelector('.inline-edit-input')) return;
      var original = cell.innerHTML;
      var input = buildEditor(cell);
      cell.innerHTML = '';
      cell.appendChild(input);
      input.focus();
      if (input.select) input.select();

      var done = false;
      function commit() {
        if (done) return;
        done = true;
        var lotId = cell.getAttribute('data-lot-id');
        var field = cell.getAttribute('data-field');
        var newValue = input.value;

        if (newValue === cell.getAttribute('data-value')) {
          cell.innerHTML = original;
          return;
        }

        var body = new URLSearchParams();
        body.set('field', field);
        body.set('value', newValue);
        body.set('_csrf', csrfToken);

        fetch(inlineUpdateUrlBase + lotId + '/inline-update', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data.ok) {
              statusEl.textContent = data.error || msgError;
              cell.innerHTML = original;
              return;
            }
            statusEl.textContent = msgSaved;
            if (field === 'code') {
              cell.setAttribute('data-value', data.display.code);
              cell.innerHTML = '';
              var strong = document.createElement('strong');
              strong.textContent = data.display.code;
              cell.appendChild(strong);
            } else if (field === 'zone_id') {
              cell.setAttribute('data-value', newValue);
              cell.textContent = data.display.zone_name;
            } else if (field === 'price') {
              cell.setAttribute('data-value', newValue);
              cell.textContent = data.display.price;
            }
          })
          .catch(function () {
            statusEl.textContent = msgError;
            cell.innerHTML = original;
          });
      }

      input.addEventListener('blur', commit);
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          input.blur();
        } else if (e.key === 'Escape') {
          done = true;
          cell.innerHTML = original;
        }
      });
    }

    document.querySelectorAll('.inline-edit-cell').forEach(function (cell) {
      cell.addEventListener('click', function () { startEdit(cell); });
    });
  })();
  </script>

  <script>
  (function () {
    var selectAll = document.getElementById('selectAllLots');
    var deleteBtn = document.getElementById('deleteSelectedLotsBtn');
    var checkboxes = function () { return document.querySelectorAll('.lot-row-checkbox'); };

    function refreshButton() {
      var anyChecked = Array.prototype.some.call(checkboxes(), function (c) { return c.checked; });
      deleteBtn.disabled = !anyChecked;
    }

    selectAll.addEventListener('change', function () {
      Array.prototype.forEach.call(checkboxes(), function (c) { c.checked = selectAll.checked; });
      refreshButton();
    });

    Array.prototype.forEach.call(checkboxes(), function (c) {
      c.addEventListener('change', refreshButton);
    });
  })();
  </script>
<?php endif; ?>
