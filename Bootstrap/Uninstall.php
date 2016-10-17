<?php

namespace ShopwarePlugins\Connect\Bootstrap;

use Shopware\Bundle\AttributeBundle\Service\CrudService;
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
    protected $bootstrap;
    protected $shopware526installed;

    public function __construct(\Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap, $shopware526installed)
    {
        $this->bootstrap = $bootstrap;
        $this->shopware526installed = $shopware526installed;
    }

    public function run()
    {
        // Currently this should not be done
//         $this->removeMyAttributes();

        $this->setMenuItem();
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

            Shopware()->Models()->generateAttributeModels(array(
                's_premium_dispatch_attributes',
                's_categories_attributes',
                's_order_details_attributes',
                's_order_basket_attributes',
                's_media_attributes'
            ));
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
        Shopware()->Db()->exec($sql);
    }

    /**
     * Remove an engine element so that the connectProductDescription is not displayed in the article anymore
     */
    public function removeEngineElement()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Article\Element');
        $element = $repo->findOneBy(array('name' => 'connectProductDescription'));

        if ($element) {
            Shopware()->Models()->remove($element);
            Shopware()->Models()->flush();
        }
    }

    /**
     * Re-Activate the connect install menu item, if version is >= 5.2.6
     */
    public function setMenuItem()
    {
        if ($this->shopware526installed) {
            $connectInstallItem = $this->bootstrap->Menu()->findOneBy(array('label' => 'Einstieg', 'action' => 'ShopwareConnect'));
            if (null !== $connectInstallItem) {
                $connectInstallItem->setActive(1);
                Shopware()->Models()->persist($connectInstallItem);
                Shopware()->Models()->flush();
            } else {
                $this->bootstrap->createMenuItem(array(
                    'label' => 'Einstieg',
                    'controller' => 'PluginManager',
                    'class' => 'sprite-inbox-image contents--media-manager',
                    'action' => 'ShopwareConnect',
                    'active' => 1,
                ));
            }
        }
    }
}