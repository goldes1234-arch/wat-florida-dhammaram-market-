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
            <div class="map-editor-pin" data-lot-id="<?= (int) $lot['id'] ?>"
                 style="left: <?= e($lot['map_x']) ?>%; top: <?= e($lot['map_y']) ?>%;"><?= e($lot['code']) ?></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="map-editor-list">
      <?php foreach ($lots as $lot): ?>
        <?php $placed = $lot['map_x'] !== null && $lot['map_y'] !== null; ?>
        <button type="button" class="map-editor-lot-btn<?= $placed ? ' is-placed' : '' ?>"
                data-lot-id="<?= (int) $lot['id'] ?>" data-lot-code="<?= e($lot['code']) ?>">
          <span class="lot-code"><?= e($lot['code']) ?></span>
          <span class="lot-map-status text-sm text-muted"><?= $placed ? __('lot.map_placed') : __('lot.map_unplaced') ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
  (function () {
    var photo = document.getElementById('mapEditorPhoto');
    var pinsLayer = document.getElementById('mapEditorPins');
    var statusEl = document.getElementById('mapEditorStatus');
    var saveUrl = <?= json_encode(base_url('admin/events/' . $event['id'] . '/lots/map-position')) ?>;
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

    function placePin(lotId, code, xPct, yPct) {
      var pin = pinsLayer.querySelector('[data-lot-id="' + lotId + '"]');
      if (!pin) {
        pin = document.createElement('div');
        pin.className = 'map-editor-pin';
        pin.setAttribute('data-lot-id', lotId);
        pin.textContent = code;
        pinsLayer.appendChild(pin);
      }
      pin.style.left = xPct + '%';
      pin.style.top = yPct + '%';
      return pin;
    }

    function markButtonPlaced(lotId) {
      var btn = document.querySelector('.map-editor-lot-btn[data-lot-id="' + lotId + '"]');
      if (!btn) return;
      btn.classList.add('is-placed');
      var statusSpan = btn.querySelector('.lot-map-status');
      if (statusSpan) statusSpan.textContent = lblPlaced;
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
          if (data.ok) markButtonPlaced(lotId);
        })
        .catch(function () { statusEl.textContent = msgError; });
    }

    document.querySelectorAll('.map-editor-lot-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.map-editor-lot-btn').forEach(function (b) { b.classList.remove('is-armed'); });
        btn.classList.add('is-armed');
        armedLotId = btn.getAttribute('data-lot-id');
        armedLotCode = btn.getAttribute('data-lot-code');
      });
    });

    photo.addEventListener('click', function (e) {
      if (!armedLotId) return;
      var point = pointFromEvent(e);
      placePin(armedLotId, armedLotCode, point.x, point.y);
      savePosition(armedLotId, point.x, point.y);
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

    pinsLayer.addEventListener('mousedown', function (e) {
      var pin = e.target.closest('.map-editor-pin');
      if (!pin) return;
      beginPinDrag(pin, pin.getAttribute('data-lot-id'), e);
    });

    pinsLayer.addEventListener('touchstart', function (e) {
      var pin = e.target.closest('.map-editor-pin');
      if (!pin) return;
      beginPinDrag(pin, pin.getAttribute('data-lot-id'), e);
    }, { passive: false });
  })();
  </script>
<?php endif; ?>
