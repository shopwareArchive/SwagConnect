<?php

namespace Shopware\Bepado\Bootstrap;

/**
 * Uninstaller of the plugin.
 * Currently attribute columns will never be removed, as well as the plugin tables. This can be changed once
 * shopware supports asking the user, if he wants to remove the plugin permanently or temporarily
 *
 * Class Uninstall
 * @package Shopware\Bepado\Bootstrap
 */
class Uninstall
{
    protected $bootstrap;

    public function __construct(\Shopware_Plugins_Backend_SwagBepado_Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function run()
    {
        // Currently this should not be done
        // $this->removeMyAttributes();

        $this->deactivateBepadoProducts();
        $this->removeEngineElement();

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
                'bepado', 'shop_id'
            );
            $modelManager->removeAttribute(
                's_order_attributes',
                'bepado', 'order_id'
            );

            $modelManager->removeAttribute(
                's_categories_attributes',
                'bepado', 'import_mapping'
            );

            $modelManager->removeAttribute(
                's_categories_attributes',
                'bepado', 'export_mapping'
            );

            $modelManager->removeAttribute(
                's_categories_attributes',
                'bepado', 'imported'
            );

            $modelManager->removeAttribute(
                's_premium_dispatch_attributes',
                'bepado', 'allowed'
            );

            $modelManager->removeAttribute(
                's_media_attributes',
                'bepado', 'hash'
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
     * Disabled all products imported from bepado
     */
    public function deactivateBepadoProducts()
    {
        $sql = '
        UPDATE s_articles
        INNER JOIN s_plugin_bepado_items
          ON s_plugin_bepado_items.article_id = s_articles.id
          AND shop_id IS NOT NULL
        SET s_articles.active = false
        ';
        Shopware()->Db()->exec($sql);
    }

    /**
     * Remove an engine element so that the bepadoProductDescription is not displayed in the article anymore
     */
    public function removeEngineElement()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Article\Element');
        $element = $repo->findOneBy(array('name' => 'bepadoProductDescription'));

        if ($element) {
            Shopware()->Models()->remove($element);
            Shopware()->Models()->flush();
        }
    }
}