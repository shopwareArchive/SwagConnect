<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

use Shopware\Connect\SDK;
use ShopwarePlugins\Connect\Components\ConnectFactory;

trait ConnectTestHelperTrait
{
    private $connectFactory;
    private $sdk;

    /**
     * @return ConnectFactory
     */
    public function getConnectFactory()
    {
        if (!$this->connectFactory) {
            $this->connectFactory = new ConnectFactory();
        }

        return $this->connectFactory;
    }

    /**
     * @return SDK
     */
    public function getSDK()
    {
        if (!$this->sdk) {
            $this->sdk = $this->getConnectFactory()->createSdk();
        }

        return $this->sdk;
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Helper
     */
    public function getHelper()
    {
        return Shopware()->Plugins()->Backend()->SwagConnect()->getHelper();
    }
}
