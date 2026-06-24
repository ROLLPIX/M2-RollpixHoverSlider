<?php
/**
 * @author Rollpix
 * @package Rollpix_ImageFlipHover
 */
declare(strict_types=1);

namespace Rollpix\ImageFlipHover\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    // General
    private const XML_PATH_ENABLED = 'rollpix_imageflip/general/enabled';
    private const XML_PATH_MODE = 'rollpix_imageflip/general/mode';

    // Flip mode
    private const XML_PATH_PRIMARY_ROLE = 'rollpix_imageflip/flip/primary_role';
    private const XML_PATH_FALLBACK_ROLE = 'rollpix_imageflip/flip/fallback_role';
    private const XML_PATH_ANIMATION_TYPE = 'rollpix_imageflip/flip/animation_type';
    private const XML_PATH_ANIMATION_SPEED = 'rollpix_imageflip/flip/animation_speed';

    // Slider mode
    private const XML_PATH_ENABLE_HOVER_FLIP = 'rollpix_imageflip/hover_slider/enable_hover_flip';
    private const XML_PATH_TRANSITION_TYPE = 'rollpix_imageflip/hover_slider/transition_type';
    private const XML_PATH_TRANSITION_SPEED = 'rollpix_imageflip/hover_slider/transition_speed';
    private const XML_PATH_MAX_IMAGES = 'rollpix_imageflip/hover_slider/max_images';
    private const XML_PATH_CONFIGURABLE_IMAGES_PER_CHILD = 'rollpix_imageflip/hover_slider/configurable_images_per_child';
    private const XML_PATH_LOOP = 'rollpix_imageflip/hover_slider/loop';

    // Desktop
    private const XML_PATH_DESKTOP_ENABLED = 'rollpix_imageflip/desktop/enabled';
    private const XML_PATH_DESKTOP_NAVIGATION = 'rollpix_imageflip/desktop/navigation';
    private const XML_PATH_DESKTOP_INDICATOR = 'rollpix_imageflip/desktop/indicator';
    private const XML_PATH_DESKTOP_INDICATOR_POSITION = 'rollpix_imageflip/desktop/indicator_position';

    // Mobile
    private const XML_PATH_MOBILE_ENABLED = 'rollpix_imageflip/mobile/enabled';
    private const XML_PATH_MOBILE_NAVIGATION = 'rollpix_imageflip/mobile/navigation';
    private const XML_PATH_MOBILE_INDICATOR = 'rollpix_imageflip/mobile/indicator';
    private const XML_PATH_MOBILE_INDICATOR_POSITION = 'rollpix_imageflip/mobile/indicator_position';

    // Locations
    private const XML_PATH_CATEGORY_PAGE = 'rollpix_imageflip/locations/category_page';
    private const XML_PATH_WIDGET_PRODUCTS = 'rollpix_imageflip/locations/widget_products';
    private const XML_PATH_SEARCH_RESULTS = 'rollpix_imageflip/locations/search_results';
    private const XML_PATH_RELATED_PRODUCTS = 'rollpix_imageflip/locations/related_products';
    private const XML_PATH_CMS_BLOCKS = 'rollpix_imageflip/locations/cms_blocks';
    private const XML_PATH_PAGE_BUILDER = 'rollpix_imageflip/locations/page_builder';

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get primary image role
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPrimaryRole(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PRIMARY_ROLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get fallback image role
     *
     * @param int|null $storeId
     * @return string
     */
    public function getFallbackRole(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_FALLBACK_ROLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get animation type
     *
     * @param int|null $storeId
     * @return string
     */
    public function getAnimationType(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_ANIMATION_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get animation speed in milliseconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getAnimationSpeed(?int $storeId = null): int
    {
        $speed = (int) $this->scopeConfig->getValue(
            self::XML_PATH_ANIMATION_SPEED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $speed > 0 ? $speed : 300;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isDesktopEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DESKTOP_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isMobileEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @deprecated Use isDesktopEnabled() and isMobileEnabled() instead
     * @param int|null $storeId
     * @return bool
     */
    public function isDesktopOnly(?int $storeId = null): bool
    {
        return $this->isDesktopEnabled($storeId) && !$this->isMobileEnabled($storeId);
    }

    /**
     * Check if enabled for category pages
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForCategoryPage(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATEGORY_PAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if enabled for widget products
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForWidgetProducts(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_WIDGET_PRODUCTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if enabled for search results
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForSearchResults(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_SEARCH_RESULTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if enabled for related products
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForRelatedProducts(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_RELATED_PRODUCTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if enabled for CMS blocks
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForCmsBlocks(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_CMS_BLOCKS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if enabled for Page Builder
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForPageBuilder(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_PAGE_BUILDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Decide whether the slider/flip should be injected for a given image block context.
     *
     * Maps Magento's image_id (defined per theme in view.xml) to the locations config.
     * Transactional, account and PDP contexts are always skipped — the slider belongs on
     * listings, never on cart/minicart/wishlist/compare/checkout or the product page gallery
     * (rendering it there is what broke cart thumbnails — see IS-6110/IS-6453).
     *
     * Note: in Luma, search results reuse the category_page_* image ids, so search cannot be
     * gated independently of category by image id alone — search follows category here.
     *
     * @param string $imageId
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForImageId(string $imageId, ?int $storeId = null): bool
    {
        if (!$this->isEnabled($storeId)) {
            return false;
        }

        $id = strtolower($imageId);

        // Transactional / account / PDP contexts: never inject the slider.
        foreach (['cart', 'wishlist', 'compar', 'checkout', 'order', 'gift', 'product_page'] as $needle) {
            if (strpos($id, $needle) !== false) {
                return false;
            }
        }

        // Listing contexts: honour the locations config.
        if (strpos($id, 'category_page') !== false) {
            // Search results reuse the category image ids in Luma — treat as category.
            return $this->isEnabledForCategoryPage($storeId);
        }
        if (strpos($id, 'related') !== false
            || strpos($id, 'upsell') !== false
            || strpos($id, 'crosssell') !== false) {
            return $this->isEnabledForRelatedProducts($storeId);
        }

        // Unknown listing-like contexts (widgets, CMS, Page Builder, custom grids).
        return $this->isEnabledForWidgetProducts($storeId);
    }

    /**
     * Get hover mode (flip or slider)
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMode(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'flip';
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isSliderMode(?int $storeId = null): bool
    {
        return $this->getMode($storeId) === 'slider';
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isFlipMode(?int $storeId = null): bool
    {
        return $this->getMode($storeId) === 'flip';
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isHoverFlipEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_HOVER_FLIP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getTransitionType(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TRANSITION_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'fade';
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getTransitionSpeed(?int $storeId = null): int
    {
        $speed = (int) $this->scopeConfig->getValue(
            self::XML_PATH_TRANSITION_SPEED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $speed > 0 ? $speed : 250;
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getMaxImages(?int $storeId = null): int
    {
        $max = (int) $this->scopeConfig->getValue(
            self::XML_PATH_MAX_IMAGES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return max(2, min(20, $max ?: 8));
    }

    /**
     * Max images per child/variant for configurable products. 0 = all.
     *
     * @param int|null $storeId
     * @return int
     */
    public function getConfigurableImagesPerChild(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_CONFIGURABLE_IMAGES_PER_CHILD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isLoopEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LOOP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getDesktopNavigation(?int $storeId = null): array
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_DESKTOP_NAVIGATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ? explode(',', $value) : ['arrows'];
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getDesktopIndicator(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_DESKTOP_INDICATOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'bars';
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getDesktopIndicatorPosition(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_DESKTOP_INDICATOR_POSITION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'top';
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getMobileNavigation(?int $storeId = null): array
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_MOBILE_NAVIGATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ? explode(',', $value) : ['swipe'];
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getMobileIndicator(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MOBILE_INDICATOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'bars';
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getMobileIndicatorPosition(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MOBILE_INDICATOR_POSITION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'top';
    }

    /**
     * Get all configuration as array for frontend
     *
     * @param int|null $storeId
     * @return array
     */
    public function getConfigArray(?int $storeId = null): array
    {
        $config = [
            'enabled' => $this->isEnabled($storeId),
            'mode' => $this->getMode($storeId),
            'desktopEnabled' => $this->isDesktopEnabled($storeId),
            'mobileEnabled' => $this->isMobileEnabled($storeId),
            'locations' => [
                'categoryPage' => $this->isEnabledForCategoryPage($storeId),
                'widgetProducts' => $this->isEnabledForWidgetProducts($storeId),
                'searchResults' => $this->isEnabledForSearchResults($storeId),
                'relatedProducts' => $this->isEnabledForRelatedProducts($storeId),
                'cmsBlocks' => $this->isEnabledForCmsBlocks($storeId),
                'pageBuilder' => $this->isEnabledForPageBuilder($storeId)
            ]
        ];

        // Slider settings (always included — flip mode uses them for mobile auto-upgrade)
        $config['hoverFlip'] = $this->isFlipMode($storeId) ? true : $this->isHoverFlipEnabled($storeId);
        $config['transitionType'] = $this->isFlipMode($storeId) ? 'fade' : $this->getTransitionType($storeId);
        $config['transitionSpeed'] = $this->isFlipMode($storeId) ? $this->getAnimationSpeed($storeId) : $this->getTransitionSpeed($storeId);
        $config['maxImages'] = $this->getMaxImages($storeId);
        $config['loop'] = $this->isLoopEnabled($storeId);

        $config['desktop'] = [
            'navigation' => $this->getDesktopNavigation($storeId),
            'indicator' => $this->getDesktopIndicator($storeId),
            'indicatorPosition' => $this->getDesktopIndicatorPosition($storeId)
        ];
        $config['mobile'] = [
            'navigation' => $this->getMobileNavigation($storeId),
            'indicator' => $this->getMobileIndicator($storeId),
            'indicatorPosition' => $this->getMobileIndicatorPosition($storeId)
        ];

        return $config;
    }
}
