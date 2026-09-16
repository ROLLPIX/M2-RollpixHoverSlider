# Rollpix Image Flip & Hover Slider for Magento 2

![Magento 2.4.x](https://img.shields.io/badge/Magento-2.4.x-orange?logo=magento)
![PHP 7.4–8.3](https://img.shields.io/badge/PHP-7.4–8.3-777BB4?logo=php&logoColor=white)
![Luma Compatible](https://img.shields.io/badge/Luma-Compatible-blue)
![Hyvä Compatible](https://img.shields.io/badge/Hyvä-Compatible-4DC0B5)
![License MIT](https://img.shields.io/badge/License-MIT-green)

**Sponsor: [www.rollpix.com](https://www.rollpix.com)**

> **[Leer en Español](README_es.md)**

---

## Overview

Browse product gallery images directly from the product listing page (PLP) without entering the product detail page. Two modes:

- **Flip** (classic): shows a single alternate image on hover
- **Slider** (v2.0): navigate all gallery images with arrows, swipe, mouse tracking, and click-on-indicator navigation

Compatible with **Luma** and **Hyvä** themes.

---

## Table of Contents

- [Features](#features)
- [Technical Requirements](#technical-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Configurable Products](#configurable-products)
- [Hyvä Theme Support](#hyvä-theme-support)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)
- [License](#license)

---

## Features

### Flip Mode
- Configurable image roles (native or custom) for the hover image
- "Second Gallery Image" option (no role assignment needed)
- Fallback role support
- 8 animation types: Fade, Slide (4 directions), Zoom, Flip Horizontal/Vertical

### Slider Mode (v2.0)
- Full gallery navigation from PLP
- **Navigation**: Arrows, Mouse Tracking (desktop), Swipe (mobile), Click on Indicators
- **Indicators**: Proportional Bars, Dots, Pills (elongated active dot), Counter (1/5), None
- **Transitions**: Slide (carousel), Fade (crossfade), Instant
- Independent desktop/mobile configuration
- Optional hover flip (auto-advance to image 2 on hover)
- Loop navigation and auto-return on mouse leave

### Configurable Products
- Collects images from all children (not just the first)
- **Images per variant** limit (e.g., 1 = one photo per color)
- **ConfigurableGallery integration**: filters parent images by variant when `Rollpix_ConfigurableGallery` is installed

### General
- Enable/disable per location (category pages, widgets, search, related, CMS, Page Builder)
- Lazy loading (images loaded on first interaction)
- Dynamic content support (AJAX, infinite scroll, sliders)
- Respects `prefers-reduced-motion` preference
- All CSS easily overridable from themes

---

## Technical Requirements

| Requirement | Version |
|---|---|
| **Module Name** | `rollpix/module-image-flip-hover` |
| **Magento** | 2.4.x |
| **PHP** | 7.4 - 8.3 |

---

## Installation

```bash
composer require rollpix/module-image-flip-hover:^2.0
bin/magento module:enable Rollpix_ImageFlipHover
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

That is all: Luma and Hyvä are both covered by these commands — there is no
companion module to enable.

---

## Configuration

Navigate to **Stores > Configuration > ROLLPIX > Image Flip Hover**.

### General Settings

| Field | Description | Default |
|---|---|---|
| Enable | Activate/deactivate the module | Yes |
| Hover Mode | **Flip** (single alternate image) or **Slider** (full gallery) | Flip |
| Desktop Only | Disable on mobile devices (< 768px) | Yes |

### Flip Mode Settings

| Field | Description | Default |
|---|---|---|
| Primary Image Role | Image role to show on hover | Second Gallery Image |
| Fallback Image Role | Used when primary role not found | Second Gallery Image |
| Animation Type | Transition effect | Fade |
| Animation Speed (ms) | Animation duration | 300 |

### Slider Mode Settings

| Field | Description | Default |
|---|---|---|
| Enable Hover Flip | Auto-advance to image 2 on hover | Yes |
| Transition Type | Slide / Fade / Instant | Fade |
| Transition Speed (ms) | Transition duration | 250 |
| Max Images | Maximum gallery images per product (2-20) | 8 |
| Images per Variant | For configurables: max images per child. 0 = all | 0 |
| Loop Navigation | Circular navigation (last → first) | No |
| Return to Main Image | Return to image 1 on mouse leave | Yes |

**Desktop Navigation** (multiselect): Arrows, Mouse Tracking, Click on Indicators

**Mobile Navigation** (multiselect): Arrows, Swipe, Click on Indicators

**Indicators** (per device): Bars, Dots, Pills, Counter, None — with position (top/bottom/below)

### Locations

Enable/disable for: Category Pages, Widgets, Search Results, Related/Upsell/Cross-sell, CMS Blocks, Page Builder.

---

## Configurable Products

The slider collects images from **all children** of a configurable product. Combined with the **Images per Variant** setting:

| Setting | Result (3 colors × 7 photos each) |
|---|---|
| `0` (all) | Shows up to 8 images (max_images cap) |
| `1` | Shows 3 images (first photo of each color) |
| `2` | Shows 6 images (first 2 of each color) |

### ConfigurableGallery Integration

When `Rollpix_ConfigurableGallery` is installed, the slider reads the `associated_attributes` column to group parent images by variant. This works when images are uploaded to the configurable parent and associated to colors via ConfigurableGallery's admin UI.

---

## Hyvä Theme Support

Hyvä works out of the box. The module ships two builds of the same slider and
picks one from the active theme:

| Theme | Build | How it loads |
|---|---|---|
| Luma and derivatives | `js/hover-slider.js` (AMD + jQuery) | `x-magento-init` |
| Hyvä and derivatives | `js/hover-slider-standalone.js` (vanilla) | plain `<script defer>` |

Detection walks the theme inheritance chain, so a child theme of `Hyva/default`
is recognised as Hyvä. Markup, CSS classes and every feature — flip, slider, all
navigation types, all indicators — are the same in both.

> **Deprecated**: `Rollpix_ImageFlipHoverHyvaCompat` used to carry the vanilla
> build and had to be enabled by hand. Since 2.2.0 it does nothing; leave it
> enabled or disabled, it makes no difference.

---

## Troubleshooting

### Flip/slider not showing
1. Verify the module is enabled and the location is active
2. Check the product has 2+ images in its gallery
3. Clear cache: `bin/magento cache:flush`

### Images not loading on hover
1. Clear static content: `bin/magento setup:static-content:deploy -f`
2. Check browser console for JS errors

### Slider images cropped or misaligned
1. Clear `var/view_preprocessed/` and redeploy static content
2. Check for CSS conflicts with your theme

### Error after update
```bash
rm -rf generated/code/*
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full version history.

---

## License

MIT License

---

## Support

- **Issues**: https://github.com/ROLLPIX/M2-ImageFlipHover/issues
- **Website**: [www.rollpix.com](https://www.rollpix.com)

---

Made with ❤️ by [ROLLPIX](https://www.rollpix.com)
