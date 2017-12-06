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
     * @return \Shopware\Models\Menu\Menu
     */
    public function getMainMenuItem()
    {
        $menuItem = $this->menuRepository->findOneBy([
            'class' => self::CONNECT_CLASS,
            'parent' => null,
        ]);

        if(!$menuItem) {
            throw new \RuntimeException('Could not find entry');
        }

        return $menuItem;
    }

    /**
     * @param array $selector
     * @return mixed
     * @throws \RuntimeException
     */
    private function fetchOneBy(array $selector)
    {
        $menuItem = $this->menuRepository->findOneBy($selector);

        if(!$menuItem) {
            throw new \RuntimeException('Could not find entry');
        }

        return $menuItem;
    }

    /**
     * Creates Shopware Connect menu
     */
    public function synchronize()
    {

        try {
            $connectMainMenu = $this->getMainMenuItem();
        } catch (\RuntimeException $e) {
            $connectMainMenu = $this->createConnectMainMenu();
        }


        $this->activateConnectMenuItem($connectMainMenu);
        $this->deactivateInstallConnectMenuItem();

        if ($this->isUnregistered()) {
            $this->createRegisterMenuItem($connectMainMenu);
            $this->removeMainRegisteredMenu();
        } else {
            $this->createMainRegisteredMenu($connectMainMenu);
            $this->removeRegisterMenu();
        }
    }

    public function remove()
    {

        $this->removeRegisterMenu();
        $this->removeMainRegisteredMenu();

        if (!$this->shopware526installed) {
            return;
        }
        $this->resetInstallConnectMenu();
    }

    private function removeMainRegisteredMenu()
    {
        $this->removeMenuItem(['label' => 'Import', 'action' => 'Import']);
        $this->removeMenuItem(['label' => 'Export', 'action' => 'Export']);
        $this->removeMenuItem(['label' => 'Settings', 'action' => 'Settings']);
        $this->removeMenuItem(['label' => 'OpenConnect', 'action' => 'OpenConnect']);
    }

    private function removeRegisterMenu()
    {
        $this->removeMenuItem(['label' => 'Register', 'controller' => 'Connect', 'action' => 'Register',]);
    }

    /**
     * @param array $selector
     */
    private function removeMenuItem($selector)
    {
        try {
            $menuItem = $this->fetchOneBy($selector);
            $this->modelManager->remove($menuItem);
        } catch (\RuntimeException $e) {
            return;
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

    /**
     * @return bool
     */
    private function isUnregistered()
    {
        return $this->configComponent->getConfig('apiKey', '') == ''
            && !$this->configComponent->getConfig('shopwareId');
    }

    /**
     * @param \Shopware\Models\Menu\Menu $parent
     */
    private function createRegisterMenuItem(\Shopware\Models\Menu\Menu $parent)
    {
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
    }

    private function resetInstallConnectMenu()
    {
        try {
            //if it is sem demo marketplace it will not find the connectMainMenu
            $connectMainMenu = $this->getMainMenuItem();
        } catch (\RuntimeException $e) {
            $connectMainMenu = $this->createConnectMainMenu();
        }

        //resets the label if it's changed (diff marketplace)
        $connectMainMenu->setLabel(self::CONNECT_LABEL);

        try {
            $connectInstallItem = $this->fetchOneBy(['label' => 'Einstieg', 'action' => 'ShopwareConnect']);
            $connectInstallItem->setParent($connectMainMenu);
            $this->activateConnectMenuItem($connectInstallItem);
        } catch (\RuntimeException $e) {
            $this->createInstallConnectMenu($connectMainMenu);
        }
    }

    /**
     * @param \Shopware\Models\Menu\Menu $parent
     */
    private function createMainRegisteredMenu(\Shopware\Models\Menu\Menu $parent)
    {
        // check if menu item already exists
        // this is possible when start update,
        // because setup function is called
        if($this->isMenuAlreadyCreated()) {
            return;
        }

        $this->createMenuItem([
            'label' => 'Import',
            'controller' => 'Connect',
            'action' => 'Import',
            'class' => 'sc-icon-import',
            'active' => 1,
            'parent' => $parent
        ]);

        $this->createMenuItem([
            'label' => 'Export',
            'controller' => 'Connect',
            'action' => 'Export',
            'class' => 'sc-icon-export',
            'active' => 1,
            'parent' => $parent
        ]);

        $this->createMenuItem([
            'label' => 'Settings',
            'controller' => 'Connect',
            'action' => 'Settings',
            'class' => 'sprite-gear',
            'active' => 1,
            'parent' => $parent
        ]);

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

    private function deactivateInstallConnectMenuItem()
    {
        try {
            $connectInstallItem = $this->fetchOneBy(['label' => 'Einstieg', 'action' => 'ShopwareConnect']);

            $connectInstallItem->setActive(0);
            $this->modelManager->persist($connectInstallItem);
            $this->modelManager->flush();
        } catch (\RuntimeException $e) {
        }
    }

    private function isMenuAlreadyCreated()
    {
        try {
            $importItem = $this->fetchOneBy([
                'controller' => 'Connect',
                'action' => 'Import'
            ]);

            return true;
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * @param \Shopware\Models\Menu\Menu $connectItem
     */
    private function activateConnectMenuItem(\Shopware\Models\Menu\Menu $connectItem)
    {
        $connectItem->setActive(1);
        $this->modelManager->persist($connectItem);
        $this->modelManager->flush();
    }

    /**
     * @param \Shopware\Models\Menu\Menu $connectMainMenu
     */
    private function createInstallConnectMenu(\Shopware\Models\Menu\Menu $connectMainMenu)
    {
        $connectInstallItem = $this->createMenuItem([
            'label' => 'Einstieg',
            'controller' => 'PluginManager',
            'class' => 'sprite-mousepointer-click',
            'action' => 'ShopwareConnect',
            'active' => 1,
            'parent' => $connectMainMenu
        ]);
        $connectInstallItem->setPlugin(null);
        $this->modelManager->persist($connectInstallItem);
        $this->modelManager->flush();
    }

    /**
     * @return \Shopware\Models\Menu\Menu
     */
    private function createConnectMainMenu()
    {
        $connectMainMenu = $this->createMenuItem([
            'label' => self::CONNECT_LABEL,
            'class' => self::CONNECT_CLASS,
            'active' => 1,
        ]);
        $connectMainMenu->setPlugin(null);
        $this->modelManager->persist($connectMainMenu);
        $this->modelManager->flush();
        return $connectMainMenu;
    }
}
