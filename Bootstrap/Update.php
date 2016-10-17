<?php

namespace ShopwarePlugins\Connect\Bootstrap;
use Shopware\CustomModels\Connect\Attribute;
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
        // Force an SDK re-verify
        $this->reVerifySDK();

        $this->createExportedFlag();
        $this->removeRedirectMenu();
        $this->updateConnectAttribute();

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

    private function createExportedFlag()
    {
        if (version_compare($this->version, '1.0.1', '<=')) {
            Shopware()->Db()->query("
                ALTER TABLE `s_plugin_connect_items`
                ADD COLUMN `exported` TINYINT(1) DEFAULT 0
            ");

            Shopware()->Db()->query("
                UPDATE `s_plugin_connect_items`
                SET `exported` = 1
                WHERE (`export_status` = ? OR `export_status` = ? OR `export_status` = ?) AND `shop_id` IS NULL",
                array(Attribute::STATUS_INSERT, Attribute::STATUS_UPDATE, Attribute::STATUS_SYNCED)
            );
        }
    }

    private function removeRedirectMenu()
    {
        if (version_compare($this->version, '1.0.4', '<=')) {
            $connectItem = $this->bootstrap->Menu()->findOneBy(array('label' => 'Open Connect', 'action' => ''));
            if ($connectItem) {
                Shopware()->Models()->remove($connectItem);
                Shopware()->Models()->flush();
            }
        }
    }

    private function updateConnectAttribute(){
        if (version_compare($this->version, '1.0.6', '<=')) {
            $result = Shopware()->Db()->query("SELECT value FROM s_plugin_connect_config WHERE name = 'connectAttribute'");
            $row = $result->fetch();
            $attr = 19;
            if ($row) {
                $attr = $row['value'];
            }

            Shopware()->Db()->query("
                    UPDATE `s_articles_attributes` 
                    SET `connect_reference` = `attr" . $attr . "` 
                    WHERE connect_reference IS NULL;
                ");

            Shopware()->Db()->query("DELETE FROM s_plugin_connect_config WHERE name = 'connectAttribute'");
        }
    }
}