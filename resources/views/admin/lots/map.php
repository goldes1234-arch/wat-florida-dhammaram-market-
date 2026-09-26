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

    <div class="map-editor-list">
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

  <script>
  (function () {
    var photo = document.getElementById('mapEditorPhoto');
    var pinsLayer = document.getElementById('mapEditorPins');
    var statusEl = document.getElementById('mapEditorStatus');
    var saveUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-position')) ?>;
    var sizeUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-size')) ?>;
    var shapeUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-shape')) ?>;
    var rotationUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-rotation')) ?>;
    var csrfToken = <?= json_encode(\App\Core\Csrf::token()) ?>;
    var msgSaved = <?= json_encode(__('lot.map_saved'), JSON_UNESCAPED_UNICODE) ?>;
    var msgError = <?= json_encode(__('lot.map_save_error'), JSON_UNESCAPED_UNICODE) ?>;
    var lblPlaced = <?= json_encode(__('lot.map_placed'), JSON_UNESCAPED_UNICODE) ?>;

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

    document.querySelectorAll('.map-editor-lot-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        armLot(btn.getAttribute('data-lot-id'), btn.getAttribute('data-lot-code'));
      });
    });

    document.querySelectorAll('.map-editor-size-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        saveSize(btn.getAttribute('data-lot-id'), btn.getAttribute('data-size'));
      });
    });

    document.querySelectorAll('.map-editor-shape-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        saveShape(btn.getAttribute('data-lot-id'), btn.getAttribute('data-shape'));
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
