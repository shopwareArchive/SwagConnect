<?php
/**
 * Shopware 5.2
 * Copyright © 2016 shopware AG
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

use ShopwarePlugins\Connect\Bootstrap\Uninstall;
use ShopwarePlugins\Connect\Bootstrap\Update;
use ShopwarePlugins\Connect\Bootstrap\Setup;
use Shopware\Connect\Gateway\PDO;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 */
final class Shopware_Plugins_Backend_SwagConnect_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /** @var \ShopwarePlugins\Connect\Components\ConnectFactory */
    private $connectFactory;

    /**
     * Returns the current version of the plugin.
     *
     * @return string|void
     * @throws Exception
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR .'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new \Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * Returns a nice name for plugin manager list
     *
     * @return string
     */
    public function getLabel()
    {
        return 'Shopware Connect';
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
     * @return array
     */
    public function update($version)
    {
        // sometimes plugin is not installed before
        // but could be updated. by this way setup process
        // is simple and only required structure will be created
        // e.g. DB and attributes
        $fullSetup = false;
        if ($this->isInstalled()) {
            $fullSetup = true;
        }

        $this->doSetup($fullSetup);
        $this->doUpdate($version);

        return array('success' => true, 'invalidateCache' => array('backend', 'config'));
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
     *
     * @param bool $fullSetup
     * @throws RuntimeException
     */
    public function doSetup($fullSetup = true)
    {
        $this->registerMyLibrary();

        $setup = new Setup(
            $this,
            Shopware()->Models(),
            Shopware()->Db(),
            $this->assertMinimumVersion('5.2.6')
        );
        $setup->run($fullSetup);
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

        $update = new Update(
            $this,
            Shopware()->Models(),
            Shopware()->Db(),
            $version
        );
        return $update->run();
    }

    /**
     * Uninstall the plugin
     */
    public function doUninstall()
    {
        $this->registerMyLibrary();

        $uninstall = new Uninstall(
            $this,
            Shopware()->Models(),
            Shopware()->Db(),
            $this->assertMinimumVersion('5.2.6')
        );
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

        /** @var $subscriber ShopwarePlugins\Connect\Subscribers\BaseSubscriber */
        foreach ($subscribers as $subscriber) {
            $subscriber->setBootstrap($this);
            $this->Application()->Events()->registerSubscriber($subscriber);
        }
    }

    public function getSubscribersForUnverifiedKeys()
    {
        return array(
            new \ShopwarePlugins\Connect\Subscribers\DisableConnectInFrontend(),
            new \ShopwarePlugins\Connect\Subscribers\Lifecycle()
        );
    }

    /**
     * These subscribers will only be used, once the user has verified his api key
     * This will prevent the users from having shopware Connect extensions in their frontend
     * even if they cannot use shopware Connect due to the missing / wrong api key
     *
     * @return array
     */
    public function getSubscribersForVerifiedKeys()
    {
        $subscribers = array(
            new \ShopwarePlugins\Connect\Subscribers\TemplateExtension(),
            $this->createCheckoutSubscriber(),
            new \ShopwarePlugins\Connect\Subscribers\Voucher(),
            new \ShopwarePlugins\Connect\Subscribers\BasketWidget(),
            new \ShopwarePlugins\Connect\Subscribers\Dispatches(),
            new \ShopwarePlugins\Connect\Subscribers\Javascript(),
            new \ShopwarePlugins\Connect\Subscribers\Less(),
            new \ShopwarePlugins\Connect\Subscribers\Lifecycle()

        );

        $this->registerMyLibrary();

        return $subscribers;
    }

    /**
     * Default subscribers can safely be used, even if the api key wasn't verified, yet
     *
     * @return array
     */
    public function getDefaultSubscribers()
    {
        $db = Shopware()->Db();
        $modelManager = Shopware()->Models();

        return array(
            new \ShopwarePlugins\Connect\Subscribers\OrderDocument(),
            new \ShopwarePlugins\Connect\Subscribers\ControllerPath($this->assertMinimumVersion('5.2')),
            new \ShopwarePlugins\Connect\Subscribers\CustomerGroup(),
            new \ShopwarePlugins\Connect\Subscribers\CronJob(),
            new \ShopwarePlugins\Connect\Subscribers\ArticleList(),
            new \ShopwarePlugins\Connect\Subscribers\Article(
                new PDO($db->getConnection()),
                $modelManager
            ),
            new \ShopwarePlugins\Connect\Subscribers\Category(
                $modelManager
            ),
            new \ShopwarePlugins\Connect\Subscribers\Connect(),
            new \ShopwarePlugins\Connect\Subscribers\Payment(),
            new \ShopwarePlugins\Connect\Subscribers\ServiceContainer(
                $modelManager
            ),
        );
    }

    public function onInitResourceSDK()
    {
        $this->registerMyLibrary();

        return $this->getConnectFactory()->createSDK();
    }

    /**
     * Register additional namespaces for the libraries
     */
    public function registerMyLibrary()
    {
        $this->Application()->Loader()->registerNamespace(
            'Shopware\\Connect',
            $this->Path() . 'Library/Shopware/Connect/'
        );
        $this->Application()->Loader()->registerNamespace(
            'Firebase\\JWT',
            $this->Path() . 'Library/Firebase/JWT/'
        );
        $this->Application()->Loader()->registerNamespace(
            'ShopwarePlugins\\Connect',
            $this->Path()
        );

        $this->registerCustomModels();
    }

    /**
     * Lazy getter for the connectFactory
     *
     * @return \ShopwarePlugins\Connect\Components\ConnectFactory
     */
    public function getConnectFactory()
    {
        $this->registerMyLibrary();

        if (!$this->connectFactory) {
            $this->connectFactory = new \ShopwarePlugins\Connect\Components\ConnectFactory($this->getVersion());
        }

        return $this->connectFactory;
    }

    /**
     * @return Shopware\Connect\SDK
     */
    public function getSDK()
    {
        return $this->getConnectFactory()->getSDK();
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Helper
     */
    public function getHelper()
    {
        return $this->getConnectFactory()->getHelper();
    }

    public function getBasketHelper()
    {
        return $this->getConnectFactory()->getBasketHelper();
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Config
     */
    public function getConfigComponents()
    {
        return $this->getConnectFactory()->getConfigComponent();
    }

    public function getMarketplaceGateway()
    {
        return $this->getConnectFactory()->getMarketplaceGateway();
    }

    public function getMarketplaceApplier()
    {
        return $this->getConnectFactory()->getMarketplaceApplier();
    }

    /**
     * @return bool
     */
    private function isInstalled()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('plugins'))
            ->from('Shopware\Models\Plugin\Plugin', 'plugins');

        $builder->where('plugins.label = :label');
        $builder->setParameter('label', $this->getLabel());

        $query = $builder->getQuery();
        $plugin = $query->getOneOrNullResult();
        /** @var $plugin Shopware\Models\Plugin\Plugin */
        if (!$plugin) {
            return false;
        }

        return (bool) $plugin->getInstalled();
    }

    /**
     * Creates checkout subscriber
     *
     * @return \ShopwarePlugins\Connect\Subscribers\Checkout
     */
    private function createCheckoutSubscriber()
    {
        $checkoutSubscriber = new \ShopwarePlugins\Connect\Subscribers\Checkout();
        foreach ($checkoutSubscriber->getListeners() as $listener) {
            if ($listener->getName() == 'Enlight_Controller_Action_PostDispatch_Frontend_Checkout') {
                $listener->setPosition(-1);
            }
        }

        return $checkoutSubscriber;
    }
}
