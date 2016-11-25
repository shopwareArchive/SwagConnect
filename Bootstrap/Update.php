<?php

namespace ShopwarePlugins\Connect\Bootstrap;

use Shopware\CustomModels\Connect\Attribute;
use Shopware\Components\Model\ModelManager;
use Enlight_Components_Db_Adapter_Pdo_Mysql as Pdo;

/**
 * Updates existing versions of the plugin
 *
 * Class Update
 * @package ShopwarePlugins\Connect\Bootstrap
 */
class Update
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
     * @var string
     */
    protected $version;

    /**
     * Setup constructor.
     * @param \Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap
     * @param ModelManager $modelManager
     * @param Pdo $db
     * @param $version
     */
    public function __construct
    (
        \Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap,
        ModelManager $modelManager,
        Pdo $db,
        $version
    ) {
        $this->bootstrap = $bootstrap;
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->version = $version;
    }

    public function run()
    {
        // Force an SDK re-verify
        $this->reVerifySDK();

        $this->createExportedFlag();
        $this->removeRedirectMenu();
        $this->updateConnectAttribute();
        $this->addConnectDescriptionElement();

        return true;
    }

    /**
     * Forces the SDK to re-verify the API key
     */
    public function reVerifySDK()
    {
        $this->db->query('
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
            $this->db->query("
                ALTER TABLE `s_plugin_connect_items`
                ADD COLUMN `exported` TINYINT(1) DEFAULT 0
            ");

            $this->db->query("
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
                $this->modelManager->remove($connectItem);
                $this->modelManager->flush();
            }
        }
    }

    private function updateConnectAttribute(){
        if (version_compare($this->version, '1.0.6', '<=')) {
            $result = $this->db->query("SELECT value FROM s_plugin_connect_config WHERE name = 'connectAttribute'");
            $row = $result->fetch();
            $attr = 19;
            if ($row) {
                $attr = $row['value'];
            }

            $this->db->query("
                    UPDATE `s_articles_attributes` 
                    SET `connect_reference` = `attr" . $attr . "` 
                    WHERE connect_reference IS NULL;
                ");

            $this->db->query("DELETE FROM s_plugin_connect_config WHERE name = 'connectAttribute'");
        }
    }

    private function addConnectDescriptionElement()
    {
        if (version_compare($this->version, '1.0.9', '<=')) {
            $this->db->query("
                INSERT INTO `s_attribute_configuration`
                (`table_name`, `column_name`, `column_type`, `translatable`, `display_in_backend`, `label`)
                VALUES ('s_articles_attributes', 'connect_product_description', 'html', 1, 1, 'Connect Beschreibung')
            ");
        }
    }
}