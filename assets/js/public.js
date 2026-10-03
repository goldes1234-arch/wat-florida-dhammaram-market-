(function () {
  'use strict';

  // Nav "ดาวน์โหลด" dropdown — click to open/close (not hover-only, so it works
  // the same on touch as with a mouse) and closes on an outside click or Escape.
  document.querySelectorAll('.nav-dropdown-toggle').forEach(function (toggle) {
    var dropdown = toggle.closest('.nav-dropdown');
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = dropdown.classList.contains('is-open');
      document.querySelectorAll('.nav-dropdown.is-open').forEach(function (d) { d.classList.remove('is-open'); });
      dropdown.classList.toggle('is-open', !isOpen);
    });
  });
  document.addEventListener('click', function (e) {
    if (e.target.closest('.nav-dropdown')) return;
    document.querySelectorAll('.nav-dropdown.is-open').forEach(function (d) { d.classList.remove('is-open'); });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.nav-dropdown.is-open').forEach(function (d) { d.classList.remove('is-open'); });
    }
  });

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
    // Captured after the floorplan photo has actually finished loading, not just when
    // this script happens to run — on a slower connection (mobile data, in particular)
    // the <img> often hasn't loaded yet at that point, so getBoundingClientRect()
    // would measure the collapsed/broken-image box instead of the real rendered
    // width. Every zoom level is that wrong width times a factor, so the whole photo
    // map — and every pin's true position within it — renders too small and
    // misaligned, exactly the kind of thing that shows up on mobile but not on a
    // fast desktop connection where the image is already cached.
    var photoBaseWidth = null;
    var photoImg = isPhotoMap ? canvas.querySelector('.photo-map-image') : null;
    function capturePhotoBaseWidth() { photoBaseWidth = canvas.getBoundingClientRect().width; }
    if (photoImg) {
      if (photoImg.complete && photoImg.naturalWidth > 0) {
        capturePhotoBaseWidth();
      } else {
        photoImg.addEventListener('load', capturePhotoBaseWidth, { once: true });
      }
    }

    var zoom = 1;
    var zoomLabel = document.getElementById('mapZoomLabel');
    var applyZoom = function () {
      if (isPhotoMap) {
        if (!photoBaseWidth) capturePhotoBaseWidth();
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

  // Featured-shops carousel: arrows + gentle auto-advance (paused on hover/touch, off for reduced motion).
  var adsCarousel = document.getElementById('adsCarousel');
  if (adsCarousel) {
    var adsTrack = adsCarousel.querySelector('.ads-grid');
    var adsStep = function () {
      var card = adsTrack.querySelector('.ad-card');
      return card ? card.getBoundingClientRect().width + 16 : adsTrack.clientWidth;
    };
    var adsMove = function (dir) {
      var atEnd = adsTrack.scrollLeft + adsTrack.clientWidth >= adsTrack.scrollWidth - 4;
      if (dir > 0 && atEnd) {
        adsTrack.scrollTo({ left: 0 });
      } else if (dir < 0 && adsTrack.scrollLeft <= 4) {
        adsTrack.scrollTo({ left: adsTrack.scrollWidth });
      } else {
        adsTrack.scrollBy({ left: dir * adsStep() });
      }
    };
    var adsPrev = adsCarousel.querySelector('.ads-nav-prev');
    var adsNext = adsCarousel.querySelector('.ads-nav-next');
    if (adsPrev) adsPrev.addEventListener('click', function () { adsMove(-1); });
    if (adsNext) adsNext.addEventListener('click', function () { adsMove(1); });

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduceMotion) {
      var adsPaused = false;
      ['mouseenter', 'touchstart', 'focusin'].forEach(function (ev) {
        adsCarousel.addEventListener(ev, function () { adsPaused = true; }, { passive: true });
      });
      ['mouseleave', 'touchend', 'focusout'].forEach(function (ev) {
        adsCarousel.addEventListener(ev, function () { adsPaused = false; }, { passive: true });
      });
      setInterval(function () { if (!adsPaused && !document.hidden) adsMove(1); }, 4500);
    }
  }

  // Cards without a link open their photo in the lightbox; make them keyboard-activatable too.
  document.querySelectorAll('.ad-card[data-lightbox-src]').forEach(function (card) {
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
    });
  });

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
