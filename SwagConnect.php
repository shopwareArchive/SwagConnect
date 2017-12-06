<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagConnect;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use ShopwarePlugins\Connect\Bootstrap\SubscriberRegistration;
use ShopwarePlugins\Connect\Bootstrap\Uninstall;
use ShopwarePlugins\Connect\Bootstrap\Update;
use ShopwarePlugins\Connect\Bootstrap\Setup;
use ShopwarePlugins\Connect\Commands\ApiEndpointCommand;
use ShopwarePlugins\Connect\Components\BasketHelper;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\Logger;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 */
class SwagConnect extends \Shopware\Components\Plugin
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
     * @throws \Exception
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
            'description' => file_get_contents($this->getPath() . 'info.txt'),
            'link' => 'http://www.shopware.de/',
        ];
    }

    /**
     * Install plugin method
     *
     * @param $context InstallContext
     * @throws \RuntimeException
     * @return array
     */
    public function install(InstallContext $context)
    {
        $this->doSetup($context);

        return ['success' => true, 'invalidateCache' => ['backend', 'config']];
    }

    /**
     * @param $context UpdateContext
     * @return array
     */
    public function update(UpdateContext $context)
    {
        // sometimes plugin is not installed before
        // but could be updated. by this way setup process
        // is simple and only required structure will be created
        // e.g. DB and attributes
        $fullSetup = false;
        if ($this->isInstalled()) {
            $fullSetup = true;
        }

        $this->doSetup($context, $fullSetup);
        $this->doUpdate($context->getUpdateVersion());

        return ['success' => true, 'invalidateCache' => ['backend', 'config', 'template', 'theme']];
    }

    /**
     * Uninstall plugin method
     * @param $context UninstallContext
     * @return bool
     */
    public function uninstall(UninstallContext $context)
    {
        $this->doUninstall($context);

        return true;
    }

    /**
     * Performs the default setup of the system.
     *
     * This can be used by the update as well as by the install method
     *
     * @param InstallContext $context
     * @param bool $fullSetup
     * @throws RuntimeException
     */
    public function doSetup($context, $fullSetup = true)
    {
        $modelManager = Shopware()->Models();
        $setup = new Setup(
            $modelManager,
            Shopware()->Db(),
            new \ShopwarePlugins\Connect\Bootstrap\Menu(
                $this->getConfigComponents(),
                $modelManager,
                $context->assertMinimumVersion('5.2.6')
            )
        );
        $setup->run($fullSetup, $this->getPath());
    }

    /**
     * Performs the update of the system
     *
     * @param $version
     * @return bool
     */
    public function doUpdate($version)
    {
        $update = new Update(
            Shopware()->Models(),
            Shopware()->Db(),
            new Logger(Shopware()->Db()),
            $version
        );

        return $update->run();
    }

    /**
     * Uninstall the plugin
     * @param $context UninstallContext
     * @return bool
     */
    public function doUninstall($context)
    {
        $modelManager = Shopware()->Models();
        $uninstall = new Uninstall(
            $modelManager,
            Shopware()->Db(),
            new \ShopwarePlugins\Connect\Bootstrap\Menu(
                $this->getConfigComponents(),
                $modelManager,
                $context->assertMinimumVersion('5.2.6')
            )
        );

        return $uninstall->run();
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_InitResource_ConnectSDK' => 'onInitResourceSDK',
            'Enlight_Controller_Front_DispatchLoopStartup' => 'onStartDispatch',
            'Shopware_Console_Add_Command' => 'onConsoleAddCommand'
        ];
    }

    /**
     * Will dynamically register all needed events
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onStartDispatch(\Enlight_Event_EventArgs $args)
    {
        $this->container->get('template')->addTemplateDir($this->getPath() . '/Views/', 'connect');
        $this->container->get('snippets')->addConfigDir($this->getPath() . '/Snippets/');
//        $this->container->get('dbal_connection')->exec("UPDATE s_core_menu SET active = 0 WHERE name ='Import'");
        $this->registerSubscribers();
    }

    /**
     * @return ArrayCollection
     */
    public function onConsoleAddCommand()
    {
        $this->registerSubscribers();

        return new ArrayCollection([
            new ApiEndpointCommand()
        ]);
    }

    public function onInitResourceSDK()
    {
        return $this->getConnectFactory()->createSDK();
    }

    /**
     * Lazy getter for the connectFactory
     *
     * @return ConnectFactory
     */
    public function getConnectFactory()
    {
        if (!$this->connectFactory) {
            $this->connectFactory = new ConnectFactory($this->getVersion());
        }

        return $this->connectFactory;
    }

    /**
     * @return \Shopware\Connect\SDK
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
        /** @var $plugin \Shopware\Models\Plugin\Plugin */
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
                $this->container->get('models'),
                $this->container->get('db'),
                $this,
                $this->container->get('events'),
                $this->getSDK(),
                $this->getConnectFactory(),
                $this->getHelper(),
                $this->container->get('service_container')
            );
        }

        $this->subscriberRegistration->registerSubscribers();
    }
}
