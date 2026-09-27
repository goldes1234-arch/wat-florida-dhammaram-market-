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

  // Home page "our event atmosphere" carousel: auto-advancing slides + dot navigation.
  var galleryCarousel = document.getElementById('galleryCarousel');
  if (galleryCarousel) {
    var galleryTrack = galleryCarousel.querySelector('.gallery-carousel-track');
    var galleryDots = Array.prototype.slice.call(galleryCarousel.querySelectorAll('.gallery-carousel-dot'));
    var galleryCount = galleryTrack.children.length;
    var galleryIndex = 0;
    var galleryTimer = null;

    var galleryGoTo = function (index) {
      galleryIndex = (index + galleryCount) % galleryCount;
      galleryTrack.style.transform = 'translateX(-' + (galleryIndex * 100) + '%)';
      galleryDots.forEach(function (dot, i) { dot.classList.toggle('is-active', i === galleryIndex); });
    };

    var galleryStartAutoplay = function () {
      if (galleryTimer) clearInterval(galleryTimer);
      if (galleryCount > 1) {
        galleryTimer = setInterval(function () { galleryGoTo(galleryIndex + 1); }, 5000);
      }
    };

    galleryDots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        galleryGoTo(parseInt(dot.getAttribute('data-index'), 10));
        galleryStartAutoplay();
      });
    });

    galleryStartAutoplay();
  }

  // Interactive booth/photo map: zoom controls + live status polling.
  // Only one of the two canvases exists per page, depending on the event's layout mode.
  var canvas = document.getElementById('boothMapCanvas') || document.getElementById('photoMapCanvas');
  if (canvas) {
    var isPhotoMap = canvas.id === 'photoMapCanvas';
    // The grid map's cells are fixed 46px squares that never overlap by design, so a
    // plain CSS transform:scale() (cosmetic only — doesn't change layout) is fine for
    // it. The photo map's pins are positioned by percentage but sized in fixed px, so
    // scale() would enlarge the gaps between them and the pins themselves by the same
    // factor — nearby pins stay exactly as overlapped at any zoom level. Growing the
    // canvas's actual width instead spreads the percentage-based positions apart in
    // real pixels while the pins keep their fixed size, which is what actually
    // separates a tight cluster of markers as you zoom in.
    var photoBaseWidth = isPhotoMap ? canvas.getBoundingClientRect().width : null;
    var zoom = 1;
    var zoomLabel = document.getElementById('mapZoomLabel');
    var applyZoom = function () {
      if (isPhotoMap && photoBaseWidth) {
        canvas.style.width = (photoBaseWidth * zoom) + 'px';
      } else {
        canvas.style.transform = 'scale(' + zoom + ')';
      }
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
