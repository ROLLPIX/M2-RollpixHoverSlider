/**
 * @author Rollpix
 * @package Rollpix_ImageFlipHover
 *
 * Hover Slider - standalone build (no jQuery, no RequireJS).
 *
 * WHY THIS FILE EXISTS
 *
 * hover-slider.js is an AMD module that depends on jQuery, so it only runs on
 * themes that ship RequireJS (Luma and friends). Hyva has neither, so on a Hyva
 * storefront the server-side markup was rendered -- the containers, the gallery
 * JSON, the config -- and nothing ever picked it up: the cards showed a single
 * static image.
 *
 * This build is the same slider written against the DOM API. It boots itself as
 * a plain <script> and reads its settings from a JSON island, so it needs no
 * loader, no companion module and no theme override. The markup, the class
 * names and the CSS are the ones the Luma build already uses.
 *
 * Which of the two gets loaded is decided in ViewModel/ImageFlip::isHyvaTheme()
 * (see view/frontend/templates/js/init.phtml).
 */
(function () {
    'use strict';

    var INITIALIZED = 'slider-initialized';

    /**
     * Global settings (enabled, mode, desktopEnabled, mobileEnabled). The
     * per-container settings travel in data-slider-config instead, so a widget
     * rendered under a different store still carries its own.
     */
    function readConfig() {
        var island = document.getElementById('rollpix-imageflip-config');

        if (island) {
            try {
                return JSON.parse(island.textContent || '{}');
            } catch (e) {
                return {};
            }
        }

        return window.rollpixImageFlipConfig || {};
    }

    var config = readConfig();

    if (!config.enabled) {
        return;
    }

    var isTouchDevice = ('ontouchstart' in window) || window.matchMedia('(hover: none)').matches;
    var initTimeout = null;

    function shouldEnable() {
        return isTouchDevice ? config.mobileEnabled !== false : config.desktopEnabled !== false;
    }

    function parseJsonAttribute(el, name) {
        var raw = el.getAttribute(name);

        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function css(el, styles) {
        for (var prop in styles) {
            if (Object.prototype.hasOwnProperty.call(styles, prop)) {
                el.style[prop] = styles[prop];
            }
        }

        return el;
    }

    // =============================================
    // INIT
    // =============================================

    function initSliders() {
        if (!shouldEnable()) {
            return;
        }

        var containers = document.querySelectorAll('[data-hover-slider="true"]:not(.' + INITIALIZED + ')');

        for (var i = 0; i < containers.length; i++) {
            createSliderInstance(containers[i]);
        }
    }

    function createSliderInstance(container) {
        var images = parseJsonAttribute(container, 'data-gallery');
        var cfg = parseJsonAttribute(container, 'data-slider-config');

        if (!images || !cfg || images.length < 2) {
            return;
        }

        var deviceCfg = (isTouchDevice ? cfg.mobile : cfg.desktop) || {};
        var isFlipOnDesktop = config.mode === 'flip' && !isTouchDevice;

        // Flip on desktop: never show controls, only hover flip.
        // Slider on desktop: controls for 3+ images.
        // Mobile (any mode): always show controls (no hover available).
        var showControls = isFlipOnDesktop ? false : (isTouchDevice ? true : images.length > 2);

        var state = {
            container: container,
            viewport: container.querySelector('.hover-slider-viewport'),
            images: images,
            currentIndex: 0,
            cfg: cfg,
            deviceCfg: deviceCfg,
            slides: [],
            slidesCreated: false,
            isFlipOnDesktop: isFlipOnDesktop,
            lastInteraction: 0
        };

        if (!state.viewport) {
            return;
        }

        container.classList.add(INITIALIZED);

        // Preload into the browser cache so the first slide change does not flash.
        for (var i = 1; i < images.length; i++) {
            var preload = new Image();
            preload.src = images[i];
        }

        if (showControls) {
            var nav = deviceCfg.nav || '';

            if (nav.indexOf('arrows') !== -1) {
                createArrows(state);
            }

            if (nav.indexOf('mouse_tracking') !== -1 && !isTouchDevice) {
                container.classList.add('nav-mouse-tracking');
                initMouseTracking(state);
            }

            if (nav.indexOf('swipe') !== -1) {
                initSwipe(state);
            }

            createIndicators(state);
        }

        bindHoverEvents(state);
    }

    // =============================================
    // SLIDES - created on first interaction
    // =============================================

    function ensureSlidesCreated(state) {
        if (state.slidesCreated) {
            return;
        }
        state.slidesCreated = true;

        var viewport = state.viewport;
        var baseImg = viewport.querySelector('img');
        var alt = baseImg ? (baseImg.getAttribute('alt') || '') : '';
        var vpWidth = viewport.offsetWidth;
        var vpHeight = viewport.offsetHeight;
        var count = state.images.length;
        var speed = state.cfg.speed || 250;
        var transition = state.cfg.transition || 'fade';

        var baseCss = {
            width: vpWidth + 'px',
            height: vpHeight + 'px',
            objectFit: 'contain',
            position: 'static'
        };

        if (!baseImg) {
            return;
        }

        if (transition === 'slide') {
            // TRACK: images side by side, translateX moves the strip.
            var track = document.createElement('span');
            track.className = 'hover-slider-track';
            css(track, {
                display: 'flex',
                width: (vpWidth * count) + 'px',
                height: vpHeight + 'px',
                transition: 'transform ' + speed + 'ms ease-out'
            });

            css(baseImg, Object.assign({}, baseCss, { flex: 'none' }));
            track.appendChild(baseImg);
            state.slides.push(baseImg);

            for (var i = 1; i < count; i++) {
                var slide = document.createElement('img');
                slide.setAttribute('src', state.images[i]);
                slide.setAttribute('alt', alt);
                css(slide, Object.assign({}, baseCss, { flex: 'none' }));
                track.appendChild(slide);
                state.slides.push(slide);
            }

            viewport.innerHTML = '';
            viewport.appendChild(track);
            state.track = track;

            return;
        }

        // STACKED: base stays in flow, overlays sit absolutely on top.
        var baseStackedCss = Object.assign({}, baseCss);

        if (transition === 'fade') {
            baseStackedCss.transition = 'opacity ' + speed + 'ms ease';
        }
        css(baseImg, baseStackedCss);
        baseImg.classList.add('hover-slider-base');
        state.slides.push(baseImg);

        for (var j = 1; j < count; j++) {
            var overlayCss = Object.assign({}, baseCss, {
                position: 'absolute',
                top: 0,
                left: 0,
                opacity: 0,
                pointerEvents: 'none'
            });

            if (transition === 'fade') {
                overlayCss.transition = 'opacity ' + speed + 'ms ease';
            }

            var overlay = document.createElement('img');
            overlay.className = 'hover-slider-overlay';
            overlay.setAttribute('src', state.images[j]);
            overlay.setAttribute('alt', alt);
            css(overlay, overlayCss);
            viewport.appendChild(overlay);
            state.slides.push(overlay);
        }

        state.useOverlay = true;
    }

    function goToSlide(state, index) {
        var count = state.images.length;

        // Flip on desktop: restrict to image 0 and 1 only.
        if (state.isFlipOnDesktop) {
            index = Math.max(0, Math.min(1, index));
        } else if (state.cfg.loop) {
            index = ((index % count) + count) % count;
        } else {
            index = Math.max(0, Math.min(count - 1, index));
        }

        if (index === state.currentIndex && state.slidesCreated) {
            return;
        }

        ensureSlidesCreated(state);
        state.currentIndex = index;

        if (state.useOverlay) {
            var overlays = state.viewport.querySelectorAll('.hover-slider-overlay');

            for (var i = 0; i < overlays.length; i++) {
                css(overlays[i], { opacity: 0, pointerEvents: 'none' });
            }

            if (index > 0 && state.slides[index]) {
                css(state.slides[index], { opacity: 1, pointerEvents: 'auto' });
                state.slides[0].style.opacity = 0;
            } else {
                state.slides[0].style.opacity = 1;
            }
        } else if (state.track) {
            state.track.style.transform = 'translateX(-' + (index * state.viewport.offsetWidth) + 'px)';
        }

        updateIndicators(state);
        updateArrows(state);
    }

    // =============================================
    // HOVER
    // =============================================

    function bindHoverEvents(state) {
        state.container.addEventListener('mouseenter', function () {
            if (state.cfg.hoverFlip && state.currentIndex === 0) {
                goToSlide(state, 1);
            }
        });

        state.container.addEventListener('mouseleave', function () {
            // Skip if the user clicked an arrow/dot in the last 500ms, otherwise
            // the pointer leaving the card would undo what they just chose.
            if (Date.now() - state.lastInteraction > 500) {
                goToSlide(state, 0);
            }
        });
    }

    // =============================================
    // ARROWS
    // =============================================

    function createArrows(state) {
        var prevSvg = '<svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>';
        var nextSvg = '<svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>';

        var prev = document.createElement('button');
        prev.type = 'button';
        prev.className = 'hover-slider-arrow hover-slider-arrow--prev';
        prev.innerHTML = prevSvg;

        var next = document.createElement('button');
        next.type = 'button';
        next.className = 'hover-slider-arrow hover-slider-arrow--next';
        next.innerHTML = nextSvg;

        prev.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            state.lastInteraction = Date.now();
            goToSlide(state, state.currentIndex - 1);
        });

        next.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            state.lastInteraction = Date.now();
            goToSlide(state, state.currentIndex + 1);
        });

        state.container.appendChild(prev);
        state.container.appendChild(next);
        state.arrows = { prev: prev, next: next };
        updateArrows(state);
    }

    function updateArrows(state) {
        if (!state.arrows || state.cfg.loop) {
            return;
        }

        state.arrows.prev.classList.toggle('disabled', state.currentIndex === 0);
        state.arrows.next.classList.toggle('disabled', state.currentIndex === state.images.length - 1);
    }

    // =============================================
    // MOUSE TRACKING
    // =============================================

    function initMouseTracking(state) {
        var rafId = null;

        state.container.addEventListener('mousemove', function (e) {
            if (rafId) {
                return;
            }

            rafId = requestAnimationFrame(function () {
                var rect = state.container.getBoundingClientRect();
                var relativeX = (e.clientX - rect.left) / rect.width;
                var idx = Math.min(
                    state.images.length - 1,
                    Math.max(0, Math.floor(relativeX * state.images.length))
                );

                goToSlide(state, idx);
                rafId = null;
            });
        });
    }

    // =============================================
    // SWIPE
    // =============================================

    function initSwipe(state) {
        var startX = 0;
        var startY = 0;
        var dragging = false;
        var el = state.container;

        el.addEventListener('touchstart', function (e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            dragging = true;
        }, { passive: true });

        el.addEventListener('touchmove', function (e) {
            if (!dragging) {
                return;
            }

            var dx = Math.abs(e.touches[0].clientX - startX);
            var dy = Math.abs(e.touches[0].clientY - startY);

            // Only claim the gesture once it is clearly horizontal, so a vertical
            // scroll over a card still scrolls the page.
            if (dx > dy && dx > 10) {
                e.preventDefault();
            }
        }, { passive: false });

        el.addEventListener('touchend', function (e) {
            if (!dragging) {
                return;
            }
            dragging = false;

            var dx = e.changedTouches[0].clientX - startX;

            if (Math.abs(dx) > 30) {
                state.lastInteraction = Date.now();
                goToSlide(state, state.currentIndex + (dx < 0 ? 1 : -1));
            }
        }, { passive: true });
    }

    // =============================================
    // INDICATORS
    // =============================================

    function createIndicators(state) {
        var type = state.deviceCfg.indicator;
        var pos = state.deviceCfg.indicatorPos;

        if (!type || type === 'none') {
            return;
        }

        if (type === 'counter') {
            var counter = document.createElement('span');
            counter.className = 'hover-slider-counter '
                + (pos === 'top' ? 'hover-slider-counter--top' : 'hover-slider-counter--bottom');
            counter.textContent = '1/' + state.images.length;
            state.container.appendChild(counter);
            state.counter = counter;

            return;
        }

        var wrapper = document.createElement('span');
        wrapper.className = 'hover-slider-indicators indicator-' + type + ' hover-slider-indicators--' + pos;

        var clickable = (state.deviceCfg.nav || '').indexOf('dots_click') !== -1;

        for (var i = 0; i < state.images.length; i++) {
            var item = document.createElement('span');

            if (type === 'bars') {
                item.className = 'hover-slider-bar';
                item.style.width = (100 / state.images.length) + '%';
            } else if (type === 'dots') {
                item.className = 'hover-slider-dot';
            } else if (type === 'pills') {
                item.className = 'hover-slider-pill';
            }

            if (i === 0) {
                item.classList.add('active');
            }

            if (clickable) {
                item.classList.add('clickable');
                item.setAttribute('data-slide-index', i);
                bindIndicatorClick(state, item, i);
            }

            wrapper.appendChild(item);
        }

        state.container.appendChild(wrapper);
        state.indicators = wrapper;
    }

    function bindIndicatorClick(state, item, index) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            state.lastInteraction = Date.now();
            goToSlide(state, index);
        });
    }

    function updateIndicators(state) {
        if (state.indicators) {
            var items = state.indicators.children;

            for (var i = 0; i < items.length; i++) {
                items[i].classList.toggle('active', i === state.currentIndex);
            }
        }

        if (state.counter) {
            state.counter.textContent = (state.currentIndex + 1) + '/' + state.images.length;
        }
    }

    // =============================================
    // DYNAMIC CONTENT
    // =============================================

    function debouncedInit() {
        if (initTimeout) {
            clearTimeout(initTimeout);
        }
        initTimeout = setTimeout(initSliders, 100);
    }

    function observeDynamicContent() {
        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(function (muts) {
                for (var i = 0; i < muts.length; i++) {
                    if (muts[i].addedNodes.length) {
                        debouncedInit();

                        return;
                    }
                }
            }).observe(document.body, { childList: true, subtree: true });
        }

        // Hyva dispatches these after Alpine swaps a listing (layered navigation,
        // "load more", slider widgets). Harmless anywhere else.
        var events = ['private-content-loaded', 'hyva:navigate', 'catalog_product_list_loaded', 'contentUpdated'];

        for (var e = 0; e < events.length; e++) {
            document.addEventListener(events[e], debouncedInit);
            window.addEventListener(events[e], debouncedInit);
        }
    }

    function start() {
        initSliders();
        observeDynamicContent();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    window.rollpixHoverSlider = { init: initSliders };
}());
