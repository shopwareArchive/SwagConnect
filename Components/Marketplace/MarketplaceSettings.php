<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\Marketplace;

use ShopwarePlugins\Connect\Components\Struct;

class MarketplaceSettings extends Struct
{
    public $marketplaceName;

    public $marketplaceNetworkUrl;

    public $marketplaceIcon;

    public $marketplaceLogo;

    public $isDefault;
}
