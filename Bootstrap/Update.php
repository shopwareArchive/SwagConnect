<?php

namespace ShopwarePlugins\Connect\Bootstrap;

use Shopware\CustomModels\Connect\Attribute;
use Shopware\Components\Model\ModelManager;
use Enlight_Components_Db_Adapter_Pdo_Mysql as Pdo;
use Shopware\Models\Attribute\Configuration;
use ShopwarePlugins\Connect\Components\ProductQuery\BaseProductQuery;

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
        $this->updateProductDescriptionSetting();

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

            $tableName = $this->modelManager->getClassMetadata('Shopware\Models\Attribute\Article')->getTableName();
            $columnName = 'connect_product_description';

            $repo = $this->modelManager->getRepository('Shopware\Models\Attribute\Configuration');
            $element = $repo->findOneBy([
                'tableName' => $tableName,
                'columnName' => $columnName,
            ]);

            if (!$element) {
                $element = new Configuration();
                $element->setTableName($tableName);
                $element->setColumnName($columnName);
            }

            $element->setColumnType('html');
            $element->setTranslatable(true);
            $element->setLabel('Connect Beschreibung');
            $element->setDisplayInBackend(true);

            $this->modelManager->persist($element);
            $this->modelManager->flush();
        }
    }

    private function updateProductDescriptionSetting()
    {
        if (version_compare($this->version, '1.0.10', '<=')) {
            //migrates to the new export settings
            $result = $this->db->query("SELECT `value` FROM s_plugin_connect_config WHERE name = 'alternateDescriptionField'");
            $row = $result->fetch();

            if ($row) {
                $mapper = [
                    'a.description' => BaseProductQuery::SHORT_DESCRIPTION_FIELD,
                    'a.descriptionLong' => BaseProductQuery::LONG_DESCRIPTION_FIELD,
                    'attribute.connectProductDescription' => BaseProductQuery::CONNECT_DESCRIPTION_FIELD,
                ];

                if ($name = $mapper[$row['value']]) {
                    $result = $this->db->query("SELECT `id` FROM s_plugin_connect_config WHERE name = '$name'");
                    $id = $result->fetch()['id'];

                    $this->db->query("
                        REPLACE INTO `s_plugin_connect_config`
                        (`id`, `name`, `value`, `shopId`, `groupName`)
                        VALUES
                        ($id, '$name', 1, null, 'export')
                     ");
                }
            }

            $this->db->query("
                ALTER TABLE `s_plugin_connect_items`
                ADD `update_additional_description` VARCHAR(255) NULL DEFAULT 'inherit' AFTER `update_short_description`;
            ");
        }
    }
}