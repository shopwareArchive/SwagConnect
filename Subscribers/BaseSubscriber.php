<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

/**
 * The base subscriber holds some default methods, which all other subscribers might need.
 * It also extends the SubscribeManager so that any subscriber can be registered as Enlight_Event_Subscriber
 *
 * todo: Refactor the subscribers and replace the $bootstrap reference with injected dependencies
 *
 * Class BaseSubscriber
 */
abstract class BaseSubscriber extends SubscribeManager
{
    /**
     * @var \Shopware_Plugins_Backend_SwagConnect_Bootstrap
     */
    protected $bootstrap;

    /**
     * @return \Shopware_Plugins_Backend_SwagConnect_Bootstrap
     */
    public function Bootstrap()
    {
        return $this->bootstrap;
    }

    /**
     * @return \Shopware
     */
    public function Application()
    {
        return $this->bootstrap->Application();
    }

    /**
     * @return \Enlight_Config
     */
    public function Config()
    {
        return $this->Application()->Config();
    }

    public function getHelper()
    {
        return $this->bootstrap->getHelper();
    }

    public function Path()
    {
        return $this->bootstrap->Path();
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Config
     */
    public function getConnectConfig()
    {
        return $this->bootstrap->getConfigComponents();
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\BasketHelper
     */
    public function getBasketHelper()
    {
        return $this->bootstrap->getBasketHelper();
    }

    /**
     * @param \Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap
     */
    public function setBootstrap($bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * @return \Shopware_Plugins_Backend_SwagConnect_Bootstrap
     */
    public function getBootstrap()
    {
        return $this->bootstrap;
    }

    /**
     * @return \Shopware\Connect\SDK
     */
    public function getSDK()
    {
        return  $this->bootstrap->getSDK();
    }

    /**
     * Register the template directory of the plugin
     */
    public function registerMyTemplateDir()
    {
        $template = '';
        if ($this->Application()->Container()->has('shop')) {
            if ($this->Application()
                    ->Container()
                    ->get('shop')
                    ->getTemplate()
                    ->getVersion() >= 3
            ) {
                $template = 'responsive';
            } else {
                $template = 'emotion';
            }
        }

        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/' . $template, 'connect'
        );
    }

    /**
     * Register additional namespaces for the libraries
     */
    public function registerMyLibrary()
    {
        $this->bootstrap->registerMyLibrary();
    }

    /**
     * Register the snippet folder
     */
    public function registerMySnippets()
    {
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
    }

    /**
     * @return bool
     */
    public function hasPriceType()
    {
        if ($this->getSDK()->getPriceType() === \Shopware\Connect\SDK::PRICE_TYPE_PURCHASE
            || $this->getSDK()->getPriceType() === \Shopware\Connect\SDK::PRICE_TYPE_RETAIL
            || $this->getSDK()->getPriceType() === \Shopware\Connect\SDK::PRICE_TYPE_BOTH
        ) {
            return true;
        }

        return false;
    }
}
