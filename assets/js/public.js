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

  // Mobile menu: the hamburger shows/hides the link panel.
  var navToggle = document.getElementById('navToggle');
  var navLinks = document.getElementById('navLinks');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      var open = navLinks.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Sticky "book a stall" bar: shown only after the element it watches (the hero buttons) leaves the screen.
  var mobileCta = document.getElementById('mobileCta');
  if (mobileCta && 'IntersectionObserver' in window) {
    var watched = document.querySelector(mobileCta.getAttribute('data-watch'));
    if (watched) {
      mobileCta.hidden = false;
      document.body.classList.add('has-mobile-cta');
      new IntersectionObserver(function (entries) {
        // Several records can arrive in one batch; only the latest one reflects where the element is now.
        var latest = entries[entries.length - 1];
        // "Leaving upward" (we are below it) is the case we want; at the very top it must stay hidden.
        mobileCta.classList.toggle('is-visible', !latest.isIntersecting && latest.boundingClientRect.top < 0);
      }).observe(watched);
    }
  }

  // Lightbox for the floor-plan / banner / shop / gallery photos. Triggers that share a
  // data-lightbox-group form a set the viewer can step through (arrows, keyboard, swipe, thumbnail strip).
  // Pinch / double-tap / mouse-wheel zoom, drag to pan while zoomed, and a share button.
  var lightbox = document.getElementById('lightbox');
  var lightboxImg = lightbox ? lightbox.querySelector('img') : null;
  var lightboxStage = lightbox ? lightbox.querySelector('.lightbox-stage') : null;
  var lightboxCaption = lightbox ? lightbox.querySelector('.lightbox-caption') : null;
  var lightboxPrev = lightbox ? lightbox.querySelector('.lightbox-prev') : null;
  var lightboxNext = lightbox ? lightbox.querySelector('.lightbox-next') : null;
  var lightboxThumbs = lightbox ? lightbox.querySelector('.lightbox-thumbs') : null;
  var lightboxShare = lightbox ? lightbox.querySelector('.lightbox-share') : null;
  var lightboxItems = [];
  var lightboxIndex = 0;
  var zoom = { scale: 1, x: 0, y: 0 };
  var MAX_ZOOM = 4;

  function applyZoom() {
    lightboxImg.style.transform = 'translate(' + zoom.x + 'px,' + zoom.y + 'px) scale(' + zoom.scale + ')';
    lightboxStage.classList.toggle('is-zoomed', zoom.scale > 1);
  }
  function resetZoom() { zoom = { scale: 1, x: 0, y: 0 }; applyZoom(); }
  function clampPan() {
    var maxX = lightboxImg.clientWidth * (zoom.scale - 1) / 2;
    var maxY = lightboxImg.clientHeight * (zoom.scale - 1) / 2;
    zoom.x = Math.max(-maxX, Math.min(maxX, zoom.x));
    zoom.y = Math.max(-maxY, Math.min(maxY, zoom.y));
  }
  function setScale(next, originX, originY) {
    next = Math.max(1, Math.min(MAX_ZOOM, next));
    // Keep the point under the finger/cursor fixed while the scale changes.
    var rect = lightboxImg.getBoundingClientRect();
    var cx = rect.left + rect.width / 2, cy = rect.top + rect.height / 2;
    var ratio = next / zoom.scale;
    zoom.x = (originX - cx) * (1 - ratio) + zoom.x * ratio;
    zoom.y = (originY - cy) * (1 - ratio) + zoom.y * ratio;
    zoom.scale = next;
    if (next === 1) { zoom.x = 0; zoom.y = 0; }
    clampPan();
    applyZoom();
  }

  function lightboxCaptionText() {
    var item = lightboxItems[lightboxIndex];
    var caption = item.getAttribute('data-caption') || '';
    var counter = lightboxItems.length > 1 ? (lightboxIndex + 1) + ' / ' + lightboxItems.length : '';
    return [caption, counter].filter(Boolean).join('  ·  ');
  }

  function lightboxShow() {
    var item = lightboxItems[lightboxIndex];
    resetZoom();
    lightboxImg.src = item.getAttribute('data-lightbox-src');
    lightboxCaption.textContent = lightboxCaptionText();
    var multiple = lightboxItems.length > 1;
    lightboxPrev.hidden = !multiple;
    lightboxNext.hidden = !multiple;
    if (!lightboxThumbs.hidden) {
      Array.prototype.forEach.call(lightboxThumbs.children, function (btn, i) {
        var active = i === lightboxIndex;
        btn.classList.toggle('is-active', active);
        if (active && btn.scrollIntoView) btn.scrollIntoView({ block: 'nearest', inline: 'center' });
      });
    }
  }

  function lightboxBuildThumbs() {
    lightboxThumbs.innerHTML = '';
    var usable = lightboxItems.length > 1 && lightboxItems.every(function (it) { return it.getAttribute('data-thumb'); });
    lightboxThumbs.hidden = !usable;
    if (!usable) return;
    lightboxItems.forEach(function (it, i) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'lightbox-thumb';
      btn.setAttribute('data-i', String(i));
      var im = document.createElement('img');
      im.src = it.getAttribute('data-thumb');
      im.alt = '';
      im.loading = 'lazy';
      btn.appendChild(im);
      lightboxThumbs.appendChild(btn);
    });
  }

  function lightboxStep(delta) {
    if (lightboxItems.length < 2) return;
    lightboxIndex = (lightboxIndex + delta + lightboxItems.length) % lightboxItems.length;
    lightboxShow();
  }

  function lightboxClose() {
    lightbox.classList.remove('is-open');
    lightboxImg.src = '';
    resetZoom();
  }

  document.querySelectorAll('[data-lightbox-src]').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      if (!lightbox || !lightboxImg) return;
      var group = trigger.getAttribute('data-lightbox-group');
      lightboxItems = group
        ? Array.prototype.slice.call(document.querySelectorAll('[data-lightbox-group="' + group + '"]'))
        : [trigger];
      lightboxIndex = Math.max(0, lightboxItems.indexOf(trigger));
      lightboxBuildThumbs();
      lightboxShow();
      lightbox.classList.add('is-open');
    });
  });

  if (lightbox) {
    lightbox.addEventListener('click', function (e) {
      if (e.target.closest('.lightbox-prev')) { lightboxStep(-1); return; }
      if (e.target.closest('.lightbox-next')) { lightboxStep(1); return; }
      var thumb = e.target.closest('.lightbox-thumb');
      if (thumb) { lightboxIndex = parseInt(thumb.getAttribute('data-i'), 10); lightboxShow(); return; }
      if (e.target.closest('.lightbox-share')) { return; }
      if (e.target === lightbox || e.target === lightboxStage || e.target.closest('.lightbox-close') || e.target.classList.contains('lightbox-figure')) {
        lightboxClose();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (!lightbox.classList.contains('is-open')) return;
      if (e.key === 'Escape') lightboxClose();
      if (e.key === 'ArrowLeft' && zoom.scale === 1) lightboxStep(-1);
      if (e.key === 'ArrowRight' && zoom.scale === 1) lightboxStep(1);
    });

    // Share: native share sheet on phones, otherwise copy the image link.
    lightboxShare.addEventListener('click', function () {
      var item = lightboxItems[lightboxIndex];
      var url = new URL(item.getAttribute('data-lightbox-src'), window.location.href).href;
      if (navigator.share) {
        navigator.share({ title: document.title, text: item.getAttribute('data-caption') || '', url: url }).catch(function () {});
      } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function () {
          lightboxCaption.textContent = lightboxShare.getAttribute('data-copied');
          setTimeout(function () { lightboxCaption.textContent = lightboxCaptionText(); }, 1600);
        });
      }
    });

    // Gestures on the image: pinch to zoom, double-tap to zoom in/out, drag to pan, swipe to change photo.
    var pointers = {};
    var pinchStartDist = 0, pinchStartScale = 1;
    var swipeStartX = 0, swipeStartY = 0, swipeActive = false, moved = 0;
    var lastTap = { time: 0, x: 0, y: 0 };
    function dist() {
      var ids = Object.keys(pointers);
      var a = pointers[ids[0]], b = pointers[ids[1]];
      return Math.hypot(a.x - b.x, a.y - b.y);
    }
    lightboxStage.addEventListener('pointerdown', function (e) {
      pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
      lightboxStage.setPointerCapture(e.pointerId);
      moved = 0;
      if (Object.keys(pointers).length === 2) { pinchStartDist = dist(); pinchStartScale = zoom.scale; swipeActive = false; }
      else { swipeActive = true; swipeStartX = e.clientX; swipeStartY = e.clientY; }
    });
    lightboxStage.addEventListener('pointermove', function (e) {
      var p = pointers[e.pointerId];
      if (!p) return;
      var dx = e.clientX - p.x, dy = e.clientY - p.y;
      moved += Math.abs(dx) + Math.abs(dy);
      p.x = e.clientX; p.y = e.clientY;
      if (Object.keys(pointers).length === 2) {
        var ids = Object.keys(pointers);
        var mx = (pointers[ids[0]].x + pointers[ids[1]].x) / 2, my = (pointers[ids[0]].y + pointers[ids[1]].y) / 2;
        setScale(pinchStartScale * dist() / pinchStartDist, mx, my);
      } else if (zoom.scale > 1) {
        zoom.x += dx; zoom.y += dy;
        clampPan();
        applyZoom();
      }
    });
    function endPointer(e) {
      var wasSingle = Object.keys(pointers).length === 1;
      delete pointers[e.pointerId];
      if (!wasSingle) return;
      var dx = e.clientX - swipeStartX, dy = e.clientY - swipeStartY;
      if (moved < 10) {
        // a tap: second one within 300ms on roughly the same spot toggles zoom
        var now = Date.now();
        if (now - lastTap.time < 300 && Math.hypot(e.clientX - lastTap.x, e.clientY - lastTap.y) < 30) {
          setScale(zoom.scale > 1 ? 1 : 2.5, e.clientX, e.clientY);
          lastTap.time = 0;
        } else {
          lastTap = { time: now, x: e.clientX, y: e.clientY };
        }
      } else if (swipeActive && zoom.scale === 1 && Math.abs(dx) > 50 && Math.abs(dy) < 70) {
        lightboxStep(dx < 0 ? 1 : -1);
      }
      swipeActive = false;
    }
    lightboxStage.addEventListener('pointerup', endPointer);
    lightboxStage.addEventListener('pointercancel', function (e) { delete pointers[e.pointerId]; swipeActive = false; });
    lightboxStage.addEventListener('wheel', function (e) {
      e.preventDefault();
      setScale(zoom.scale * (e.deltaY < 0 ? 1.15 : 1 / 1.15), e.clientX, e.clientY);
    }, { passive: false });
  }

  var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Home mosaic: the big tile cross-fades through its photos (paused while hovered/focused).
  var rotator = document.getElementById('mosaicRotator');
  if (rotator && !prefersReducedMotion) {
    var slides = Array.prototype.slice.call(rotator.querySelectorAll('.mosaic-slide'));
    var slideIndex = 0, rotatorPaused = false;
    ['mouseenter', 'focusin'].forEach(function (ev) { rotator.addEventListener(ev, function () { rotatorPaused = true; }); });
    ['mouseleave', 'focusout'].forEach(function (ev) { rotator.addEventListener(ev, function () { rotatorPaused = false; }); });
    setInterval(function () {
      if (rotatorPaused || document.hidden || slides.length < 2) return;
      slides[slideIndex].classList.remove('is-active');
      slideIndex = (slideIndex + 1) % slides.length;
      slides[slideIndex].classList.add('is-active');
    }, 5000);
  }

  // Photos fade in once loaded instead of popping in (only when JS is running, so nothing stays hidden without it).
  document.querySelectorAll('.mosaic-tile img, .photo-grid-item img, .event-card-media img, .ad-card-media img').forEach(function (im) {
    im.classList.add('fade-img');
    if (im.complete) { im.classList.add('is-loaded'); return; }
    im.addEventListener('load', function () { im.classList.add('is-loaded'); });
    im.addEventListener('error', function () { im.classList.add('is-loaded'); });
  });

  // Hero numbers count up once when they scroll into view.
  var counters = document.querySelectorAll('[data-count]');
  if (counters.length && 'IntersectionObserver' in window && !prefersReducedMotion) {
    var countObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        countObserver.unobserve(entry.target);
        var el = entry.target, target = parseInt(el.getAttribute('data-count'), 10), startTime = null;
        if (!(target > 0)) return;
        function frame(t) {
          if (startTime === null) startTime = t;
          var progress = Math.min(1, (t - startTime) / 900);
          el.textContent = String(Math.round(target * (1 - Math.pow(1 - progress, 3))));
          if (progress < 1) requestAnimationFrame(frame);
        }
        el.textContent = '0';
        requestAnimationFrame(frame);
      });
    });
    counters.forEach(function (el) { countObserver.observe(el); });
  }

  // Sections ease in as they scroll into view; the class is removed afterwards so hover effects still work.
  if ('IntersectionObserver' in window && !prefersReducedMotion) {
    var revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        revealObserver.unobserve(entry.target);
        var el = entry.target;
        el.classList.add('in');
        setTimeout(function () { el.classList.remove('reveal', 'in'); el.style.transitionDelay = ''; }, 900);
      });
    }, { threshold: 0.08 });
    var revealGroups = {};
    document.querySelectorAll('.how-it-works-step, .event-card, .faq-item, .visitor-strip, .follow-banner, .section-title').forEach(function (el) {
      var parent = el.parentElement;
      var key = parent.__revealKey || (parent.__revealKey = Math.random());
      revealGroups[key] = (revealGroups[key] || 0) + 1;
      el.style.transitionDelay = Math.min(revealGroups[key] - 1, 5) * 70 + 'ms';
      el.classList.add('reveal');
      revealObserver.observe(el);
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
