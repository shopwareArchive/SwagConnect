<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator;

use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator;

/**
 * Interface ProductAttributesValidator
 */
class ProductsAttributesValidator implements ProductAttributesValidator
{
    /**
     * Validates given attributes array,
     * They must be only scalar values
     *
     * @param array $attributes
     *
     * @throws \Exception
     *
     * @return array
     */
    public function validate(array $attributes)
    {
        foreach ($attributes as $attributeKey => $attributeValue) {
            if (!is_null($attributeValue) && is_scalar($attributeValue) === false) {
                throw new \Exception(
                    sprintf('Product attribute %s must be scalar value', $attributeKey)
                );
            }
        }

        return $attributes;
    }
}
