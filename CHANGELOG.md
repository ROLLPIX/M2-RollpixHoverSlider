# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.0] - 2026-06-25

### Added
- Slider mode: new admin setting **"Atributos que Diferencian Imágenes"** (`hover_slider/variant_selector_attributes`, multiselect). For configurable products the slider now keeps one representative variant per distinct value of the first configured attribute that is a variation axis of the product (e.g. one image set per *color*), so size-only variants (talle) no longer repeat the same photos. Empty = legacy behavior (images of every variant). This embeds the variant-selector mechanic previously provided by the Rollpix ConfigurableGallery module, without depending on it or on per-image color mapping. (IS-6421)

## [2.0.3] - 2026-06-24

### Fixed
- Configurable products (slider mode): the first gallery image is no longer dropped and the base-image color no longer appears twice. The main `<img>` is now rendered server-side as `gallery_urls[0]` (the first variant), so the default thumbnail and slide 0 match the slider — no flash, no JS change. For simple products `gallery_urls[0] == small_image`, so it is a visual no-op. (IS-6453)

### Changed
- The slider is now injected only on configured listing surfaces. `Config::isEnabledForImageId()` maps the image block's `image_id` to the `locations/*` config (previously declared but never consulted) and hard-skips cart/minicart/wishlist/compare/checkout/PDP contexts. This fixes product thumbnails not loading on the cart and supersedes the IS-6110 CSS workaround (kept as a fallback). Note: in Luma, search results reuse the category image ids, so `search_results` follows `category_page`. (IS-6453)

## [2.0.2] - 2026-06-22

### Fixed
- Cart: product thumbnails for items with multiple images no longer collapse to `height: 0`. The hover-slider viewport is kept in flow on the cart so the base image defines the height (overlays stay absolute on top). PLP/PDP unchanged. (IS-6110)

## [2.0.1] - 2026-04-12

### Changed
- **Admin config reorganization**: separated Flip and Slider settings into dedicated groups with cross-group visibility dependencies
- Replaced `Desktop Only` toggle with independent `Enable Desktop` and `Enable Mobile` toggles
- Config paths reorganized: `general/primary_role` → `flip/primary_role`, desktop/mobile settings moved to their own groups
- Flip mode on mobile auto-upgrades to slider (touch has no hover)
- `hover-slider.js` now handles all cases (flip + slider, desktop + mobile)
- README rewritten for public repo: EN + ES, Rollpix format with compatibility badges

### Fixed
- Mouseleave auto-return to first image now has 500ms debounce to prevent accidental return when clicking edge arrows
- Flip mode desktop: no controls shown, only hover to image 2 with fade
- Slider mode: navigation restricted to image 0-1 only in desktop flip mode
- README and README_es.md with Rollpix standard format (sponsor, bilingual links, badges)

### Removed
- `auto_return` admin config option (always active now with smart debouncing)

## [2.0.0] - 2026-04-10

### Added
- **Slider mode**: new "Slider de galería" mode that allows browsing all product gallery images from PLP with arrows, mouse tracking, swipe, and click-on-indicator navigation
- **Independent desktop/mobile config**: separate navigation methods and indicator styles for each device
- **Navigation types**: Arrows, Mouse Tracking (desktop), Swipe (mobile), Click on Indicators
- **Indicator types**: Proportional Bars, Dots, Pills (elongated active dot), Counter (1/5), None
- **Transition types**: Slide (carousel), Fade (crossfade), Instant (no animation)
- **Configurable product support**: collect images from all children, not just the first child
- **Images per variant**: configurable max images per child/color (e.g., 1 = one photo per color)
- **ConfigurableGallery integration**: reads `associated_attributes` to filter parent images by variant when Rollpix_ConfigurableGallery is installed
- **Hyvä compatibility**: `Rollpix_ImageFlipHoverHyvaCompat` sub-module with vanilla JS (no jQuery/RequireJS)
- **Hover flip in slider mode**: optional auto-advance to image 2 on hover, with full gallery navigation available
- **Loop navigation**: optional circular navigation (last → first)
- **Auto return**: optionally return to first image on mouse leave
- Admin config: 12 new fields in "Slider Configuration" group
- Source models: `DesktopNavigationType`, `MobileNavigationType`, `IndicatorType`, `IndicatorPosition`, `TransitionType`, `HoverMode`

### Changed
- Version bump to 2.0.0 (significant feature addition, opted for major version to prevent automatic composer updates on existing installations)
- CSS selectors changed from `.product-image-container.has-flip-image` to `.has-flip-image` for Hyvä compatibility
- `ImageFlipService`: added batch gallery preload (`preloadGalleryBatch`) for single-query performance
- `CollectionPlugin`: added `afterLoad()` for batch preloading in slider mode
- `ImagePlugin` (ImageFactory): branching by mode (flip/slider) in `afterCreate()`
- `Block\Product\ImagePlugin`: slider HTML injection with `data-gallery` and `data-slider-config` JSON attributes
- `Helper\Config`: 15 new getter methods for slider configuration
- Admin info block moved to last position (sortOrder 999)

### Fixed
- Mobile: indicators and controls always visible (no hover dependency on touch devices)
- Mobile: swipe enabled for all products with 2+ images
- Arrows: disabled state invisible without hover, dimmed on hover

## [1.3.5] - 2026-04-08

### Fixed
- Gallery fallback now finds the first image different from base instead of blindly using position #2. Fixes products where the base image is not at gallery position 1 (e.g., base image at position 2 was returned as the "second image" causing an invisible flip).

## [1.3.4] - 2026-04-08

### Fixed
- Skip invalid image values (`.tmp`, etc.) in flip image resolution. Products synced from external systems may have garbage file extensions in image role attributes, which prevented the fallback to second gallery image from triggering.

## [1.3.3] - 2026-04-08

### Fixed
- Skip flip image when it is the same file as the base image (was causing invisible flip on products where `image_on_hover` role pointed to the same photo as `image`)
- Gallery query now uses current store ID instead of only default store, with `GROUP BY` to prevent duplicate rows in multi-store setups
- Fix primary role bypassing placeholder detection
- Fix ModuleInfo constructor: add missing authSession parameter

### Added
- Module info group in admin config: module name, version (from composer.json), GitHub link

## [1.2.1] - 2026-04-07

### Fixed
- Fix flip image fallback not working for configurable products: gallery query missing `store_id` filter caused duplicate rows per store view, corrupting the `OFFSET` and returning the base image instead of the actual second gallery image
- Change default `fallback_role` from `small_image` to `second_image` for better out-of-the-box behavior

### Added
- Configurable product child fallback: when the parent configurable has no flip image, the module now tries child simple products as last resort

## [1.2.0] - 2025-12-20

### Added
- Desktop-only mode: new config option to restrict flip effect to screens wider than 768px

### Fixed
- Mobile click/tap issue when desktop-only mode is disabled

## [1.1.1] - 2025-12-15

### Fixed
- Keep module name untranslated in admin menu
- Fix admin menu: move config under ROLLPIX tab

## [1.1.0] - 2025-12-01

### Added
- Initial release
- Product image flip on hover for category pages, widgets, search results, related products, CMS blocks, and Page Builder
- Multiple animation types: fade, slide (left/right/up/down), zoom, flip (horizontal/vertical)
- Configurable primary and fallback image roles
- "Second Gallery Image" option for automatic flip image selection
- Custom `media_image` attribute auto-detection
- Dynamic content support: AJAX, sliders (Slick, Owl Carousel, Swiper), Amasty Infinite Scroll
- Admin configuration per store view
- Translation files: en_US, es_ES, es_AR (voseo)
