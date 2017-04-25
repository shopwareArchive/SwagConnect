<?php

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