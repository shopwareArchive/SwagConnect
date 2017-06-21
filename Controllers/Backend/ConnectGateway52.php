<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ShopwarePlugins\Connect\Controllers\Backend\ConnectGatewayBaseController;

/**
 * @category  Shopware
 *
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
class Shopware_Controllers_Backend_ConnectGateway extends ConnectGatewayBaseController implements \Shopware\Components\CSRFWhitelistAware
{
    public function getWhitelistedCSRFActions()
    {
        return ['index', 'removePlugin'];
    }

    public function indexAction()
    {
        parent::indexAction();
    }
}
