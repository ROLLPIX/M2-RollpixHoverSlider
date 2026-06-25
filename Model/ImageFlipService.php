<?php
/**
 * @author Rollpix
 * @package Rollpix_ImageFlipHover
 */
declare(strict_types=1);

namespace Rollpix\ImageFlipHover\Model;

use Rollpix\ImageFlipHover\Helper\Config;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\StoreManagerInterface;

class ImageFlipService
{
    /**
     * @var array In-memory cache of gallery URLs keyed by product ID
     */
    private array $galleryCache = [];

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ImageHelper
     */
    private ImageHelper $imageHelper;

    /**
     * @var MediaConfig
     */
    private MediaConfig $mediaConfig;

    /**
     * @var AssetRepository
     */
    private AssetRepository $assetRepository;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param Config $config
     * @param ImageHelper $imageHelper
     * @param MediaConfig $mediaConfig
     * @param AssetRepository $assetRepository
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        ImageHelper $imageHelper,
        MediaConfig $mediaConfig,
        AssetRepository $assetRepository,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->imageHelper = $imageHelper;
        $this->mediaConfig = $mediaConfig;
        $this->assetRepository = $assetRepository;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    /**
     * Get flip image URL for a product
     *
     * @param ProductInterface|Product $product
     * @param string|null $imageId Optional image ID for resizing
     * @return string|null
     */
    public function getFlipImageUrl($product, ?string $imageId = null): ?string
    {
        if (!$this->config->isEnabled()) {
            return null;
        }

        $baseImage = $product->getData('image');
        $primaryRole = $this->config->getPrimaryRole();
        $fallbackRole = $this->config->getFallbackRole();

        // Try primary role first
        $imageUrl = $this->getImageByRoleOrSecond($product, $primaryRole, $imageId, $baseImage);

        // If no primary image found, try fallback role
        if (!$imageUrl && $fallbackRole && $fallbackRole !== $primaryRole) {
            $imageUrl = $this->getImageByRoleOrSecond($product, $fallbackRole, $imageId, $baseImage);
        }

        // For configurable products: try child simple products if parent has no flip image
        if (!$imageUrl && $product->getTypeId() === 'configurable') {
            $imageUrl = $this->getFlipImageFromChildren($product, $imageId);
        }

        return $imageUrl;
    }

    /**
     * Get image by role, or second gallery image if role is 'second_image'
     *
     * @param ProductInterface|Product $product
     * @param string $role
     * @param string|null $imageId
     * @return string|null
     */
    private function getImageByRoleOrSecond($product, string $role, ?string $imageId = null, ?string $baseImage = null): ?string
    {
        if (empty($role)) {
            return null;
        }

        // Special handling for "second_image" option
        if ($role === 'second_image') {
            $secondImage = $this->getAlternateGalleryImage($product, $baseImage);
            if ($secondImage) {
                return $this->buildImageUrl($product, $secondImage, $imageId);
            }
            return null;
        }

        // Regular role handling
        return $this->getImageUrlByRole($product, $role, $imageId, $baseImage);
    }

    /**
     * Build resized image URL
     *
     * @param ProductInterface|Product $product
     * @param string $imageValue
     * @param string|null $imageId
     * @return string|null
     */
    private function buildImageUrl($product, string $imageValue, ?string $imageId = null): ?string
    {
        try {
            $this->imageHelper->init($product, $imageId ?: 'category_page_list')
                ->setImageFile($imageValue);

            return $this->imageHelper->getUrl();
        } catch (\Exception $e) {
            return $this->mediaConfig->getMediaUrl($imageValue);
        }
    }

    /**
     * Get image URL by role
     *
     * @param ProductInterface|Product $product
     * @param string $role
     * @param string|null $imageId
     * @return string|null
     */
    private function getImageUrlByRole($product, string $role, ?string $imageId = null, ?string $baseImage = null): ?string
    {
        if (empty($role)) {
            return null;
        }

        // Get image value for the specified role
        $imageValue = $product->getData($role);

        // Check if image exists and is not the placeholder
        if (!$imageValue || $imageValue === 'no_selection') {
            // Try to find image in media gallery with this role
            $imageValue = $this->getImageFromGalleryByRole($product, $role);
        }

        if (!$imageValue || $imageValue === 'no_selection') {
            return null;
        }

        // Skip if flip image is the same as the base image
        if ($baseImage && $imageValue === $baseImage) {
            return null;
        }

        // Skip invalid image values (.tmp files, missing extensions, etc.)
        if (!$this->isValidImageValue($imageValue)) {
            return null;
        }

        // Generate resized image URL using image helper
        try {
            $this->imageHelper->init($product, $imageId ?: 'category_page_list')
                ->setImageFile($imageValue);

            return $this->imageHelper->getUrl();
        } catch (\Exception $e) {
            // If image helper fails, return direct URL
            return $this->mediaConfig->getMediaUrl($imageValue);
        }
    }

    /**
     * Check if an image value is a valid image file path
     *
     * @param string $imageValue
     * @return bool
     */
    private function isValidImageValue(string $imageValue): bool
    {
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $extension = strtolower(pathinfo($imageValue, PATHINFO_EXTENSION));

        return in_array($extension, $validExtensions, true);
    }

    /**
     * Get image from gallery by role (queries the database directly for custom roles)
     *
     * @param ProductInterface|Product $product
     * @param string $role
     * @return string|null
     */
    private function getImageFromGalleryByRole($product, string $role): ?string
    {
        $productId = $product->getId();
        if (!$productId) {
            return null;
        }

        // First, check if the role is stored as a product attribute value
        // This works for custom media_image attributes like rpx_product_image_on_hover
        $connection = $this->resourceConnection->getConnection();

        // Get the attribute ID for the role
        $eavAttributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $attributeId = $connection->fetchOne(
            $connection->select()
                ->from($eavAttributeTable, ['attribute_id'])
                ->where('attribute_code = ?', $role)
                ->where('entity_type_id = ?', 4) // catalog_product entity type
        );

        if ($attributeId) {
            // Check varchar table for the image value
            $varcharTable = $this->resourceConnection->getTableName('catalog_product_entity_varchar');
            $imageValue = $connection->fetchOne(
                $connection->select()
                    ->from($varcharTable, ['value'])
                    ->where('attribute_id = ?', $attributeId)
                    ->where('entity_id = ?', $productId)
                    ->where('value IS NOT NULL')
                    ->where('value != ?', 'no_selection')
                    ->where('value != ?', '')
            );

            if ($imageValue) {
                return $imageValue;
            }
        }

        // Fallback: check media gallery images collection
        $mediaGallery = $product->getMediaGalleryImages();

        if ($mediaGallery && $mediaGallery->getSize() > 0) {
            foreach ($mediaGallery as $image) {
                $types = $image->getData('types') ?? [];
                if (is_string($types)) {
                    $types = explode(',', $types);
                }

                // Check if this image has the requested role
                if (in_array($role, $types, true)) {
                    return $image->getData('file');
                }
            }
        }

        return null;
    }

    /**
     * Get the first gallery image that is different from the base image
     *
     * @param ProductInterface|Product $product
     * @param string|null $baseImage
     * @return string|null
     */
    private function getAlternateGalleryImage($product, ?string $baseImage = null): ?string
    {
        $productId = $product->getId();
        if (!$productId) {
            return null;
        }

        return $this->getAlternateGalleryImageByProductId((int) $productId, $baseImage);
    }

    /**
     * Get the first gallery image by position that is not the base image
     *
     * @param int $productId
     * @param string|null $baseImage Image path to exclude
     * @return string|null
     */
    private function getAlternateGalleryImageByProductId(int $productId, ?string $baseImage = null): ?string
    {
        $connection = $this->resourceConnection->getConnection();
        $galleryTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery');
        $galleryValueTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value');
        $galleryEntityTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value_to_entity');

        $storeId = (int) $this->storeManager->getStore()->getId();

        // Get the first gallery image that is not the base image, ordered by position
        $select = $connection->select()
            ->from(['mg' => $galleryTable], ['value'])
            ->join(
                ['mgvte' => $galleryEntityTable],
                'mg.value_id = mgvte.value_id',
                []
            )
            ->joinLeft(
                ['mgv' => $galleryValueTable],
                'mg.value_id = mgv.value_id AND mgvte.entity_id = mgv.entity_id'
                . ' AND mgv.store_id IN (0, ' . $storeId . ')',
                []
            )
            ->where('mgvte.entity_id = ?', $productId)
            ->where('mg.media_type = ?', 'image')
            ->where('COALESCE(mgv.disabled, 0) = 0')
            ->group('mg.value_id')
            ->order('MIN(COALESCE(mgv.position, 999)) ASC');

        // Exclude the base image so we always get a different one
        if ($baseImage) {
            $select->where('mg.value != ?', $baseImage);
        }

        $select->limit(1);

        return $connection->fetchOne($select) ?: null;
    }

    /**
     * Get flip image from child products of a configurable product
     *
     * @param ProductInterface|Product $product
     * @param string|null $imageId
     * @return string|null
     */
    private function getFlipImageFromChildren($product, ?string $imageId): ?string
    {
        $childIds = $this->getConfigurableChildIds((int) $product->getId());
        if (empty($childIds)) {
            return null;
        }

        $primaryRole = $this->config->getPrimaryRole();
        $fallbackRole = $this->config->getFallbackRole();

        // Try primary role on children
        $imageValue = $this->findChildImage($childIds, $primaryRole);

        // Try fallback role on children
        if (!$imageValue && $fallbackRole && $fallbackRole !== $primaryRole) {
            $imageValue = $this->findChildImage($childIds, $fallbackRole);
        }

        if ($imageValue) {
            return $this->buildImageUrl($product, $imageValue, $imageId);
        }

        return null;
    }

    /**
     * Find an image from child products by role
     *
     * @param array $childIds
     * @param string $role
     * @return string|null
     */
    private function findChildImage(array $childIds, string $role): ?string
    {
        if (empty($role)) {
            return null;
        }

        if ($role === 'second_image') {
            foreach ($childIds as $childId) {
                $image = $this->getAlternateGalleryImageByProductId((int) $childId);
                if ($image) {
                    return $image;
                }
            }
            return null;
        }

        // For regular roles, check EAV attribute values on children
        return $this->getAttributeValueFromChildren($childIds, $role);
    }

    /**
     * Get child product IDs for a configurable product
     *
     * @param int $parentId
     * @return array
     */
    private function getConfigurableChildIds(int $parentId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $superLinkTable = $this->resourceConnection->getTableName('catalog_product_super_link');

        return $connection->fetchCol(
            $connection->select()
                ->from($superLinkTable, ['product_id'])
                ->where('parent_id = ?', $parentId)
        );
    }

    /**
     * Get image attribute value from child products
     *
     * @param array $childIds
     * @param string $role
     * @return string|null
     */
    private function getAttributeValueFromChildren(array $childIds, string $role): ?string
    {
        $connection = $this->resourceConnection->getConnection();

        $eavAttributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $attributeId = $connection->fetchOne(
            $connection->select()
                ->from($eavAttributeTable, ['attribute_id'])
                ->where('attribute_code = ?', $role)
                ->where('entity_type_id = ?', 4) // catalog_product entity type
        );

        if (!$attributeId) {
            return null;
        }

        $varcharTable = $this->resourceConnection->getTableName('catalog_product_entity_varchar');
        $imageValue = $connection->fetchOne(
            $connection->select()
                ->from($varcharTable, ['value'])
                ->where('attribute_id = ?', $attributeId)
                ->where('entity_id IN (?)', $childIds)
                ->where('value IS NOT NULL')
                ->where('value != ?', 'no_selection')
                ->where('value != ?', '')
                ->limit(1)
        );

        return $imageValue ?: null;
    }

    /**
     * Check if product has a flip image
     *
     * @param ProductInterface|Product $product
     * @return bool
     */
    public function hasFlipImage($product): bool
    {
        return $this->getFlipImageUrl($product) !== null;
    }

    /**
     * Get flip image data for product (for use in templates)
     *
     * @param ProductInterface|Product $product
     * @param string|null $imageId
     * @return array
     */
    public function getFlipImageData($product, ?string $imageId = null): array
    {
        $flipImageUrl = $this->getFlipImageUrl($product, $imageId);

        return [
            'hasFlipImage' => $flipImageUrl !== null,
            'flipImageUrl' => $flipImageUrl,
            'animationType' => $this->config->getAnimationType(),
            'animationSpeed' => $this->config->getAnimationSpeed()
        ];
    }

    /**
     * Preload gallery images for a batch of product IDs (single query)
     *
     * @param array $productIds
     * @param int $maxImages
     * @return void
     */
    public function preloadGalleryBatch(array $productIds, int $maxImages = 8): void
    {
        if (empty($productIds)) {
            return;
        }

        // Filter out already cached
        $productIds = array_filter($productIds, function ($id) {
            return !isset($this->galleryCache[(int) $id]);
        });

        if (empty($productIds)) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $galleryTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery');
        $galleryValueTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value');
        $galleryEntityTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value_to_entity');

        $storeId = (int) $this->storeManager->getStore()->getId();

        $select = $connection->select()
            ->from(['mg' => $galleryTable], ['value'])
            ->join(
                ['mgvte' => $galleryEntityTable],
                'mg.value_id = mgvte.value_id',
                ['entity_id']
            )
            ->joinLeft(
                ['mgv' => $galleryValueTable],
                'mg.value_id = mgv.value_id AND mgvte.entity_id = mgv.entity_id'
                . ' AND mgv.store_id IN (0, ' . $storeId . ')',
                []
            )
            ->where('mgvte.entity_id IN (?)', $productIds)
            ->where('mg.media_type = ?', 'image')
            ->where('COALESCE(mgv.disabled, 0) = 0')
            ->group(['mgvte.entity_id', 'mg.value_id'])
            ->order(['mgvte.entity_id ASC', 'MIN(COALESCE(mgv.position, 999)) ASC']);

        $rows = $connection->fetchAll($select);

        // Group by entity_id and slice to maxImages
        $grouped = [];
        foreach ($rows as $row) {
            $entityId = (int) $row['entity_id'];
            if (!isset($grouped[$entityId])) {
                $grouped[$entityId] = [];
            }
            if (count($grouped[$entityId]) < $maxImages) {
                $imageValue = $row['value'];
                if ($this->isValidImageValue($imageValue)) {
                    $grouped[$entityId][] = $imageValue;
                }
            }
        }

        // Store in cache (raw paths, URLs will be built on demand)
        foreach ($productIds as $id) {
            $this->galleryCache[(int) $id] = $grouped[(int) $id] ?? [];
        }
    }

    /**
     * Get slider image data for a product
     *
     * @param ProductInterface|Product $product
     * @param string|null $imageId
     * @return array
     */
    public function getSliderImageData($product, ?string $imageId = null): array
    {
        $productId = (int) $product->getId();

        // Try cache first
        if (isset($this->galleryCache[$productId])) {
            $imagePaths = $this->galleryCache[$productId];
        } else {
            $imagePaths = $this->getAllGalleryImagesByProductId(
                $productId,
                $this->config->getMaxImages()
            );
        }

        // For configurables: collect images from children or filter parent by variant
        if ($product->getTypeId() === 'configurable') {
            $perChild = $this->config->getConfigurableImagesPerChild();
            $maxTotal = $this->config->getMaxImages();

            // IS-6421: explicit variant-selector path. When the admin configures an
            // attribute (e.g. color) that is a variation axis of this product, keep one
            // representative child per distinct value and use ITS images. This takes
            // precedence over the legacy ConfigurableGallery path below, so it works even
            // if the `associated_attributes` column lingers after CG is uninstalled. Only
            // engages when the axis matches and the representatives have images; otherwise
            // it falls through to the legacy behavior (no regression when unset).
            $variantHandled = false;
            if (!empty($this->config->getVariantSelectorAttributes())) {
                $childIds = $this->getConfigurableChildIds($productId);
                $representatives = !empty($childIds)
                    ? $this->reduceChildrenToVariantRepresentatives($productId, $childIds)
                    : [];

                if (!empty($representatives)) {
                    $perVariant = $perChild > 0 ? $perChild : $maxTotal;
                    $this->preloadGalleryBatch($representatives, $perVariant);

                    $variantImages = [];
                    foreach ($representatives as $repId) {
                        $repPaths = $this->galleryCache[(int) $repId] ?? [];
                        foreach (array_slice($repPaths, 0, $perVariant) as $path) {
                            $variantImages[] = $path;
                        }
                    }

                    if (!empty($variantImages)) {
                        $imagePaths = array_slice(array_values(array_unique($variantImages)), 0, $maxTotal);
                        $variantHandled = true;
                    }
                }
            }

            // Case 1: Parent has images with associated_attributes (ConfigurableGallery)
            if (!$variantHandled && !empty($imagePaths) && $perChild > 0 && $this->hasAssociatedAttributesColumn()) {
                $filteredPaths = $this->getImagesGroupedByVariant($productId, $perChild, $maxTotal);
                if (!empty($filteredPaths)) {
                    $imagePaths = $filteredPaths;
                }
            }

            // Case 2: Parent has no images or no ConfigurableGallery — use children's images
            if (!$variantHandled && (empty($imagePaths) || (!$this->hasAssociatedAttributesColumn() && $perChild > 0))) {
                $childIds = $this->getConfigurableChildIds($productId);
                if (!empty($childIds)) {
                    $this->preloadGalleryBatch($childIds, $perChild > 0 ? $perChild : $maxTotal);

                    $childImages = [];
                    foreach ($childIds as $childId) {
                        $childId = (int) $childId;
                        $childPaths = $this->galleryCache[$childId] ?? [];
                        if (!empty($childPaths)) {
                            if ($perChild > 0) {
                                $childPaths = array_slice($childPaths, 0, $perChild);
                            }
                            foreach ($childPaths as $path) {
                                $childImages[] = $path;
                            }
                        }
                    }

                    if (!empty($childImages)) {
                        if (!empty($imagePaths) && $perChild === 0) {
                            $imagePaths = array_merge($imagePaths, $childImages);
                        } else {
                            $imagePaths = $childImages;
                        }
                        $imagePaths = array_values(array_unique($imagePaths));
                        $imagePaths = array_slice($imagePaths, 0, $maxTotal);
                    }
                }
            }
        }

        if (count($imagePaths) < 2) {
            return [
                'hasSliderImages' => false,
                'galleryUrls' => [],
                'imageCount' => count($imagePaths)
            ];
        }

        // Build resized URLs
        $galleryUrls = [];
        foreach ($imagePaths as $path) {
            $url = $this->buildImageUrl($product, $path, $imageId);
            if ($url) {
                $galleryUrls[] = $url;
            }
        }

        return [
            'hasSliderImages' => count($galleryUrls) >= 2,
            'galleryUrls' => $galleryUrls,
            'imageCount' => count($galleryUrls)
        ];
    }

    /**
     * Get all gallery image paths for a product ID
     *
     * @param int $productId
     * @param int $maxImages
     * @return array Raw image paths
     */
    private function getAllGalleryImagesByProductId(int $productId, int $maxImages = 8): array
    {
        $connection = $this->resourceConnection->getConnection();
        $galleryTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery');
        $galleryValueTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value');
        $galleryEntityTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value_to_entity');

        $storeId = (int) $this->storeManager->getStore()->getId();

        $select = $connection->select()
            ->from(['mg' => $galleryTable], ['value'])
            ->join(
                ['mgvte' => $galleryEntityTable],
                'mg.value_id = mgvte.value_id',
                []
            )
            ->joinLeft(
                ['mgv' => $galleryValueTable],
                'mg.value_id = mgv.value_id AND mgvte.entity_id = mgv.entity_id'
                . ' AND mgv.store_id IN (0, ' . $storeId . ')',
                []
            )
            ->where('mgvte.entity_id = ?', $productId)
            ->where('mg.media_type = ?', 'image')
            ->where('COALESCE(mgv.disabled, 0) = 0')
            ->group('mg.value_id')
            ->order('MIN(COALESCE(mgv.position, 999)) ASC')
            ->limit($maxImages);

        $values = $connection->fetchCol($select);

        return array_filter($values, [$this, 'isValidImageValue']);
    }

    /**
     * Check if the associated_attributes column exists (ConfigurableGallery installed)
     *
     * @return bool
     */
    private function hasAssociatedAttributesColumn(): bool
    {
        static $has = null;
        if ($has === null) {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value');
            $has = $connection->tableColumnExists($tableName, 'associated_attributes');
        }
        return $has;
    }

    /**
     * Get gallery images grouped by variant (using associated_attributes from ConfigurableGallery)
     * Returns first N images per unique variant value.
     *
     * @param int $productId
     * @param int $perVariant Max images per variant
     * @param int $maxTotal Max total images
     * @return array Image paths
     */
    private function getImagesGroupedByVariant(int $productId, int $perVariant, int $maxTotal): array
    {
        $connection = $this->resourceConnection->getConnection();
        $galleryTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery');
        $galleryValueTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value');
        $galleryEntityTable = $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value_to_entity');

        $storeId = (int) $this->storeManager->getStore()->getId();

        $select = $connection->select()
            ->from(['mg' => $galleryTable], ['value'])
            ->join(
                ['mgvte' => $galleryEntityTable],
                'mg.value_id = mgvte.value_id',
                []
            )
            ->joinLeft(
                ['mgv' => $galleryValueTable],
                'mg.value_id = mgv.value_id AND mgvte.entity_id = mgv.entity_id'
                . ' AND mgv.store_id IN (0, ' . $storeId . ')',
                ['associated_attributes']
            )
            ->where('mgvte.entity_id = ?', $productId)
            ->where('mg.media_type = ?', 'image')
            ->where('COALESCE(mgv.disabled, 0) = 0')
            ->order('MIN(COALESCE(mgv.position, 999)) ASC')
            ->group('mg.value_id');

        $rows = $connection->fetchAll($select);

        // Group images by variant
        $byVariant = []; // variant_key => [path, path, ...]
        $generic = [];   // images without associated_attributes (generic/all variants)

        foreach ($rows as $row) {
            $path = $row['value'];
            if (!$this->isValidImageValue($path)) {
                continue;
            }

            $assoc = $row['associated_attributes'] ?? '';
            if (empty($assoc)) {
                $generic[] = $path;
            } else {
                // Can have multiple: "attribute93-4,attribute93-5"
                $variants = explode(',', $assoc);
                foreach ($variants as $variant) {
                    $variant = trim($variant);
                    if (!isset($byVariant[$variant])) {
                        $byVariant[$variant] = [];
                    }
                    $byVariant[$variant][] = $path;
                }
            }
        }

        // Collect first N per variant
        $result = [];
        foreach ($byVariant as $paths) {
            $sliced = array_slice($paths, 0, $perVariant);
            foreach ($sliced as $p) {
                $result[] = $p;
            }
        }

        // Add generic images
        foreach ($generic as $p) {
            $result[] = $p;
        }

        // Deduplicate and cap
        $result = array_values(array_unique($result));
        return array_slice($result, 0, $maxTotal);
    }

    /**
     * Reduce a configurable's children to one representative per distinct value
     * of the configured variant-selector attribute (e.g. one child per color).
     *
     * Children that share the same selector value (e.g. same color, different talle)
     * collapse to a single representative, so size-only variants no longer duplicate
     * images in the slider. Children without a value for the axis are each kept.
     *
     * @param int $parentId
     * @param array $childIds
     * @return array Reduced, ordered child IDs, or [] when no configured selector
     *               attribute matches one of the product's variation axes (caller
     *               then keeps the full child list = legacy behavior).
     */
    private function reduceChildrenToVariantRepresentatives(int $parentId, array $childIds): array
    {
        $selectorCodes = $this->config->getVariantSelectorAttributes();
        if (empty($selectorCodes) || empty($childIds)) {
            return [];
        }

        $attributeId = $this->resolveVariantSelectorAttributeId($parentId, $selectorCodes);
        if (!$attributeId) {
            return [];
        }

        $valuesByChild = $this->getChildAttributeValues($childIds, $attributeId);
        if (empty($valuesByChild)) {
            return [];
        }

        // Stable, deterministic order by entity ID
        $childIds = array_map('intval', $childIds);
        sort($childIds);

        $representatives = [];
        $seen = [];
        foreach ($childIds as $childId) {
            // Children missing the axis value each get their own bucket so their
            // images are never silently dropped.
            $value = $valuesByChild[$childId] ?? null;
            $key = $value === null ? 'null-' . $childId : 'v-' . $value;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $representatives[] = $childId;
        }

        return $representatives;
    }

    /**
     * Resolve the EAV attribute ID of the first configured selector attribute that
     * is actually a variation axis (super attribute) of the configurable product.
     *
     * @param int $parentId
     * @param string[] $selectorCodes Priority-ordered attribute codes
     * @return int|null
     */
    private function resolveVariantSelectorAttributeId(int $parentId, array $selectorCodes): ?int
    {
        $connection = $this->resourceConnection->getConnection();
        $superAttrTable = $this->resourceConnection->getTableName('catalog_product_super_attribute');
        $eavAttributeTable = $this->resourceConnection->getTableName('eav_attribute');

        // code => attribute_id for this product's variation axes
        $axes = $connection->fetchPairs(
            $connection->select()
                ->from(['sa' => $superAttrTable], [])
                ->join(
                    ['ea' => $eavAttributeTable],
                    'sa.attribute_id = ea.attribute_id',
                    ['ea.attribute_code', 'ea.attribute_id']
                )
                ->where('sa.product_id = ?', $parentId)
                ->where('ea.entity_type_id = ?', 4) // catalog_product entity type
        );

        if (empty($axes)) {
            return null;
        }

        foreach ($selectorCodes as $code) {
            if (isset($axes[$code])) {
                return (int) $axes[$code];
            }
        }

        return null;
    }

    /**
     * Get the (store-aware) attribute value of each child for a select attribute.
     * Configurable variation axes are always select attributes (int backend).
     *
     * @param array $childIds
     * @param int $attributeId
     * @return array entity_id => value (option id)
     */
    private function getChildAttributeValues(array $childIds, int $attributeId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $intTable = $this->resourceConnection->getTableName('catalog_product_entity_int');
        $storeId = (int) $this->storeManager->getStore()->getId();

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($intTable, ['entity_id', 'store_id', 'value'])
                ->where('attribute_id = ?', $attributeId)
                ->where('entity_id IN (?)', array_map('intval', $childIds))
                ->where('store_id IN (?)', [0, $storeId])
        );

        // Default (store 0) values, with store-specific overrides winning.
        $default = [];
        $override = [];
        foreach ($rows as $row) {
            if ($row['value'] === null || $row['value'] === '') {
                continue;
            }
            $entityId = (int) $row['entity_id'];
            if ((int) $row['store_id'] === $storeId && $storeId !== 0) {
                $override[$entityId] = $row['value'];
            } else {
                $default[$entityId] = $row['value'];
            }
        }

        return $override + $default;
    }
}
