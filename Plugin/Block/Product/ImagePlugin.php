<?php
/**
 * @author Rollpix
 * @package Rollpix_ImageFlipHover
 */
declare(strict_types=1);

namespace Rollpix\ImageFlipHover\Plugin\Block\Product;

use Rollpix\ImageFlipHover\Helper\Config;
use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\Framework\Serialize\Serializer\Json;

class ImagePlugin
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Json
     */
    private Json $jsonSerializer;

    /**
     * @param Config $config
     * @param Json $jsonSerializer
     */
    public function __construct(Config $config, Json $jsonSerializer)
    {
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Override the template for product image block when flip image is available
     *
     * @param ImageBlock $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(ImageBlock $subject, string $result): string
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        if (!$subject->getData('has_flip_image')) {
            return $result;
        }

        // Always inject slider HTML (handles both modes)
        if ($subject->getData('slider_mode')) {
            return $this->handleSliderMode($subject, $result);
        }

        return $result;
    }

    /**
     * Handle slider mode injection
     */
    private function handleSliderMode(ImageBlock $subject, string $result): string
    {
        $galleryUrls = $subject->getData('gallery_urls') ?: [];
        $imageCount = $subject->getData('image_count') ?: 0;

        if ($imageCount < 2) {
            return $result;
        }

        return $this->injectSliderHtml($result, $galleryUrls);
    }

    /**
     * Inject slider HTML into product image output
     */
    private function injectSliderHtml(string $html, array $galleryUrls): string
    {
        if (empty($galleryUrls)) {
            return $html;
        }

        $galleryJson = htmlspecialchars($this->jsonSerializer->serialize($galleryUrls), ENT_QUOTES, 'UTF-8');
        $sliderConfig = $this->buildSliderConfig();
        $configJson = htmlspecialchars($this->jsonSerializer->serialize($sliderConfig), ENT_QUOTES, 'UTF-8');
        $transitionSpeed = $this->config->getTransitionSpeed();

        $sliderAttrs = 'data-hover-slider="true" '
            . 'data-gallery="' . $galleryJson . '" '
            . 'data-slider-config="' . $configJson . '" '
            . 'style="--slider-transition-speed: ' . $transitionSpeed . 'ms;"';

        // Match the base product image regardless of attribute order (Luma + Hyvä).
        $pattern = '/(<img\s[^>]*class="product-image-photo"[^>]*>)/s';
        if (!preg_match($pattern, $html, $matches)) {
            return $html;
        }
        $originalImg = $matches[1];

        // Render the first gallery image as the visible/base image so the default thumbnail
        // and slide 0 are gallery_urls[0] (the first variant) instead of the product's
        // small_image. The JS reuses this <img> as slide 0, so this alone fixes the
        // dropped-first-variant and duplicate-base-color bugs with no JS change. For simple
        // products gallery_urls[0] == small_image, so it is a visual no-op. (IS-6453)
        $newImg = $this->rewriteBaseImg($originalImg, (string) $galleryUrls[0]);

        if (strpos($html, 'product-image-container') !== false) {
            // Luma path: tag the existing container, then wrap the (rewritten) img in a viewport
            $html = preg_replace(
                '/class="product-image-container([^"]*)"/',
                'class="product-image-container$1 has-hover-slider" ' . $sliderAttrs,
                $html
            );
            $sliderContainer = '<span class="hover-slider-viewport">' . $newImg . '</span>';
        } else {
            // Hyvä path: no wrapper container, wrap the img directly
            $sliderContainer = '<span class="has-hover-slider" ' . $sliderAttrs . '>'
                . '<span class="hover-slider-viewport">' . $newImg . '</span>'
                . '</span>';
        }

        return str_replace($originalImg, $sliderContainer, $html);
    }

    /**
     * Set the <img>'s src to the given URL and strip attributes that could override it on the
     * client (srcset/sizes responsive candidates, data-src lazy-load placeholders).
     *
     * @param string $imgTag
     * @param string $url
     * @return string
     */
    private function rewriteBaseImg(string $imgTag, string $url): string
    {
        if ($url === '') {
            return $imgTag;
        }

        $newSrc = 'src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';

        if (preg_match('/\bsrc="[^"]*"/', $imgTag, $m)) {
            $imgTag = str_replace($m[0], $newSrc, $imgTag);
        } else {
            $imgTag = preg_replace('/<img\b/', '<img ' . $newSrc, $imgTag, 1);
        }

        // Drop attributes that could re-override the src after first paint.
        $imgTag = preg_replace('/\s(?:srcset|sizes|data-src)="[^"]*"/', '', $imgTag);

        return $imgTag;
    }

    /**
     * Build slider config array from admin settings
     */
    private function buildSliderConfig(): array
    {
        $isFlip = $this->config->isFlipMode();
        return [
            'hoverFlip' => $isFlip ? true : $this->config->isHoverFlipEnabled(),
            'transition' => $isFlip ? 'fade' : $this->config->getTransitionType(),
            'speed' => $isFlip ? $this->config->getAnimationSpeed() : $this->config->getTransitionSpeed(),
            'loop' => $isFlip ? false : $this->config->isLoopEnabled(),
            'desktop' => [
                'nav' => $this->config->getDesktopNavigation(),
                'indicator' => $this->config->getDesktopIndicator(),
                'indicatorPos' => $this->config->getDesktopIndicatorPosition()
            ],
            'mobile' => [
                'nav' => $this->config->getMobileNavigation(),
                'indicator' => $this->config->getMobileIndicator(),
                'indicatorPos' => $this->config->getMobileIndicatorPosition()
            ]
        ];
    }

    /**
     * Inject flip image into existing product image HTML
     */
    private function injectFlipImage(
        string $html,
        string $flipImageUrl,
        string $animationType,
        int $animationSpeed
    ): string {
        $flipAttrs = 'data-flip-image="true" '
            . 'data-flip-url="' . htmlspecialchars($flipImageUrl) . '" '
            . 'data-animation-type="' . htmlspecialchars($animationType) . '" '
            . 'data-animation-speed="' . $animationSpeed . '" '
            . 'style="--flip-animation-speed: ' . $animationSpeed . 'ms;"';

        $hasContainer = strpos($html, 'product-image-container') !== false;

        if ($hasContainer) {
            // Luma path: add attributes to existing container
            $html = preg_replace(
                '/class="product-image-container([^"]*)"/',
                'class="product-image-container$1 has-flip-image flip-animation-' . htmlspecialchars($animationType) . '" '
                . $flipAttrs,
                $html
            );
        }

        // Find the img tag and wrap it with flip container, adding the flip image
        $pattern = '/(<img\s[^>]*class="product-image-photo"[^>]*>)/s';

        if (preg_match($pattern, $html, $matches)) {
            $originalImg = $matches[1];

            // Create the modified primary image
            $primaryImg = str_replace(
                'class="product-image-photo"',
                'class="product-image-photo primary-image"',
                $originalImg
            );

            // Extract attributes for flip image
            $width = $this->extractAttribute($originalImg, 'width');
            $height = $this->extractAttribute($originalImg, 'height');
            $alt = $this->extractAttribute($originalImg, 'alt');

            // Create flip image
            $flipImg = sprintf(
                '<img class="product-image-photo flip-image" data-src="%s" loading="lazy" width="%s" height="%s" alt="%s - %s"/>',
                htmlspecialchars($flipImageUrl),
                htmlspecialchars($width),
                htmlspecialchars($height),
                htmlspecialchars($alt),
                htmlspecialchars((string) __('Alternate View'))
            );

            if ($hasContainer) {
                // Luma: wrap inside existing container
                $flipContainer = '<span class="flip-image-container">' . $primaryImg . $flipImg . '</span>';
            } else {
                // Hyvä: create outer container with data attrs
                $flipContainer = '<span class="has-flip-image flip-animation-' . htmlspecialchars($animationType) . '" ' . $flipAttrs . '>'
                    . '<span class="flip-image-container">' . $primaryImg . $flipImg . '</span>'
                    . '</span>';
            }

            $html = str_replace($originalImg, $flipContainer, $html);
        }

        return $html;
    }

    /**
     * Add flip data-attributes to existing slider container for desktop flip JS
     */
    private function injectFlipDataAttributes(string $html, ImageBlock $subject): string
    {
        $flipUrl = $subject->getData('flip_image_url');
        $animationType = $subject->getData('flip_animation_type') ?: 'fade';
        $animationSpeed = $subject->getData('flip_animation_speed') ?: 300;

        $flipAttrs = 'data-flip-image="true" '
            . 'data-flip-url="' . htmlspecialchars($flipUrl) . '" '
            . 'data-animation-type="' . htmlspecialchars($animationType) . '" '
            . 'data-animation-speed="' . $animationSpeed . '"';

        // Add to the has-hover-slider container if it exists
        if (strpos($html, 'has-hover-slider') !== false) {
            $html = str_replace('data-hover-slider="true"', 'data-hover-slider="true" ' . $flipAttrs, $html);
        } elseif (strpos($html, 'product-image-container') !== false) {
            // Fallback: add to product-image-container
            $html = preg_replace(
                '/class="product-image-container([^"]*)"/',
                'class="product-image-container$1 has-flip-image flip-animation-' . htmlspecialchars($animationType) . '" ' . $flipAttrs,
                $html
            );
        }

        return $html;
    }

    /**
     * Extract attribute value from HTML tag
     */
    private function extractAttribute(string $html, string $attribute): string
    {
        $pattern = '/' . preg_quote($attribute) . '="([^"]*)"/';
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
