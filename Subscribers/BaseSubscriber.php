<?php

namespace Shopware\Bepado\Subscribers;

/**
 * The base subscriber holds some default methods, which all other subscribers might need.
 * It also extends the SubscribeManager so that any subscriber can be registered as Enlight_Event_Subscriber
 *
 * todo: Refactor the subscribers and replace the $bootstrap reference with injected dependencies
 *
 * Class BaseSubscriber
 * @package Shopware\Bepado\Subscribers
 */
abstract class BaseSubscriber extends SubscribeManager
{
    /**
     * @var \Shopware_Plugins_Backend_SwagBepado_Bootstrap
     */
    protected $bootstrap;

    /**
     * @return \Shopware_Plugins_Backend_SwagBepado_Bootstrap
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
     * @return \Shopware\Bepado\Components\BasketHelper
     */
    public function getBasketHelper()
    {
        return $this->bootstrap->getBasketHelper();
    }

    /**
     * @param \Shopware_Plugins_Backend_SwagBepado_Bootstrap $bootstrap
     */
    public function setBootstrap($bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * @return \Shopware_Plugins_Backend_SwagBepado_Bootstrap
     */
    public function getBootstrap()
    {
        return $this->bootstrap;
    }

    /**
     * @return \Bepado\SDK\SDK
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
            if($this->Application()
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
            $this->Path() . 'Views/'.$template, 'bepado'
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

}