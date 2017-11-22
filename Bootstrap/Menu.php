<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private $menuRepository;

    public function __construct(
        Config $configComponent,
        ModelManager $modelManager,
        $shopware526installed
    ) {
        $this->configComponent = $configComponent;
        $this->modelManager = $modelManager;
        $this->shopware526installed = $shopware526installed;
        $this->menuRepository = Shopware()->Models()->getRepository(\Shopware\Models\Menu\Menu::class);
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
        return $this->menuRepository->findOneBy([
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
                $connectInstallItem = $this->menuRepository->findOneBy(['label' => 'Einstieg', 'action' => 'ShopwareConnect']);
                if (null !== $connectInstallItem) {
                    $connectInstallItem->setActive(0);
                    $this->modelManager->persist($connectInstallItem);
                    $this->modelManager->flush();
                }
            } else {
                //move help menu item after Connect
                $helpItem = $this->menuRepository->findOneBy(['label' => '']);
                $helpItem->setPosition(1);
                $this->modelManager->persist($helpItem);
                $this->modelManager->flush();
            }

            if ($connectItem) {
                $connectItem->setActive(1);
                $this->modelManager->persist($connectItem);
                $this->modelManager->flush();
            }

            $parent = $this->menuRepository->findOneBy(['class' => self::CONNECT_CLASS]);
            if (null === $parent) {
                $parent = $this->createMenuItem([
                    'label' => self::CONNECT_LABEL,
                    'class' => 'connect-icon',
                    'active' => 1,
                ]);

                if ($this->shopware526installed) {
                    $parent->setClass(self::CONNECT_CLASS);
                    //if "Connect" menu does not exist
                    //it must not have pluginID, because on plugin uninstall
                    //it will be removed
                    $parent->setPlugin(null);
                }
            }

            if ($this->configComponent->getConfig('apiKey', '') == ''
                && !$this->configComponent->getConfig('shopwareId')) {
                $registerItem = $this->menuRepository->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'Register'
                ]);
                if (!$registerItem) {
                    $this->createMenuItem([
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
                $importItem = $this->menuRepository->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'Import'
                ]);
                if (!$importItem) {
                    $this->createMenuItem([
                        'label' => 'Import',
                        'controller' => 'Connect',
                        'action' => 'Import',
                        'class' => 'sc-icon-import',
                        'active' => 1,
                        'parent' => $parent
                    ]);
                }

                $exportItem = $this->menuRepository->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'Export'
                ]);
                if (!$exportItem) {
                    $this->createMenuItem([
                        'label' => 'Export',
                        'controller' => 'Connect',
                        'action' => 'Export',
                        'class' => 'sc-icon-export',
                        'active' => 1,
                        'parent' => $parent
                    ]);
                }

                $settingsItem = $this->menuRepository->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'Settings'
                ]);
                if (!$settingsItem) {
                    $this->createMenuItem([
                        'label' => 'Settings',
                        'controller' => 'Connect',
                        'action' => 'Settings',
                        'class' => 'sprite-gear',
                        'active' => 1,
                        'parent' => $parent
                    ]);
                }

                $openConnectItem = $this->menuRepository->findOneBy([
                    'controller' => 'Connect',
                    'action' => 'OpenConnect'
                ]);
                if (!$openConnectItem) {
                    $this->createMenuItem([
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
        $connectMainMenu = $this->menuRepository->findOneBy([
            'class' => self::CONNECT_CLASS,
            'parent' => null,
        ]);

        if (!$connectMainMenu) {
            $connectMainMenu = $this->createMenuItem([
                'label' => self::CONNECT_LABEL,
                'class' => self::CONNECT_CLASS,
                'active' => 1,
            ]);
            $connectMainMenu->setPlugin(null);
        } else {
            //resets the label if it's changed (diff marketplace)
            $connectMainMenu->setLabel(self::CONNECT_LABEL);
        }

        $connectInstallItem = $this->menuRepository->findOneBy(['label' => 'Einstieg', 'action' => 'ShopwareConnect']);
        if (null !== $connectInstallItem) {
            $connectInstallItem->setActive(1);
            $connectInstallItem->setParent($connectMainMenu);
        } else {
            $connectInstallItem = $this->createMenuItem([
                'label' => 'Einstieg',
                'controller' => 'PluginManager',
                'class' => 'sprite-mousepointer-click',
                'action' => 'ShopwareConnect',
                'active' => 1,
                'parent' => $connectMainMenu
            ]);
            $connectInstallItem = $this->createMenuItem([
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

    /**
     * @param array $values
     * @return \Shopware\Models\Menu\Menu
     */
    private function createMenuItem($values)
    {
        $menu = new \Shopware\Models\Menu\Menu();
        $menu->setLabel($values['label']);
        $menu->setController($values['controller']);
        $menu->setClass($values['class']);
        $menu->setAction($values['action']);
        $menu->setActive($values['active']);
        $menu->setParent($values['parent']);

        $this->modelManager->persist($menu);
        $this->modelManager->flush();

        return $menu;
    }
}
