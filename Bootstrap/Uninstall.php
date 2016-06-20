<?php

namespace ShopwarePlugins\Connect\Bootstrap;

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

    public function __construct(\Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function run()
    {
        // Currently this should not be done
        // $this->removeMyAttributes();

        $this->deactivateConnectProducts();
        $this->removeEngineElement();
        $this->deleteTables();

        return true;
    }

    /**
     * Remove the attributes when uninstalling the plugin
     */
    public function removeMyAttributes()
    {
        /** @var \Shopware\Components\Model\ModelManager $modelManager */
        $modelManager = Shopware()->Models();

        try {
            $modelManager->removeAttribute(
                's_order_attributes',
                'connect', 'shop_id'
            );
            $modelManager->removeAttribute(
                's_order_attributes',
                'connect', 'order_id'
            );

            $modelManager->removeAttribute(
                's_categories_attributes',
                'connect', 'import_mapping'
            );

            $modelManager->removeAttribute(
                's_categories_attributes',
                'connect', 'export_mapping'
            );

            $modelManager->removeAttribute(
                's_categories_attributes',
                'connect', 'imported'
            );

            $modelManager->removeAttribute(
                's_premium_dispatch_attributes',
                'connect', 'allowed'
            );

            $modelManager->removeAttribute(
                's_media_attributes',
                'connect', 'hash'
            );

            $modelManager->generateAttributeModels(array(
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

    public function deleteTables()
    {
        Shopware()->Db()->executeQuery('DROP TABLE sw_connect_change');
        Shopware()->Db()->executeQuery('DROP TABLE sw_connect_data');
        Shopware()->Db()->executeQuery('DROP TABLE sw_connect_product');
        Shopware()->Db()->executeQuery('DROP TABLE sw_connect_reservations');
        Shopware()->Db()->executeQuery('DROP TABLE sw_connect_shipping_costs');
        Shopware()->Db()->executeQuery('DROP TABLE sw_connect_shipping_rules');
        Shopware()->Db()->executeQuery('DROP TABLE sw_connect_shop_config');
        Shopware()->Db()->executeQuery('DROP TABLE s_plugin_connect_categories');
        Shopware()->Db()->executeQuery('DROP TABLE s_plugin_connect_config');
        Shopware()->Db()->executeQuery('DROP TABLE s_plugin_connect_items');
        Shopware()->Db()->executeQuery('DROP TABLE s_plugin_connect_log');
        Shopware()->Db()->executeQuery('DROP TABLE s_plugin_connect_marketplace_attr');
        Shopware()->Db()->executeQuery('DROP TABLE s_plugin_connect_product_to_categories');
        Shopware()->Db()->executeQuery('DROP TABLE s_plugin_connect_streams');
    }
}