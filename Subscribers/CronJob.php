<?php

namespace ShopwarePlugins\Connect\Subscribers;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\ImageImport;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;

/**
 * Cronjob callback
 *
 * Class CronJob
 * @package ShopwarePlugins\Connect\Subscribers
 */
class CronJob extends BaseSubscriber
{
    /**
     * @var \ShopwarePlugins\Connect\Components\Config
     */
    private $configComponent;

    public function getSubscribedEvents()
    {
        //todo@sb: fix cronjobs via bin/console
        return array(
            'Shopware_CronJob_ShopwareConnectImportImages' => 'importImages',
            'Shopware_CronJob_ShopwareConnectUpdateProducts' => 'updateProducts',
        );
    }

    /**
     * @return ImageImport
     */
    public function getImageImport()
    {
        return new ImageImport(
            Shopware()->Models(),
            $this->getHelper(),
            Shopware()->Container()->get('thumbnail_manager'),
            new Logger(Shopware()->Db())
        );
    }

    /**
     * Import images of new products
     *
     * @param \Shopware_Components_Cron_CronJob $job
     * @return bool
     */
    public function importImages(\Shopware_Components_Cron_CronJob $job)
    {
        $limit = $this->getConfigComponent()->getConfig('articleImagesLimitImport', 10);
        $this->getImageImport()->import($limit);

        return true;
    }

    /**
     * Collect all own products and send them
     * to Connect system.
     *
     * Used to update products with many variants.
     *
     * @param \Shopware_Components_Cron_CronJob $job
     * @return bool
     */
    public function updateProducts(\Shopware_Components_Cron_CronJob $job)
    {
        $sourceIds = Shopware()->Db()->fetchCol(
            'SELECT source_id FROM s_plugin_connect_items WHERE shop_id IS NULL AND cron_update = 1 LIMIT 100'
        );

        if (empty($sourceIds)) {
            return true;
        }

        $this->getConnectExport()->export($sourceIds);

        $quotedSourceIds = Shopware()->Db()->quote($sourceIds);
        Shopware()->Db()->query("
            UPDATE s_plugin_connect_items
            SET cron_update = false
            WHERE source_id IN ($quotedSourceIds)"
        )->execute();

        return true;
    }

    /**
     * @return ConnectExport
     */
    private function getConnectExport()
    {
        return new ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            Shopware()->Models(),
            new ProductsAttributesValidator(),
            $this->getConfigComponent(),
            new ErrorHandler()
        );
    }

    private function getConfigComponent()
    {
        if (!$this->configComponent) {
            $this->configComponent = new Config(Shopware()->Models());
        }

        return $this->configComponent;
    }
}