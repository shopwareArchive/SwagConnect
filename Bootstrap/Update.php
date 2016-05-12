<?php

namespace ShopwarePlugins\Connect\Bootstrap;
use ShopwarePlugins\Connect\Components\CategoryExtractor;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceSettings;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceSettingsApplier;
use Shopware\Models\Order\Status;

/**
 * Updates existing versions of the plugin
 *
 * Class Update
 * @package ShopwarePlugins\Connect\Bootstrap
 */
class Update
{

    /** @var \Shopware_Plugins_Backend_SwagConnect_Bootstrap */
    protected $bootstrap;
    protected $version;

    public function __construct(\Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap, $version)
    {
        $this->bootstrap = $bootstrap;
        $this->version = $version;
    }

    public function run()
    {
        // When the dummy plugin is going to be installed, don't do the later updates
//        if (version_compare($this->version, '1.0.0', '<=')) {
//            return true;
//        }

        // Force an SDK re-verify
        $this->reVerifySDK();

        $this->createStreamField();
        $this->removeOldConnectMenu();
        $this->addCronUpdateFlag();
        $this->removeSnippets();

        return true;
    }

    /**
     * Forces the SDK to re-verify the API key
     */
    public function reVerifySDK()
    {
        Shopware()->Db()->query('
            UPDATE sw_connect_shop_config
            SET s_config = ?
            WHERE s_shop = "_last_update_"
            LIMIT 1; ',
            array(time() - 8 * 60 * 60 * 24)
        );
    }

    public function createStreamField()
    {
        if (version_compare($this->version, '0.0.1', '<=')) {
            Shopware()->Db()->query('
                ALTER TABLE s_plugin_connect_items
                ADD stream VARCHAR(255) NOT NULL
            ');
        }
    }

    public function removeOldConnectMenu()
    {
        if (version_compare($this->version, '0.0.2', '<=')) {
            $connectItem = $this->bootstrap->Menu()->findOneBy(array('label' => 'Shopware Connect'));
            if ($connectItem) {
                Shopware()->Models()->remove($connectItem);
                Shopware()->Models()->flush();
            }
        }
    }

    public function addCronUpdateFlag()
    {
        if (version_compare($this->version, '0.0.3', '<=')) {
            Shopware()->Db()->query('
                ALTER TABLE s_plugin_connect_items
                ADD cron_update TINYINT(1) NULL DEFAULT NULL
            ');
        }
    }

    public function removeSnippets()
    {
        if (version_compare($this->version, '0.0.5', '<=')) {
            Shopware()->Db()->query("
                DELETE FROM s_core_snippets WHERE namespace = 'backend/connect/view/main'
            ");
        }
    }

    private function clearTemplateCache()
    {
        // check shopware version, because Shopware()->Container()
        // is available after version 4.2.x
        if (version_compare(Shopware()->Config()->version, '4.2.0', '<')) {
            Shopware()->Template()->clearAllCache();
        } else {
            $cacheManager = Shopware()->Container()->get('shopware.cache_manager');
            $cacheManager->clearTemplateCache();
        }
    }

    private function clearConfigCache()
    {
        // check shopware version, because Shopware()->Container()
        // is available after version 4.2.x
        if (version_compare(Shopware()->Config()->version, '4.2.0', '<')) {
            Shopware()->Template()->clearAllCache();
        } else {
            $cacheManager = Shopware()->Container()->get('shopware.cache_manager');
            $cacheManager->clearConfigCache();
        }
    }
}