(function () {
  'use strict';

  // Auto-dismiss flash alerts after a few seconds.
  document.querySelectorAll('.alert[data-autodismiss]').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s ease';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 5000);
  });

  // Any form with data-confirm="message" asks before submitting.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.hasAttribute && form.hasAttribute('data-confirm')) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    }
  });

  // Photos straight off a phone are often 4-8MB — more than many hosts accept in one form post.
  // File inputs marked data-shrink-max="2400" get any image over ~1.5MB scaled down in the
  // browser (longest side capped at that many px, JPEG) the moment it is chosen; the server
  // still re-encodes it to its own final size.
  document.querySelectorAll('input[type="file"][data-shrink-max]').forEach(function (input) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      var max = parseInt(input.getAttribute('data-shrink-max'), 10);
      if (!file || file.size <= 1.5 * 1024 * 1024 || !window.createImageBitmap || !window.DataTransfer) return;
      createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
        var scale = Math.min(1, max / Math.max(bmp.width, bmp.height));
        var canvas = document.createElement('canvas');
        canvas.width = Math.round(bmp.width * scale);
        canvas.height = Math.round(bmp.height * scale);
        canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(function (blob) {
          if (!blob || blob.size >= file.size) return;
          var dt = new DataTransfer();
          dt.items.add(new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }));
          input.files = dt.files;
        }, 'image/jpeg', 0.9);
      }).catch(function () { /* keep the original file */ });
    });
  });

  // Generic sidebar/nav toggle: [data-toggle="#target"]
  document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.querySelector(btn.getAttribute('data-toggle'));
      if (target) {
        target.classList.toggle('is-open');
      }
    });
  });
})();
