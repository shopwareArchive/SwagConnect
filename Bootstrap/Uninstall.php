<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Bootstrap;

use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Enlight_Components_Db_Adapter_Pdo_Mysql as Pdo;

/**
 * Uninstaller of the plugin.
 * Currently attribute columns will never be removed, as well as the plugin tables. This can be changed once
 * shopware supports asking the user, if he wants to remove the plugin permanently or temporarily
 *
 * Class Uninstall
 * @package ShopwarePlugins\Connect\Bootstrap
 */
class Uninstall
{
    /**
     * @var \Shopware_Plugins_Backend_SwagConnect_Bootstrap
     */
    protected $bootstrap;

    /**
     * @var Pdo
     */
    protected $db;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var Menu
     */
    private $menu;

    /**
     * Setup constructor.
     * @param \Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap
     * @param ModelManager $modelManager
     * @param Pdo $db
     * @param Menu $menu
     */
    public function __construct(
        \Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap,
        ModelManager $modelManager,
        Pdo $db,
        Menu $menu
    ) {
        $this->bootstrap = $bootstrap;
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->menu = $menu;
    }

    public function run()
    {
        // Currently this should not be done
//         $this->removeMyAttributes();

        $this->menu->remove();
        $this->deactivateConnectProducts();
        $this->removeEngineElement();

        return true;
    }

    /**
     * @return CrudService
     */
    public function getCrudService()
    {
        return $this->bootstrap->Application()->Container()->get('shopware_attribute.crud_service');
    }

    /**
     * Remove the attributes when uninstalling the plugin
     */
    public function removeMyAttributes()
    {
        $crudService = $this->getCrudService();

        try {
            $crudService->delete(
                's_order_attributes',
                'connect_shop_id'
            );
            $crudService->delete(
                's_order_attributes',
                'connect_order_id'
            );

            $crudService->delete(
                's_categories_attributes',
                'connect_import_mapping'
            );

            $crudService->delete(
                's_categories_attributes',
                'connect_export_mapping'
            );

            $crudService->delete(
                's_categories_attributes',
                'connect_imported'
            );

            $crudService->delete(
                's_premium_dispatch_attributes',
                'connect_allowed'
            );

            $crudService->delete(
                's_media_attributes',
                'connect_hash'
            );

            $this->modelManager->generateAttributeModels([
                's_premium_dispatch_attributes',
                's_categories_attributes',
                's_order_details_attributes',
                's_order_basket_attributes',
                's_media_attributes'
            ]);
        } catch (\Exception $e) {
        }
    }

    /**
     * Disabled all products imported from shopware Connect
     */
    public function deactivateConnectProducts()
    {
        $sql = '
        UPDATE s_articles
        INNER JOIN s_plugin_connect_items
          ON s_plugin_connect_items.article_id = s_articles.id
          AND shop_id IS NOT NULL
        SET s_articles.active = false
        ';
        $this->db->exec($sql);
    }

    /**
     * Remove an engine element so that the connectProductDescription is not displayed in the article anymore
     */
    public function removeEngineElement()
    {
        $repo = $this->modelManager->getRepository('Shopware\Models\Article\Element');
        $element = $repo->findOneBy(['name' => 'connectProductDescription']);

        if ($element) {
            $this->modelManager->remove($element);
            $this->modelManager->flush();
        }
    }
}
