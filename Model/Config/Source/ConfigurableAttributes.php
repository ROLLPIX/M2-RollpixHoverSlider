<?php
/**
 * @author Rollpix
 * @package Rollpix_ImageFlipHover
 */
declare(strict_types=1);

namespace Rollpix\ImageFlipHover\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for the "variant selector attributes" config field.
 *
 * Lists every product attribute that can act as a configurable variation axis
 * (frontend input select/multiselect). The admin picks an ordered, priority list;
 * for each configurable product the module uses the first picked attribute that is
 * actually one of that product's super attributes (e.g. color) to decide which
 * variants must show different images.
 */
class ConfigurableAttributes implements OptionSourceInterface
{
    /**
     * @var ProductAttributeCollectionFactory
     */
    private ProductAttributeCollectionFactory $productAttributeCollectionFactory;

    /**
     * @var array|null
     */
    private ?array $options = null;

    /**
     * @param ProductAttributeCollectionFactory $productAttributeCollectionFactory
     */
    public function __construct(
        ProductAttributeCollectionFactory $productAttributeCollectionFactory
    ) {
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
    }

    /**
     * Get all select/multiselect product attributes
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = [];

        try {
            $collection = $this->productAttributeCollectionFactory->create();
            $collection->addFieldToFilter('frontend_input', ['in' => ['select', 'multiselect']]);
            $collection->addFieldToFilter('frontend_label', ['neq' => '']);
            $collection->setOrder('frontend_label', 'ASC');

            foreach ($collection as $attribute) {
                $code = $attribute->getAttributeCode();
                if (!$code) {
                    continue;
                }
                $label = $attribute->getFrontendLabel() ?: $code;

                $this->options[] = [
                    'value' => $code,
                    'label' => sprintf('%s (%s)', $label, $code)
                ];
            }
        } catch (\Exception $e) {
            $this->options = [];
        }

        if (empty($this->options)) {
            // Fallback so the field is never empty in the admin
            $this->options[] = ['value' => 'color', 'label' => 'Color (color)'];
        }

        return $this->options;
    }
}
