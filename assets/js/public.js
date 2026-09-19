(function () {
  'use strict';

  // Lightbox for the floor-plan / banner preview images.
  var lightbox = document.getElementById('lightbox');
  var lightboxImg = lightbox ? lightbox.querySelector('img') : null;

  document.querySelectorAll('[data-lightbox-src]').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      if (!lightbox || !lightboxImg) return;
      lightboxImg.src = trigger.getAttribute('data-lightbox-src');
      lightbox.classList.add('is-open');
    });
  });

  if (lightbox) {
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox || e.target.closest('.lightbox-close')) {
        lightbox.classList.remove('is-open');
        lightboxImg.src = '';
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        lightbox.classList.remove('is-open');
      }
    });
  }

  // Interactive booth map: zoom controls + live status polling.
  var canvas = document.getElementById('boothMapCanvas');
  if (canvas) {
    var zoom = 1;
    var zoomLabel = document.getElementById('mapZoomLabel');
    var applyZoom = function () {
      canvas.style.transform = 'scale(' + zoom + ')';
      if (zoomLabel) zoomLabel.textContent = Math.round(zoom * 100) + '%';
    };
    var zoomInBtn = document.getElementById('mapZoomIn');
    var zoomOutBtn = document.getElementById('mapZoomOut');
    var zoomResetBtn = document.getElementById('mapZoomReset');
    if (zoomInBtn) zoomInBtn.addEventListener('click', function () { zoom = Math.min(2.5, zoom + 0.2); applyZoom(); });
    if (zoomOutBtn) zoomOutBtn.addEventListener('click', function () { zoom = Math.max(0.5, zoom - 0.2); applyZoom(); });
    if (zoomResetBtn) zoomResetBtn.addEventListener('click', function () { zoom = 1; applyZoom(); });

    var pollUrl = canvas.getAttribute('data-poll-url');
    if (pollUrl) {
      setInterval(function () {
        fetch(pollUrl)
          .then(function (r) { return r.json(); })
          .then(function (data) {
            var lots = data.lots || {};
            Object.keys(lots).forEach(function (lotId) {
              var cell = canvas.querySelector('[data-lot-id="' + lotId + '"]');
              if (!cell) return;
              cell.className = cell.className.replace(/status-[a-z_]+/, 'status-' + lots[lotId]);
            });
          })
          .catch(function () { /* offline or server hiccup — just skip this tick */ });
      }, 8000);
    }
  }

  // Payment method cards: clicking anywhere on the card selects its radio.
  document.querySelectorAll('.payment-option').forEach(function (option) {
    var radio = option.querySelector('input[type="radio"]');
    if (!radio) return;

    function refresh() {
      document.querySelectorAll('.payment-option').forEach(function (o) {
        o.classList.toggle('is-selected', o.querySelector('input[type="radio"]').checked);
      });
    }

    option.addEventListener('click', function () {
      radio.checked = true;
      refresh();
    });
    radio.addEventListener('change', refresh);
    refresh();
  });
})();
