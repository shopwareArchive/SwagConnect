<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use ShopwarePlugins\Connect\Bootstrap\SubscriberRegistration;
use ShopwarePlugins\Connect\Bootstrap\Uninstall;
use ShopwarePlugins\Connect\Bootstrap\Update;
use ShopwarePlugins\Connect\Bootstrap\Setup;
use ShopwarePlugins\Connect\Commands\ApiEndpointCommand;
use ShopwarePlugins\Connect\Components\BasketHelper;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\SnHttpClient;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 */
final class Shopware_Plugins_Backend_SwagConnect_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @var SubscriberRegistration
     */
    private $subscriberRegistration;

    /**
     * @var ConnectFactory
     */
    private $connectFactory;

    /**
     * Returns the current version of the plugin.
     *
     * @throws Exception
     * @return string|void
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        }
        throw new \Exception('The plugin has an invalid version file.');
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
        return [
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'description' => file_get_contents($this->Path() . 'info.txt'),
            'link' => 'http://www.shopware.de/',
        ];
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

        return ['success' => true, 'invalidateCache' => ['backend', 'config']];
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

        return ['success' => true, 'invalidateCache' => ['backend', 'config', 'template', 'theme']];
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
     * @return array
     */
    public function disable()
    {
        $this->sendUninstallNotificationToSocialNetwork();

        return ['success' => true];
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
        $modelManager = Shopware()->Models();
        $setup = new Setup(
            $this,
            $modelManager,
            Shopware()->Db(),
            new \ShopwarePlugins\Connect\Bootstrap\Menu(
                $this,
                $this->getConfigComponents(),
                $modelManager,
                $this->assertMinimumVersion('5.2.6')
            )
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
            new Logger(Shopware()->Db()),
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
        $modelManager = Shopware()->Models();
        $uninstall = new Uninstall(
            $this,
            $modelManager,
            Shopware()->Db(),
            new \ShopwarePlugins\Connect\Bootstrap\Menu(
                $this,
                $this->getConfigComponents(),
                $modelManager,
                $this->assertMinimumVersion('5.2.6')
            )
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
        $this->get('template')->addTemplateDir($this->Path() . 'Views/', 'connect');
        $this->get('snippets')->addConfigDir($this->Path() . 'Snippets/');
        $this->registerMyLibrary();
        $this->registerSubscribers();
    }

    /**
     * @return ArrayCollection
     */
    public function onConsoleAddCommand()
    {
        $this->registerMyLibrary();
        $this->registerSubscribers();

        return new ArrayCollection([
            new ApiEndpointCommand()
        ]);
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
            'ShopwarePlugins\\Connect',
            $this->Path()
        );

        $this->registerCustomModels();
    }

    /**
     * Lazy getter for the connectFactory
     *
     * @return ConnectFactory
     */
    public function getConnectFactory()
    {
        $this->registerMyLibrary();

        if (!$this->connectFactory) {
            $this->connectFactory = new ConnectFactory($this->getVersion());
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

    /**
     * @return BasketHelper
     */
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

    /**
     * @return bool
     */
    private function isInstalled()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(['plugins'])
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

    private function registerSubscribers()
    {
        if (!$this->subscriberRegistration instanceof SubscriberRegistration) {
            $this->subscriberRegistration = new SubscriberRegistration(
                $this->getConfigComponents(),
                $this->get('models'),
                $this->get('db'),
                $this,
                $this->get('events'),
                $this->getSDK(),
                $this->getConnectFactory(),
                $this->getHelper(),
                $this->get('service_container')
            );
        }

        $this->subscriberRegistration->registerSubscribers();
    }

    /**
     * @return SnHttpClient
     */
    public function getSnHttpClient()
    {
        return new SnHttpClient(
            $this->Application()->Container()->get('http_client'),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            ConfigFactory::getConfigInstance()
        );
    }

    public function sendUninstallNotificationToSocialNetwork()
    {
        try {
            $this->getSnHttpClient()->sendRequestToConnect('account/supplier-plugin-uninstalled');
        } catch (\Exception $e) {
            Shopware()->PluginLogger()->warning("The plugin was uninstalled but sending the status to Connect failed with this exception: " . $e->getMessage());
        }
    }
}
