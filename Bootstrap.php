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
use Shopware\Bepado\Bootstrap\Uninstall;
use Shopware\Bepado\Bootstrap\Update;
use Shopware\Bepado\Bootstrap\Setup;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 */
final class Shopware_Plugins_Backend_SwagBepado_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /** @var \Shopware\Bepado\Components\BepadoFactory */
    private $bepadoFactory;

    /**
     * Returns the current version of the plugin.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.4.34';
    }

    /**
     * Returns a nice name for plugin manager list
     *
     * @return string
     */
    public function getLabel()
    {
        return 'bepado';
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'description' => file_get_contents($this->Path() . 'info.txt'),
            'link' => 'http://www.shopware.de/',
        );
    }

    /**
     * Install plugin method
     *
     * @throws \RuntimeException
     * @return bool
     */
    public function install()
    {
        $this->doSetup();

        return array('success' => true, 'invalidateCache' => array('backend', 'config'));
    }

    /**
     * @param $version string
     * @return bool
     */
    public function update($version)
    {
        $this->doSetup();

        return $this->doUpdate($version);
    }

    /**
     * Uninstall plugin method
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->doUninstall();

        return true;
    }

    /**
     * Performs the default setup of the system.
     *
     * This can be used by the update as well as by the install method
     */
    public function doSetup()
    {
        if (!$this->assertVersionGreaterThen('4.1.0')) {
            throw new \RuntimeException('Shopware version 4.1.0 or later is required.');
        };

        $this->registerMyLibrary();

        $setup = new Setup($this);
        $setup->run();
    }

    /**
     * Performs the update of the system
     *
     * @param $version
     * @return bool
     */
    public function doUpdate($version)
    {
        $this->registerMyLibrary();

        $update = new Update($this, $version);
        return $update->run();
    }

    /**
     * Uninstall the plugin
     */
    public function doUninstall()
    {
        $this->registerMyLibrary();

        $uninstall = new Uninstall($this);
        return $uninstall->run();
    }

    /**
     * Will dynamically register all needed events
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $this->registerMyLibrary();

        try {
            /** @var Shopware\Components\Model\ModelManager $modelManager */
            $configComponent = $this->getConfigComponents();
            $verified = $configComponent->getConfig('apiKeyVerified', false);
        } catch (\Exception $e) {
            // if the config table is not available, just assume, that the update
            // still needs to be installed
            $verified = false;
        }


        $subscribers = $this->getDefaultSubscribers();

        // Some subscribers may only be used, if the SDK is verified
        if ($verified) {
            $subscribers = array_merge($subscribers, $this->getSubscribersForVerifiedKeys());
        // These subscribers are used if the api key is not valid
        } else {
            $subscribers = array_merge($subscribers, $this->getSubscribersForUnverifiedKeys());
        }

        /** @var $subscriber Shopware\Bepado\Subscribers\BaseSubscriber */
        foreach ($subscribers as $subscriber) {
            $subscriber->setBootstrap($this);
            $this->Application()->Events()->registerSubscriber($subscriber);
        }
    }

    public function getSubscribersForUnverifiedKeys()
    {
        return array(
            new \Shopware\Bepado\Subscribers\DisableBepadoInFrontend()
        );
    }

    /**
     * These subscribers will only be used, once the user has verified his api key
     * This will prevent the users from having bepado extensions in their frontend
     * even if they cannot use bepado due to the missing / wrong api key
     *
     * @return array
     */
    public function getSubscribersForVerifiedKeys()
    {
        $subscribers = array(
            new \Shopware\Bepado\Subscribers\TemplateExtension(),
            new \Shopware\Bepado\Subscribers\Checkout(),
            new \Shopware\Bepado\Subscribers\Voucher(),
            new \Shopware\Bepado\Subscribers\BasketWidget(),
            new \Shopware\Bepado\Subscribers\Dispatches(),
        );

        $this->registerMyLibrary();
        $configComponent = $this->getConfigComponents();

        if ($configComponent->getConfig('autoUpdateProducts', true)) {
            $subscribers[] = new \Shopware\Bepado\Subscribers\Lifecycle();
        }

        return $subscribers;
    }

    /**
     * Default subscribers can safely be used, even if the api key wasn't verified, yet
     *
     * @return array
     */
    public function getDefaultSubscribers()
    {
        return array(
            new \Shopware\Bepado\Subscribers\OrderDocument(),
            new \Shopware\Bepado\Subscribers\ControllerPath(),
            new \Shopware\Bepado\Subscribers\CronJob(),
            new \Shopware\Bepado\Subscribers\ArticleList(),
            new \Shopware\Bepado\Subscribers\Article(),
            new \Shopware\Bepado\Subscribers\Bepado(),
        );
    }

    public function onInitResourceSDK()
    {
        $this->registerMyLibrary();

        return $this->getBepadoFactory()->createSDK();
    }

    /**
     * Register additional namespaces for the libraries
     */
    public function registerMyLibrary()
    {
        $this->Application()->Loader()->registerNamespace(
            'Bepado',
            $this->Path() . 'Library/Bepado/'
        );
        $this->Application()->Loader()->registerNamespace(
            'Shopware\\Bepado',
            $this->Path()
        );

        $this->registerCustomModels();
    }

    /**
     * Lazy getter for the bepadoFactory
     *
     * @return \Shopware\Bepado\Components\BepadoFactory
     */
    public function getBepadoFactory()
    {
        $this->registerMyLibrary();

        if (!$this->bepadoFactory) {
            $this->bepadoFactory = new \Shopware\Bepado\Components\BepadoFactory($this->getVersion());
        }

        return $this->bepadoFactory;
    }

    /**
     * @return Bepado\SDK\SDK
     */
    public function getSDK()
    {
        return $this->getBepadoFactory()->getSDK();
    }

    /**
     * @return \Shopware\Bepado\Components\Helper
     */
    public function getHelper()
    {
        return $this->getBepadoFactory()->getHelper();
    }

    public function getBasketHelper()
    {
        return $this->getBepadoFactory()->getBasketHelper();
    }

    /**
     * @return \Shopware\Bepado\Components\Config
     */
    public function getConfigComponents()
    {
        return $this->getBepadoFactory()->getConfigComponent();
    }
}
