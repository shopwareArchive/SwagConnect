<?php

namespace ShopwarePlugins\Connect\Services;


use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Menu\Menu;

class MenuService
{
    /**
     * @var InstallerService
     */
    private $installerService;

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * MenuService constructor.
     * @param InstallerService $installerService
     * @param ModelManager $manager
     */
    public function __construct(
        InstallerService $installerService,
        ModelManager $manager
    ) {
        $this->installerService = $installerService;
        $this->manager = $manager;
        $this->connection = $manager->getConnection();
    }


    /**
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function createRegisterMenu()
    {
        $plugin = $this->installerService->getPluginByName('SwagConnect');

        /** @var Menu $menuItem */
        $menuItem = $this->manager->getRepository(Menu::class)->findOneBy(['label' => 'Connect']);

        $this->connection->delete('s_core_menu', ['controller' => 'Connect', 'pluginID' => $plugin->getId()]);
        $this->connection->insert('s_core_menu', [
            'Name' => 'Register',
            'class' => 'sprite-mousepointer-click',
            'active' => 1,
            'pluginID' => $plugin->getId(),
            'controller' => 'Connect',
            'action' => 'Register',
            'parent' => $menuItem->getId(),
        ]);
    }
}