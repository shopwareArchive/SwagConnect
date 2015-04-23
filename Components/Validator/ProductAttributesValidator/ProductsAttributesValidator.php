<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */
namespace Shopware\Bepado\Components\Validator\ProductAttributesValidator;
use Shopware\Bepado\Components\Validator\ProductAttributesValidator;

/**
 * Interface ProductAttributesValidator
 * @package Shopware\Bepado\Components\Validator
 */
class ProductsAttributesValidator implements ProductAttributesValidator
{
    /**
     * Validates given attributes array,
     * They must be only scalar values
     *
     * @param array $attributes
     * @return array
     * @throws \Exception
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