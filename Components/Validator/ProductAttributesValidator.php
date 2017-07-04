<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\Validator;

/**
 * Interface ProductAttributesValidator
 * @package ShopwarePlugins\Connect\Components\Validator
 */
interface ProductAttributesValidator
{
    public function validate(array $attributes);
}
