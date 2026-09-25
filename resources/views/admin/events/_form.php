<?php
/** @var array|null $event */
/** @var array $contacts */
$e = $event ?? [];
$dtLocal = static fn (?string $v) => $v ? str_replace(' ', 'T', substr($v, 0, 16)) : '';
$contactRows = $contacts ?? [];
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header"><h3><?= __('event.singular') ?></h3></div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('event.name_th') ?></label>
        <input type="text" name="name_th" class="form-control" value="<?= e($e['name_th'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label><?= __('event.name_en') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
        <input type="text" name="name_en" class="form-control" value="<?= e($e['name_en'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('event.description_th') ?></label>
        <textarea name="description_th" class="form-control"><?= e($e['description_th'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label><?= __('event.description_en') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
        <textarea name="description_en" class="form-control"><?= e($e['description_en'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="form-group">
      <label><?= __('event.venue') ?></label>
      <input type="text" name="venue_name" class="form-control" value="<?= e($e['venue_name'] ?? '') ?>">
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('event.start_date') ?> / <?= __('event.booking_open_at') ?></h3></div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('event.start_date') ?></label>
        <input type="date" name="start_date" class="form-control" value="<?= e($e['start_date'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label><?= __('event.end_date') ?></label>
        <input type="date" name="end_date" class="form-control" value="<?= e($e['end_date'] ?? '') ?>" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('event.booking_open_at') ?></label>
        <input type="datetime-local" name="booking_open_at" class="form-control" value="<?= e($dtLocal($e['booking_open_at'] ?? null)) ?>" required>
      </div>
      <div class="form-group">
        <label><?= __('event.booking_close_at') ?></label>
        <input type="datetime-local" name="booking_close_at" class="form-control" value="<?= e($dtLocal($e['booking_close_at'] ?? null)) ?>" required>
      </div>
    </div>
    <div class="checkbox-row">
      <input type="checkbox" id="is_published" name="is_published" value="1" <?= !empty($e['is_published']) ? 'checked' : '' ?>>
      <label for="is_published" style="margin:0;"><?= __('event.published') ?></label>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('event.contacts_title') ?></h3></div>
    <p class="form-hint mb-4"><?= __('event.contacts_hint') ?></p>

    <?php if (!empty($copyableEvents)): ?>
      <div class="form-row copy-contacts-row">
        <div class="form-group" style="flex:2 1 260px;">
          <label><?= __('event.copy_contacts_label') ?></label>
          <select id="copyContactsSource" class="form-control">
            <option value=""><?= __('event.copy_contacts_placeholder') ?></option>
            <?php foreach ($copyableEvents as $ce): ?>
              <option value="<?= (int) $ce['id'] ?>"><?= e($ce['name_th']) ?> (<?= e(date('d/m/Y', strtotime($ce['start_date']))) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="flex:0 0 auto;align-self:flex-end;">
          <button type="button" id="copyContactsBtn" class="btn btn-secondary"><?= __('event.copy_contacts_button') ?></button>
        </div>
        <div class="form-group" style="flex:1 1 140px;align-self:flex-end;">
          <span id="copyContactsStatus" class="text-sm text-muted"></span>
        </div>
      </div>
    <?php endif; ?>

    <?php for ($i = 0; $i < 4; $i++): ?>
      <?php $c = $contactRows[$i] ?? ['name' => '', 'phone' => '', 'contact_channel' => '']; ?>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('event.contact_name') ?> #<?= $i + 1 ?></label>
          <input type="text" name="contact_name[]" class="form-control" value="<?= e($c['name']) ?>">
        </div>
        <div class="form-group">
          <label><?= __('event.contact_phone') ?></label>
          <input type="text" name="contact_phone[]" class="form-control" value="<?= e($c['phone']) ?>">
        </div>
        <div class="form-group">
          <label><?= __('event.contact_channel') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
          <input type="text" name="contact_channel[]" class="form-control" value="<?= e($c['contact_channel'] ?? '') ?>" placeholder="<?= e(__('event.contact_channel_hint')) ?>">
        </div>
      </div>
    <?php endfor; ?>
  </div>

  <?php if (!empty($copyableEvents)): ?>
  <script>
  (function () {
    var btn = document.getElementById('copyContactsBtn');
    var select = document.getElementById('copyContactsSource');
    var status = document.getElementById('copyContactsStatus');
    var baseUrl = <?= json_encode(base_url('admin/events')) ?>;
    var msgLoading = <?= json_encode(__('event.copy_contacts_loading'), JSON_UNESCAPED_UNICODE) ?>;
    var msgDone = <?= json_encode(__('event.copy_contacts_done'), JSON_UNESCAPED_UNICODE) ?>;
    var msgError = <?= json_encode(__('event.copy_contacts_error'), JSON_UNESCAPED_UNICODE) ?>;
    var msgPick = <?= json_encode(__('event.copy_contacts_pick_first'), JSON_UNESCAPED_UNICODE) ?>;

    btn.addEventListener('click', function () {
      var eventId = select.value;
      if (!eventId) {
        status.textContent = msgPick;
        return;
      }
      status.textContent = msgLoading;
      fetch(baseUrl + '/' + eventId + '/contacts', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var contacts = data.contacts || [];
          var names = document.querySelectorAll('input[name="contact_name[]"]');
          var phones = document.querySelectorAll('input[name="contact_phone[]"]');
          var channels = document.querySelectorAll('input[name="contact_channel[]"]');
          for (var i = 0; i < names.length; i++) {
            names[i].value = contacts[i] ? contacts[i].name : '';
            phones[i].value = contacts[i] ? contacts[i].phone : '';
            channels[i].value = (contacts[i] && contacts[i].contact_channel) ? contacts[i].contact_channel : '';
          }
          status.textContent = msgDone;
        })
        .catch(function () {
          status.textContent = msgError;
        });
    });
  })();
  </script>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><h3><?= __('event.banner_image') ?> / <?= __('event.floorplan_image') ?></h3></div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('event.banner_image') ?></label>
        <?php if (!empty($e['banner_image'])): ?>
          <img src="<?= upload_url($e['banner_image']) ?>" class="thumb-md mb-2" alt="">
          <div class="checkbox-row mb-2">
            <input type="checkbox" id="remove_banner_image" name="remove_banner_image" value="1">
            <label for="remove_banner_image" style="margin:0;"><?= __('event.remove_image') ?></label>
          </div>
        <?php endif; ?>
        <input type="file" name="banner_image" class="form-control" accept="image/jpeg,image/png,image/webp">
        <p class="form-hint"><?= __('event.banner_image_hint') ?></p>
      </div>
      <div class="form-group">
        <label><?= __('event.floorplan_image') ?></label>
        <?php if (!empty($e['floorplan_image'])): ?>
          <img src="<?= upload_url($e['floorplan_image']) ?>" class="thumb-md mb-2" alt="">
          <div class="checkbox-row mb-2">
            <input type="checkbox" id="remove_floorplan_image" name="remove_floorplan_image" value="1">
            <label for="remove_floorplan_image" style="margin:0;"><?= __('event.remove_image') ?></label>
          </div>
        <?php endif; ?>
        <input type="file" name="floorplan_image" class="form-control" accept="image/jpeg,image/png,image/webp">
        <p class="form-hint"><?= __('event.floorplan_image_hint') ?></p>
      </div>
    </div>
    <div class="form-group">
      <label><?= __('event.layout_mode') ?></label>
      <select name="layout_mode" class="form-control">
        <option value="grid" <?= ($e['layout_mode'] ?? 'grid') === 'grid' ? 'selected' : '' ?>><?= __('event.layout_mode_grid') ?></option>
        <option value="photo" <?= ($e['layout_mode'] ?? 'grid') === 'photo' ? 'selected' : '' ?>><?= __('event.layout_mode_photo') ?></option>
      </select>
      <p class="form-hint"><?= __('event.layout_mode_hint') ?></p>
    </div>
  </div>

  <div style="display:flex;gap:10px;">
    <button type="submit" class="btn btn-primary btn-lg"><?= e($submitLabel) ?></button>
    <a href="<?= base_url('admin/events') ?>" class="btn btn-secondary btn-lg"><?= __('common.cancel') ?></a>
  </div>
</form>

<?php if (!empty($e['id'])): ?>
  <?php $photos = $eventPhotos ?? []; $photoCount = count($photos); $photosFull = $photoCount >= \App\Models\EventPhoto::MAX_PER_EVENT; ?>
  <div class="card">
    <div class="card-header"><h3><?= __('event.photos_section') ?></h3></div>
    <p class="form-hint mb-4"><?= __('event.photos_hint', ['max' => \App\Models\EventPhoto::MAX_PER_EVENT]) ?></p>

    <?php if ($photos): ?>
      <div class="grid grid-cols-4 mb-4">
        <?php foreach ($photos as $photo): ?>
          <div class="card" style="padding:10px;">
            <img src="<?= upload_url($photo['image_path']) ?>" class="thumb-sm mb-2" style="width:100%;height:90px;" alt="">
            <button type="submit" form="event-photo-delete-<?= (int) $photo['id'] ?>" class="btn btn-danger btn-sm" style="width:100%;"><?= __('common.delete') ?></button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($photosFull): ?>
      <p class="form-hint"><?= __('event.photos_limit_full', ['max' => \App\Models\EventPhoto::MAX_PER_EVENT]) ?></p>
    <?php else: ?>
      <div class="form-row" style="align-items:flex-end;">
        <div class="form-group">
          <label><?= __('event.photos_add_button') ?></label>
          <input type="file" name="photo" form="event-photo-add-form" class="form-control" accept="image/jpeg,image/png,image/webp" required>
        </div>
        <div class="form-group" style="flex:0 0 auto;">
          <button type="submit" form="event-photo-add-form" class="btn btn-primary"><?= __('event.photos_add_button') ?></button>
        </div>
      </div>
      <p class="form-hint"><?= __('event.photos_limit_note', ['remaining' => \App\Models\EventPhoto::MAX_PER_EVENT - $photoCount, 'max' => \App\Models\EventPhoto::MAX_PER_EVENT]) ?></p>
    <?php endif; ?>
  </div>

  <form id="event-photo-add-form" method="post" action="<?= base_url('admin/events/' . $e['id'] . '/photos') ?>" enctype="multipart/form-data" style="display:none;"><?= csrf_field() ?></form>
  <?php foreach ($photos as $photo): ?>
    <form id="event-photo-delete-<?= (int) $photo['id'] ?>" method="post" action="<?= base_url('admin/events/' . $e['id'] . '/photos/' . $photo['id'] . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>" style="display:none;"><?= csrf_field() ?></form>
  <?php endforeach; ?>
<?php endif; ?>
