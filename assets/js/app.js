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
