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
        $this->renameConnectChangeColumns();
        $this->renameMenuOpenConnect();
        $this->changeMenuIcons();

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
        if (version_compare($this->version, '0.0.6', '<=')) {
            Shopware()->Db()->query("
                DELETE FROM s_core_snippets WHERE namespace = 'backend/connect/view/main'
            ");
        }
    }

    public function renameConnectChangeColumns()
    {
        if (version_compare($this->version, '0.0.7', '<=')) {
            Shopware()->Db()->query("
                ALTER TABLE `sw_connect_change` CHANGE `c_source_id` `c_entity_id` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
                ALTER TABLE `sw_connect_change` CHANGE `c_product` `c_payload` LONGBLOB NULL DEFAULT NULL;
            ");
        }
    }

    public function renameMenuOpenConnect()
    {
        if (version_compare($this->version, '0.0.9', '<=')) {
            Shopware()->Db()->query("
                UPDATE `s_core_menu`
                SET `name` = 'OpenConnect', `onclick` = 'window.open(''connect/autoLogin'')', `action` = 'OpenConnect', `controller` = 'Connect'
                WHERE `name` = 'OpenConnect' OR `name` = 'Open Connect';
            ");

            Shopware()->Db()->query("
                UPDATE `s_core_snippets`
                SET `value` = 'Login'
                WHERE `name` = 'Connect/OpenConnect';
            ");

        }
    }

    public function changeMenuIcons()
    {
        if (version_compare($this->version, '0.0.10', '<=')) {
            Shopware()->Db()->query("
                UPDATE `s_core_menu`
                SET `class` = 'sc-icon-import'
                WHERE `controller` = 'Connect' AND `name` = 'Import'
            ");

            Shopware()->Db()->query("
                UPDATE `s_core_menu`
                SET `class` = 'sc-icon-export'
                WHERE `controller` = 'Connect' AND `name` = 'Export'
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