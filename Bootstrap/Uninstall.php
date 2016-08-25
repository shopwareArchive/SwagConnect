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

    /**
     * Re-Activate the connect install menu item, if version is >= 5.2.6
     */
    public function setMenuItem()
    {
        if ($this->bootstrap->assertMinimumVersion('5.2.6')) {
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