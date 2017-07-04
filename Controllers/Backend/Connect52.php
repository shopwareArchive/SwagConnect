<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ShopwarePlugins\Connect\Controllers\Backend\ConnectBaseController;

class Shopware_Controllers_Backend_Connect extends ConnectBaseController implements \Shopware\Components\CSRFWhitelistAware
{
    public function getWhitelistedCSRFActions()
    {
        return ['login', 'autoLogin'];
    }
}
