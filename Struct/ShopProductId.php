<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Struct;

/**
 * Class ShopProductId represents remote product identifiers
 * @package ShopwarePlugins\Connect\Struct
 */
class ShopProductId extends BaseStruct
{
    /**
     * @var string
     */
    public $sourceId;

    /**
     * @var int
     */
    public $shopId;
}
