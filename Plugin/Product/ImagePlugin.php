<?php
/**
 * @author Rollpix
 * @package Rollpix_ImageFlipHover
 */
declare(strict_types=1);

namespace Rollpix\ImageFlipHover\Plugin\Product;

use Rollpix\ImageFlipHover\Helper\Config;
use Rollpix\ImageFlipHover\Model\ImageFlipService;
use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;

class ImagePlugin
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ImageFlipService
     */
    private ImageFlipService $imageFlipService;

    /**
     * @param Config $config
     * @param ImageFlipService $imageFlipService
     */
    public function __construct(
        Config $config,
        ImageFlipService $imageFlipService
    ) {
        $this->config = $config;
        $this->imageFlipService = $imageFlipService;
    }

    /**
     * Add flip/slider image data to the image block
     *
     * @param ImageFactory $subject
     * @param ImageBlock $result
     * @param Product $product
     * @param string $imageId
     * @param array $attributes
     * @return ImageBlock
     */
    public function afterCreate(
        ImageFactory $subject,
        ImageBlock $result,
        Product $product,
        string $imageId,
        array $attributes = []
    ): ImageBlock {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        // Only inject on configured listing surfaces. Skips cart/minicart/wishlist/compare/
        // checkout/PDP — rendering the slider there breaks those thumbnails (IS-6110/IS-6453)
        // and also avoids the gallery DB queries for contexts that never show the slider.
        if (!$this->config->isEnabledForImageId($imageId)) {
            return $result;
        }

        // Always attach slider data — handles both slider mode and flip mode
        // (flip on desktop = slider with hoverFlip, flip on mobile = auto-upgrade to slider)
        return $this->attachSliderData($result, $product, $imageId);
    }

    /**
     * Attach flip image data (existing behavior)
     */
    private function attachFlipData(ImageBlock $result, Product $product, string $imageId): ImageBlock
    {
        $flipImageData = $this->imageFlipService->getFlipImageData($product, $imageId);

        if ($flipImageData['hasFlipImage']) {
            $result->setData('flip_image_url', $flipImageData['flipImageUrl']);
            $result->setData('flip_animation_type', $flipImageData['animationType']);
            $result->setData('flip_animation_speed', $flipImageData['animationSpeed']);
            $result->setData('has_flip_image', true);
        }

        return $result;
    }

    /**
     * Attach slider gallery data
     */
    private function attachSliderData(ImageBlock $result, Product $product, string $imageId): ImageBlock
    {
        $sliderData = $this->imageFlipService->getSliderImageData($product, $imageId);

        if ($sliderData['hasSliderImages']) {
            $result->setData('slider_mode', true);
            $result->setData('gallery_urls', $sliderData['galleryUrls']);
            $result->setData('image_count', $sliderData['imageCount']);
            $result->setData('has_flip_image', true); // Trigger block plugin
        }

        return $result;
    }
}
