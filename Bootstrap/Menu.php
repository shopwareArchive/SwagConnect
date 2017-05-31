<?php

namespace ShopwarePlugins\Connect\Bootstrap;

use ShopwarePlugins\Connect\Components\Config;
use Shopware\Components\Model\ModelManager;

class Menu
{
    /**
     * menu item label
     */
    const CONNECT_LABEL = 'Connect';

    /**
     * menu item class name
     */
    const CONNECT_CLASS = 'shopware-connect';

    /**
     * @var \Shopware_Plugins_Backend_SwagConnect_Bootstrap
     */
    private $bootstrap;

    /**
     * @var \ShopwarePlugins\Connect\Components\Config
     */
    private $configComponent;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var string
     */
    private $shopware526installed;

    public function __construct(
        \Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap,
        Config $configComponent,
        ModelManager $modelManager,
        $shopware526installed
    )
    {
        $this->bootstrap = $bootstrap;
        $this->configComponent = $configComponent;
        $this->modelManager = $modelManager;
        $this->shopware526installed = $shopware526installed;
    }

    /**
     * @return bool
     */
    public function isExists()
    {
        $menuItem = $this->getMainMenuItem();
        if (!$menuItem) {
            return false;
        }

        return true;
    }

    /**
     * @return \Shopware\Models\Menu\Menu | null
     */
    public function getMainMenuItem()
    {
        return $this->bootstrap->Menu()->findOneBy([
            'class' => self::CONNECT_CLASS,
            'parent' => null,
        ]);
    }

    /**
     * Creates Shopware Connect menu
     */
    public function create()
    {
        $connectItem = $this->getMainMenuItem();
        // check if shopware Connect menu item exists
        if (!$connectItem || $this->shopware526installed) {
            if ($this->shopware526installed) {
                $connectInstallItem = $this->bootstrap->Menu()->findOneBy(['label' => 'Einstieg', 'action' => 'ShopwareConnect']);
                if (null !== $connectInstallItem) {
                    $connectInstallItem->setActive(0);
                    $this->modelManager->persist($connectInstallItem);
                    $this->modelManager->flush();
                }
            } else {
                //move help menu item after Connect
                $helpItem = $this->bootstrap->Menu()->findOneBy(['label' => '']);
                $helpItem->setPosition(1);
                $this->modelManager->persist($helpItem);
                $this->modelManager->flush();
            }

            if ($connectItem) {
                $connectItem->setActive(1);
                $this->modelManager->persist($connectItem);
                $this->modelManager->flush();
            }

            $parent = $this->bootstrap->Menu()->findOneBy(['class' => self::CONNECT_CLASS]);
            if (null === $parent) {
                $parent = $this->bootstrap->createMenuItem([
                    'label' => self::CONNECT_LABEL,
                    'class' => 'connect-icon',
                    'active' => 1,
                ]);

                if ($this->shopware526installed) {
                    $parent->setClass(Menu::CONNECT_CLASS);
                    //if "Connect" menu does not exist
                    //it must not have pluginID, because on plugin uninstall
                    //it will be removed
                    $parent->setPlugin(null);
                }
            }

            if ($this->configComponent->getConfig('apiKey', '') == ''
                && !$this->configComponent->getConfig('shopwareId')) {
                $registerItem = $this->bootstrap->Menu()->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'Register'
                ]);
                if (!$registerItem) {
                    $this->bootstrap->createMenuItem([
                        'label' => 'Register',
                        'controller' => 'Connect',
                        'action' => 'Register',
                        'class' => 'sprite-mousepointer-click',
                        'active' => 1,
                        'parent' => $parent
                    ]);
                }
            } else {
                // check if menu item already exists
                // this is possible when start update,
                // because setup function is called
                $importItem = $this->bootstrap->Menu()->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'Import'
                ]);
                if (!$importItem) {
                    $this->bootstrap->createMenuItem([
                        'label' => 'Import',
                        'controller' => 'Connect',
                        'action' => 'Import',
                        'class' => 'sc-icon-import',
                        'active' => 1,
                        'parent' => $parent
                    ]);
                }

                $exportItem = $this->bootstrap->Menu()->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'Export'
                ]);
                if (!$exportItem) {
                    $this->bootstrap->createMenuItem([
                        'label' => 'Export',
                        'controller' => 'Connect',
                        'action' => 'Export',
                        'class' => 'sc-icon-export',
                        'active' => 1,
                        'parent' => $parent
                    ]);
                }

                $settingsItem = $this->bootstrap->Menu()->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'Settings'
                ]);
                if (!$settingsItem) {
                    $this->bootstrap->createMenuItem([
                        'label' => 'Settings',
                        'controller' => 'Connect',
                        'action' => 'Settings',
                        'class' => 'sprite-gear',
                        'active' => 1,
                        'parent' => $parent
                    ]);
                }

                $openConnectItem = $this->bootstrap->Menu()->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'OpenConnect'
                ]);
                if (!$openConnectItem) {
                    $this->bootstrap->createMenuItem([
                        'label' => 'OpenConnect',
                        'controller' => 'Connect',
                        'action' => 'OpenConnect',
                        'onclick' => 'window.open("connect/autoLogin")',
                        'class' => 'connect-icon',
                        'active' => 1,
                        'parent' => $parent
                    ]);
                }
            }

        }
    }

    /**
     * Re-Activate the connect install menu item, if version is >= 5.2.6
     */
    public function remove()
    {
        if (!$this->shopware526installed) {
            return;
        }

        //if it is sem demo marketplace it will not find the correct menu item
        $connectMainMenu = $this->bootstrap->Menu()->findOneBy([
            'class' => Menu::CONNECT_CLASS,
            'parent' => null,
        ]);

        if (!$connectMainMenu) {
            $connectMainMenu = $this->bootstrap->createMenuItem([
                'label' => Menu::CONNECT_LABEL,
                'class' => Menu::CONNECT_CLASS,
                'active' => 1,
            ]);
            $connectMainMenu->setPlugin(null);
        } else {
            //resets the label if it's changed (diff marketplace)
            $connectMainMenu->setLabel(Menu::CONNECT_LABEL);
        }

        $connectInstallItem = $this->bootstrap->Menu()->findOneBy(['label' => 'Einstieg', 'action' => 'ShopwareConnect']);
        if (null !== $connectInstallItem) {
            $connectInstallItem->setActive(1);
            $connectInstallItem->setParent($connectMainMenu);
        } else {
            $connectInstallItem = $this->bootstrap->createMenuItem([
                'label' => 'Einstieg',
                'controller' => 'PluginManager',
                'class' => 'sprite-mousepointer-click',
                'action' => 'ShopwareConnect',
                'active' => 1,
                'parent' => $connectMainMenu
            ]);
            $connectInstallItem->setPlugin(null);
        }
    }
}