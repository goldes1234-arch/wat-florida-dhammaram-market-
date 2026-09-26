<?php
/** @var array $event */
/** @var array $lots */
?>
<div class="page-header">
  <h1><?= __('lot.map_editor_title') ?> — <?= e($event['name_th']) ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/events/' . $event['id'] . '/lots') ?>" class="btn btn-secondary">&larr; <?= __('common.back') ?></a>
  </div>
</div>

<?php if (empty($event['floorplan_image'])): ?>
  <div class="alert alert-warning">
    🗺️ <span><?= __('lot.map_no_photo') ?> <a href="<?= base_url('admin/events/' . $event['id'] . '/edit') ?>"><?= __('lot.map_upload_link') ?></a></span>
  </div>
<?php elseif (!$lots): ?>
  <div class="empty-state"><div class="empty-icon">🎪</div><?= __('lot.none') ?></div>
<?php else: ?>
  <div class="map-editor-toolbar">
    <p class="form-hint mb-0"><?= __('lot.map_editor_hint') ?></p>
    <span id="mapEditorStatus" class="text-sm text-muted"></span>
  </div>

  <div class="map-editor-layout">
    <div class="map-editor-photo-wrap">
      <img src="<?= upload_url($event['floorplan_image']) ?>" class="map-editor-photo" id="mapEditorPhoto" alt="">
      <div class="map-editor-pins" id="mapEditorPins">
        <?php foreach ($lots as $lot): ?>
          <?php if ($lot['map_x'] !== null && $lot['map_y'] !== null): ?>
            <?php $rotation = $lot['map_shape'] === 'box' ? (float) $lot['map_rotation'] : 0; ?>
            <div class="map-editor-pin size-<?= e($lot['map_size']) ?> shape-<?= e($lot['map_shape']) ?>" data-lot-id="<?= (int) $lot['id'] ?>"
                 data-rotation="<?= e((string) $rotation) ?>"
                 style="left: <?= e($lot['map_x']) ?>%; top: <?= e($lot['map_y']) ?>%; transform: translate(-50%, -50%) rotate(<?= e((string) $rotation) ?>deg);"><?= e($lot['code']) ?></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="map-editor-sidebar">
      <div class="map-editor-quick-add">
        <div class="map-editor-quick-add-title"><?= __('lot.map_quick_add_title') ?></div>
        <div class="map-editor-quick-add-row">
          <input type="text" id="quickAddCode" class="form-control" placeholder="<?= __('lot.map_quick_add_code_placeholder') ?>">
          <input type="number" step="0.01" min="0" id="quickAddPrice" class="form-control" placeholder="<?= __('lot.map_quick_add_price_placeholder') ?>">
          <button type="button" id="quickAddBtn" class="btn btn-primary btn-sm"><?= __('lot.map_quick_add_button') ?></button>
        </div>
        <p id="quickAddError" class="form-hint mb-0" style="color:var(--color-danger); display:none;"></p>
      </div>

      <div class="map-editor-list" id="mapEditorList">
      <?php foreach ($lots as $lot): ?>
        <?php $placed = $lot['map_x'] !== null && $lot['map_y'] !== null; ?>
        <div class="map-editor-lot-row<?= $placed ? ' is-placed' : '' ?>"
             data-lot-id="<?= (int) $lot['id'] ?>" data-map-size="<?= e($lot['map_size']) ?>" data-map-shape="<?= e($lot['map_shape']) ?>">
          <button type="button" class="map-editor-lot-btn"
                  data-lot-id="<?= (int) $lot['id'] ?>" data-lot-code="<?= e($lot['code']) ?>">
            <span class="lot-code"><?= e($lot['code']) ?></span>
            <span class="lot-map-status text-sm text-muted"><?= $placed ? __('lot.map_placed') : __('lot.map_unplaced') ?></span>
          </button>
          <div class="map-editor-shape-group" role="group">
            <button type="button" class="map-editor-shape-btn<?= $lot['map_shape'] === 'pin' ? ' is-active' : '' ?>"
                    data-lot-id="<?= (int) $lot['id'] ?>" data-shape="pin" title="<?= __('lot.map_shape_pin') ?>">●</button>
            <button type="button" class="map-editor-shape-btn<?= $lot['map_shape'] === 'box' ? ' is-active' : '' ?>"
                    data-lot-id="<?= (int) $lot['id'] ?>" data-shape="box" title="<?= __('lot.map_shape_box') ?>">▭</button>
          </div>
          <div class="map-editor-size-group" role="group">
            <?php foreach (['small' => 'S', 'medium' => 'M', 'large' => 'L'] as $sizeValue => $sizeLetter): ?>
              <button type="button" class="map-editor-size-btn<?= $lot['map_size'] === $sizeValue ? ' is-active' : '' ?>"
                      data-lot-id="<?= (int) $lot['id'] ?>" data-size="<?= $sizeValue ?>"
                      title="<?= __('lot.map_size_' . $sizeValue) ?>"><?= $sizeLetter ?></button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script>
  (function () {
    var photo = document.getElementById('mapEditorPhoto');
    var pinsLayer = document.getElementById('mapEditorPins');
    var statusEl = document.getElementById('mapEditorStatus');
    var saveUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-position')) ?>;
    var sizeUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-size')) ?>;
    var shapeUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-shape')) ?>;
    var rotationUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-rotation')) ?>;
    var quickAddUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-quick-add')) ?>;
    var csrfToken = <?= json_encode(\App\Core\Csrf::token()) ?>;
    var msgSaved = <?= json_encode(__('lot.map_saved'), JSON_UNESCAPED_UNICODE) ?>;
    var msgError = <?= json_encode(__('lot.map_save_error'), JSON_UNESCAPED_UNICODE) ?>;
    var msgQuickAddSuccess = <?= json_encode(__('lot.map_quick_add_success'), JSON_UNESCAPED_UNICODE) ?>;
    var lblPlaced = <?= json_encode(__('lot.map_placed'), JSON_UNESCAPED_UNICODE) ?>;
    var lblUnplaced = <?= json_encode(__('lot.map_unplaced'), JSON_UNESCAPED_UNICODE) ?>;
    var lblShapePin = <?= json_encode(__('lot.map_shape_pin'), JSON_UNESCAPED_UNICODE) ?>;
    var lblShapeBox = <?= json_encode(__('lot.map_shape_box'), JSON_UNESCAPED_UNICODE) ?>;
    var lblSizeSmall = <?= json_encode(__('lot.map_size_small'), JSON_UNESCAPED_UNICODE) ?>;
    var lblSizeMedium = <?= json_encode(__('lot.map_size_medium'), JSON_UNESCAPED_UNICODE) ?>;
    var lblSizeLarge = <?= json_encode(__('lot.map_size_large'), JSON_UNESCAPED_UNICODE) ?>;
    var lotListEl = document.getElementById('mapEditorList');

    var armedLotId = null;
    var armedLotCode = null;

    function clampPct(value) {
      return Math.max(0, Math.min(100, value));
    }

    // Mouse events carry clientX/Y directly; touch events carry them on each Touch in
    // `touches` (finger still down) or `changedTouches` (touchend, finger just lifted).
    function clientPoint(evt) {
      if (evt.touches && evt.touches.length) return evt.touches[0];
      if (evt.changedTouches && evt.changedTouches.length) return evt.changedTouches[0];
      return evt;
    }

    function pointFromEvent(evt) {
      var p = clientPoint(evt);
      var rect = photo.getBoundingClientRect();
      return {
        x: clampPct(((p.clientX - rect.left) / rect.width) * 100),
        y: clampPct(((p.clientY - rect.top) / rect.height) * 100),
      };
    }

    function pinTransform(pin) {
      var rotation = pin.getAttribute('data-rotation') || 0;
      pin.style.transform = 'translate(-50%, -50%) rotate(' + rotation + 'deg)';
    }

    function placePin(lotId, code, xPct, yPct) {
      var pin = pinsLayer.querySelector('[data-lot-id="' + lotId + '"]');
      if (!pin) {
        var row = document.querySelector('.map-editor-lot-row[data-lot-id="' + lotId + '"]');
        var size = row ? row.getAttribute('data-map-size') : 'medium';
        var shape = row ? row.getAttribute('data-map-shape') : 'pin';
        pin = document.createElement('div');
        pin.className = 'map-editor-pin size-' + size + ' shape-' + shape;
        pin.setAttribute('data-lot-id', lotId);
        pin.setAttribute('data-rotation', '0');
        pin.textContent = code;
        pinsLayer.appendChild(pin);
        pinTransform(pin);
      }
      pin.style.left = xPct + '%';
      pin.style.top = yPct + '%';
      return pin;
    }

    function markRowPlaced(lotId) {
      var row = document.querySelector('.map-editor-lot-row[data-lot-id="' + lotId + '"]');
      if (!row) return;
      row.classList.add('is-placed');
      var statusSpan = row.querySelector('.lot-map-status');
      if (statusSpan) statusSpan.textContent = lblPlaced;
    }

    function applyPinSize(lotId, size) {
      var row = document.querySelector('.map-editor-lot-row[data-lot-id="' + lotId + '"]');
      if (row) row.setAttribute('data-map-size', size);

      var pin = pinsLayer.querySelector('[data-lot-id="' + lotId + '"]');
      if (pin) {
        pin.className = pin.className.replace(/\bsize-[a-z]+\b/, 'size-' + size);
      }

      document.querySelectorAll('.map-editor-size-btn[data-lot-id="' + lotId + '"]').forEach(function (b) {
        b.classList.toggle('is-active', b.getAttribute('data-size') === size);
      });
    }

    function applyPinShape(lotId, shape) {
      var row = document.querySelector('.map-editor-lot-row[data-lot-id="' + lotId + '"]');
      if (row) row.setAttribute('data-map-shape', shape);

      var pin = pinsLayer.querySelector('[data-lot-id="' + lotId + '"]');
      if (pin) {
        pin.className = pin.className.replace(/\bshape-[a-z]+\b/, 'shape-' + shape);
      }

      document.querySelectorAll('.map-editor-shape-btn[data-lot-id="' + lotId + '"]').forEach(function (b) {
        b.classList.toggle('is-active', b.getAttribute('data-shape') === shape);
      });

      if (lotId === armedLotId) refreshRotateHandle();
    }

    function saveSize(lotId, size) {
      var body = new URLSearchParams();
      body.set('lot_id', lotId);
      body.set('map_size', size);
      body.set('_csrf', csrfToken);

      fetch(sizeUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          statusEl.textContent = data.ok ? msgSaved : msgError;
          if (data.ok) applyPinSize(lotId, size);
        })
        .catch(function () { statusEl.textContent = msgError; });
    }

    function saveShape(lotId, shape) {
      var body = new URLSearchParams();
      body.set('lot_id', lotId);
      body.set('map_shape', shape);
      body.set('_csrf', csrfToken);

      fetch(shapeUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          statusEl.textContent = data.ok ? msgSaved : msgError;
          if (data.ok) applyPinShape(lotId, shape);
        })
        .catch(function () { statusEl.textContent = msgError; });
    }

    function savePosition(lotId, xPct, yPct) {
      var body = new URLSearchParams();
      body.set('lot_id', lotId);
      body.set('map_x', xPct.toFixed(2));
      body.set('map_y', yPct.toFixed(2));
      body.set('_csrf', csrfToken);

      fetch(saveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          statusEl.textContent = data.ok ? msgSaved : msgError;
          if (data.ok) markRowPlaced(lotId);
        })
        .catch(function () { statusEl.textContent = msgError; });
    }

    function saveRotation(lotId, rotation) {
      var body = new URLSearchParams();
      body.set('lot_id', lotId);
      body.set('map_rotation', rotation.toFixed(1));
      body.set('_csrf', csrfToken);

      fetch(rotationUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) { statusEl.textContent = data.ok ? msgSaved : msgError; })
        .catch(function () { statusEl.textContent = msgError; });
    }

    // Arming selects a lot as the active one — its row gets highlighted in the
    // sidebar and, if it's a box, its rotate handle appears on the map. Reused both
    // from the sidebar list and from clicking a marker directly on the photo, so
    // picking up a box to rotate never requires a trip back to the number list.
    function armLot(lotId, code) {
      lotId = String(lotId);
      document.querySelectorAll('.map-editor-lot-row').forEach(function (r) {
        r.classList.toggle('is-armed', r.getAttribute('data-lot-id') === lotId);
      });
      armedLotId = lotId;
      armedLotCode = code;
      refreshRotateHandle();
    }

    // Delegated so buttons on lot rows added later by quick-add work with no extra
    // wiring — the list only ever needs this one listener, bound once.
    lotListEl.addEventListener('click', function (e) {
      var lotBtn = e.target.closest('.map-editor-lot-btn');
      if (lotBtn) {
        armLot(lotBtn.getAttribute('data-lot-id'), lotBtn.getAttribute('data-lot-code'));
        return;
      }
      var shapeBtn = e.target.closest('.map-editor-shape-btn');
      if (shapeBtn) {
        saveShape(shapeBtn.getAttribute('data-lot-id'), shapeBtn.getAttribute('data-shape'));
        return;
      }
      var sizeBtn = e.target.closest('.map-editor-size-btn');
      if (sizeBtn) {
        saveSize(sizeBtn.getAttribute('data-lot-id'), sizeBtn.getAttribute('data-size'));
      }
    });

    // Builds a sidebar row for a lot the quick-add form just created, matching the
    // server-rendered markup exactly so the delegated listener above handles it too.
    function addLotRow(lot) {
      var row = document.createElement('div');
      row.className = 'map-editor-lot-row';
      row.setAttribute('data-lot-id', lot.id);
      row.setAttribute('data-map-size', lot.map_size);
      row.setAttribute('data-map-shape', lot.map_shape);

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'map-editor-lot-btn';
      btn.setAttribute('data-lot-id', lot.id);
      btn.setAttribute('data-lot-code', lot.code);

      var codeSpan = document.createElement('span');
      codeSpan.className = 'lot-code';
      codeSpan.textContent = lot.code;
      var statusSpan = document.createElement('span');
      statusSpan.className = 'lot-map-status text-sm text-muted';
      statusSpan.textContent = lblUnplaced;
      btn.appendChild(codeSpan);
      btn.appendChild(statusSpan);

      var shapeGroup = document.createElement('div');
      shapeGroup.className = 'map-editor-shape-group';
      shapeGroup.setAttribute('role', 'group');
      [['pin', '●', lblShapePin], ['box', '▭', lblShapeBox]].forEach(function (s) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'map-editor-shape-btn' + (lot.map_shape === s[0] ? ' is-active' : '');
        b.setAttribute('data-lot-id', lot.id);
        b.setAttribute('data-shape', s[0]);
        b.title = s[2];
        b.textContent = s[1];
        shapeGroup.appendChild(b);
      });

      var sizeGroup = document.createElement('div');
      sizeGroup.className = 'map-editor-size-group';
      sizeGroup.setAttribute('role', 'group');
      [['small', 'S', lblSizeSmall], ['medium', 'M', lblSizeMedium], ['large', 'L', lblSizeLarge]].forEach(function (s) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'map-editor-size-btn' + (lot.map_size === s[0] ? ' is-active' : '');
        b.setAttribute('data-lot-id', lot.id);
        b.setAttribute('data-size', s[0]);
        b.title = s[2];
        b.textContent = s[1];
        sizeGroup.appendChild(b);
      });

      row.appendChild(btn);
      row.appendChild(shapeGroup);
      row.appendChild(sizeGroup);
      lotListEl.appendChild(row);
      return row;
    }

    var quickAddBtn = document.getElementById('quickAddBtn');
    var quickAddCode = document.getElementById('quickAddCode');
    var quickAddPrice = document.getElementById('quickAddPrice');
    var quickAddError = document.getElementById('quickAddError');

    function submitQuickAdd() {
      var code = quickAddCode.value.trim();
      quickAddError.style.display = 'none';
      if (!code) return;

      var body = new URLSearchParams();
      body.set('code', code);
      body.set('price', quickAddPrice.value || '0');
      body.set('_csrf', csrfToken);

      quickAddBtn.disabled = true;
      fetch(quickAddUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          quickAddBtn.disabled = false;
          if (!data.ok) {
            quickAddError.textContent = data.error || msgError;
            quickAddError.style.display = '';
            return;
          }
          addLotRow(data.lot);
          quickAddCode.value = '';
          quickAddPrice.value = '';
          quickAddCode.focus();
          statusEl.textContent = msgQuickAddSuccess;
        })
        .catch(function () {
          quickAddBtn.disabled = false;
          quickAddError.textContent = msgError;
          quickAddError.style.display = '';
        });
    }

    quickAddBtn.addEventListener('click', submitQuickAdd);
    [quickAddCode, quickAddPrice].forEach(function (input) {
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          submitQuickAdd();
        }
      });
    });

    photo.addEventListener('click', function (e) {
      if (!armedLotId) return;
      var point = pointFromEvent(e);
      placePin(armedLotId, armedLotCode, point.x, point.y);
      savePosition(armedLotId, point.x, point.y);
      refreshRotateHandle();
    });

    // Dragging an existing pin (mouse or a finger on a touchscreen) re-positions and
    // re-saves it without needing to re-arm it first.
    function beginPinDrag(pin, lotId, startEvt) {
      if (startEvt.cancelable) startEvt.preventDefault();

      function onMove(moveEvt) {
        if (moveEvt.cancelable) moveEvt.preventDefault();
        var point = pointFromEvent(moveEvt);
        pin.style.left = point.x + '%';
        pin.style.top = point.y + '%';
      }

      function onEnd(endEvt) {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onEnd);
        document.removeEventListener('touchmove', onMove);
        document.removeEventListener('touchend', onEnd);
        document.removeEventListener('touchcancel', onEnd);
        var point = pointFromEvent(endEvt);
        savePosition(lotId, point.x, point.y);
      }

      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onEnd);
      document.addEventListener('touchmove', onMove, { passive: false });
      document.addEventListener('touchend', onEnd);
      document.addEventListener('touchcancel', onEnd);
    }

    // --- Rotation handle: only shown on the currently armed lot's marker, and only
    // when it's a box (pins don't rotate). Dragging it computes the angle from the
    // box's own centre to the pointer, so the box tilts to match an angled parking
    // space freely, in any direction — not locked to fixed steps.
    var rotateHandle = null;

    function removeRotateHandle() {
      if (rotateHandle && rotateHandle.parentNode) rotateHandle.parentNode.removeChild(rotateHandle);
      rotateHandle = null;
    }

    function refreshRotateHandle() {
      removeRotateHandle();
      if (!armedLotId) return;
      var pin = pinsLayer.querySelector('[data-lot-id="' + armedLotId + '"]');
      if (!pin || !pin.classList.contains('shape-box')) return;

      rotateHandle = document.createElement('div');
      rotateHandle.className = 'map-editor-rotate-handle';
      rotateHandle.setAttribute('data-lot-id', armedLotId);
      pin.appendChild(rotateHandle);
    }

    function beginRotateDrag(pin, lotId, startEvt) {
      if (startEvt.cancelable) startEvt.preventDefault();

      function angleFromEvent(evt) {
        var p = clientPoint(evt);
        var rect = pin.getBoundingClientRect();
        var centerX = rect.left + rect.width / 2;
        var centerY = rect.top + rect.height / 2;
        var deg = Math.atan2(p.clientY - centerY, p.clientX - centerX) * (180 / Math.PI) + 90;
        if (deg > 180) deg -= 360;
        if (deg < -180) deg += 360;
        return deg;
      }

      function onMove(moveEvt) {
        if (moveEvt.cancelable) moveEvt.preventDefault();
        var deg = angleFromEvent(moveEvt);
        pin.setAttribute('data-rotation', deg.toFixed(1));
        pinTransform(pin);
      }

      function onEnd(endEvt) {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onEnd);
        document.removeEventListener('touchmove', onMove);
        document.removeEventListener('touchend', onEnd);
        document.removeEventListener('touchcancel', onEnd);
        var deg = angleFromEvent(endEvt);
        pin.setAttribute('data-rotation', deg.toFixed(1));
        pinTransform(pin);
        saveRotation(lotId, deg);
      }

      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onEnd);
      document.addEventListener('touchmove', onMove, { passive: false });
      document.addEventListener('touchend', onEnd);
      document.addEventListener('touchcancel', onEnd);
    }

    pinsLayer.addEventListener('mousedown', function (e) {
      var handle = e.target.closest('.map-editor-rotate-handle');
      if (handle) {
        var box = handle.closest('.map-editor-pin');
        beginRotateDrag(box, box.getAttribute('data-lot-id'), e);
        return;
      }
      var pin = e.target.closest('.map-editor-pin');
      if (!pin) return;
      armLot(pin.getAttribute('data-lot-id'), pin.textContent);
      beginPinDrag(pin, pin.getAttribute('data-lot-id'), e);
    });

    pinsLayer.addEventListener('touchstart', function (e) {
      var handle = e.target.closest('.map-editor-rotate-handle');
      if (handle) {
        var box = handle.closest('.map-editor-pin');
        beginRotateDrag(box, box.getAttribute('data-lot-id'), e);
        return;
      }
      var pin = e.target.closest('.map-editor-pin');
      if (!pin) return;
      armLot(pin.getAttribute('data-lot-id'), pin.textContent);
      beginPinDrag(pin, pin.getAttribute('data-lot-id'), e);
    }, { passive: false });
  })();
  </script>
<?php endif; ?>
