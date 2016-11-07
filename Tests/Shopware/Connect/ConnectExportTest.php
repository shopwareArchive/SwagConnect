<?php

namespace Tests\ShopwarePlugins\Connect;

use Shopware\CustomModels\Connect\Attribute;
use Shopware\Models\Article\Configurator\Group;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use ShopwarePlugins\Connect\Components\Config;

class ConnectExportTest extends ConnectTestHelper
{
    /**
     * @var \ShopwarePlugins\Connect\Components\ConnectExport
     */
    private $connectExport;

    /**
     * @var \ShopwarePlugins\Connect\Components\Config
     */
    private $config;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $conn = Shopware()->Db();
        $conn->delete('sw_connect_shop_config', array('s_shop = ?' => '_price_type'));
        $conn->insert('sw_connect_shop_config', array('s_shop' => '_price_type', 's_config' => 3));
    }

    public function setUp()
    {
        parent::setUp();

        $this->manager = Shopware()->Models();
        $this->config = new Config($this->manager);
        $this->connectExport = new ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            $this->manager,
            new ProductsAttributesValidator(),
            new Config($this->manager),
            new ErrorHandler()
        );

        if (method_exists('Shopware\Models\Article\Detail', 'setPurchasePrice')) {
            $purchasePrice = 'detailPurchasePrice';
        } else {
            $purchasePrice = 'basePrice';
        }
        $configs = array(
            'priceGroupForPriceExport' => array('EK', null, 'export'),
            'priceGroupForPurchasePriceExport' => array('EK', null, 'export'),
            'priceFieldForPriceExport' => array('price', null, 'export'),
            'priceFieldForPurchasePriceExport' => array($purchasePrice, null, 'export'),
        );

        foreach ($configs as $name => $values) {
            list($value, $shopId, $group) = $values;

            $this->config->setConfig(
                $name,
                $value,
                $shopId,
                $group
            );
        }
    }

    public function testExport()
    {
        /** @var \Shopware\Models\Article\Article $model */
        $model = $this->manager->getRepository('Shopware\Models\Article\Article')->find(3);
        $detail = $model->getMainDetail();
        /** @var \Shopware\Models\Article\Price $prices */
        $prices = $detail->getPrices();
        if (method_exists($detail, 'setPurchasePrice')) {
            $detail->setPurchasePrice(9.89);
        } else {
            $prices[0]->setBasePrice(9.89);
            $detail->setPrices($prices);
        }

        $this->manager->persist($detail);

        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($model);
        $connectAttribute->setExportStatus(Attribute::STATUS_INSERT);
        $connectAttribute->setExported(true);
        $this->manager->persist($connectAttribute);
        $this->manager->flush();
        $this->manager->clear();

        $errors = $this->connectExport->export(array(3));

        $this->assertEmpty($errors);
        $sql = 'SELECT export_status, export_message, exported FROM s_plugin_connect_items WHERE article_detail_id = ?';
        $row = Shopware()->Db()->fetchRow($sql, array($detail->getId()));

        $this->assertEquals('update', $row['export_status']);
        $this->assertNull($row['export_message']);
        $this->assertEquals(1, $row['exported']);
    }

    public function testExportErrors()
    {
        $model = $this->manager->getRepository('Shopware\Models\Article\Article')->find(4);
        $detail = $model->getMainDetail();
        $detail->setPurchasePrice(0);
        $this->manager->persist($detail);
        $this->manager->flush();

        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($model);
        $errors = $this->connectExport->export(array(4));

        $this->assertNotEmpty($errors);

        $sql = 'SELECT export_status, export_message FROM s_plugin_connect_items WHERE article_id = ?';
        $row = Shopware()->Db()->fetchRow($sql, array(4));

        $this->assertEquals('error-price', $row['export_status']);
        $this->assertContains('Ein Preisfeld für dieses Produkt ist nicht gepfegt', $row['export_message']);
    }

    public function testSyncDeleteArticle()
    {
        $articleId = $this->insertVariants();
        $modelManager = $this->manager;
        $article = $modelManager->getRepository('Shopware\Models\Article\Article')->find($articleId);

        $this->connectExport->setDeleteStatusForVariants($article);
        $result = Shopware()->Db()->executeQuery(
            'SELECT export_status, exported
              FROM s_plugin_connect_items
              WHERE article_id = ?',
            array($articleId)
        )->fetchAll();

        foreach ($result as $connectAttribute) {
            $this->assertEquals('delete', $connectAttribute['export_status']);
            $this->assertEquals(0, $connectAttribute['exported']);
        }

        $this->assertEquals(4, Shopware()->Db()->query('SELECT COUNT(*) FROM sw_connect_change WHERE c_entity_id LIKE "1919%"')->fetchColumn());
    }

    public function testDeleteVariant()
    {
        $articleId = $this->insertVariants();
        $modelManager = $this->manager;
        /** @var \Shopware\Models\Article\Article $article */
        $article = $modelManager->getRepository('Shopware\Models\Article\Article')->find($articleId);
        $detail = $article->getMainDetail();

        $this->connectExport->syncDeleteDetail($detail);

        $this->assertEquals(1, Shopware()->Db()->query('SELECT COUNT(*) FROM sw_connect_change WHERE c_entity_id LIKE "1919%"')->fetchColumn());
        $this->assertEquals(1, Shopware()->Db()->query('SELECT COUNT(*) FROM s_plugin_connect_items WHERE source_id = "1919" AND export_status = "delete"')->fetchColumn());
    }

    private function insertVariants()
    {
        //clear connect_change table
        Shopware()->Db()->exec('DELETE FROM sw_connect_change WHERE c_entity_id LIKE "1919%"');
        //clear s_articles table
        Shopware()->Db()->exec('DELETE FROM s_articles WHERE name = "Turnschuh"');
        //clear s_articles_detail table
        Shopware()->Db()->exec('DELETE FROM s_articles_details WHERE ordernumber LIKE "1919%"');
        //clear connect_items table
        Shopware()->Db()->exec('DELETE FROM s_plugin_connect_items WHERE source_id LIKE "1919%"');


        $minimalTestArticle = array(
            'name' => 'Turnschuh',
            'active' => true,
            'tax' => 19,
            'supplier' => 'Turnschuh Inc.',
            'categories' => array(
                array('id' => 15),
            ),
            'mainDetail' => array(
                'number' => '1919',
            ),
        );

        $articleResource = \Shopware\Components\Api\Manager::getResource('article');
        /** @var \Shopware\Models\Article\Article $article */
        $article = $articleResource->create($minimalTestArticle);

        $updateArticle = array(
            'configuratorSet' => array(
                'groups' => array(
                    array(
                        'name' => 'Größe',
                        'options' => array(
                            array('name' => 'S'),
                            array('name' => 'M'),
                            array('name' => 'L'),
                            array('name' => 'XL'),
                            array('name' => 'XXL'),
                        )
                    ),
                    array(
                        'name' => 'Farbe',
                        'options' => array(
                            array('name' => 'Weiß'),
                            array('name' => 'Gelb'),
                            array('name' => 'Blau'),
                            array('name' => 'Schwarz'),
                            array('name' => 'Rot'),
                        )
                    ),
                )
            ),
            'taxId' => 1,
            'variants' => array(
                array(
                    'isMain' => true,
                    'number' => '1919',
                    'inStock' => 15,
                    'standard' => null,
                    'additionaltext' => 'L / Schwarz',
                    'configuratorOptions' => array(
                        array('group' => 'Größe', 'groupId' => null, 'optionId' => null, 'option' => 'L'),
                        array('group' => 'Farbe', 'groupId' => null, 'optionId' => null, 'option' => 'Schwarz'),
                    ),
                ),
                array(
                    'isMain' => false,
                    'number' => '1919-1',
                    'inStock' => 15,
                    'standard' => null,
                    'additionnaltext' => 'S / Schwarz',
                    'configuratorOptions' => array(
                        array('group' => 'Größe', 'groupId' => null, 'optionId' => null,'option' => 'S'),
                        array('group' => 'Farbe', 'groupId' => null, 'optionId' => null, 'option' => 'Schwarz'),
                    ),
                ),
                array(
                    'isMain' => false,
                    'number' => '1919-2',
                    'inStock' => 15,
                    'standard' => null,
                    'additionnaltext' => 'S / Rot',
                    'configuratorOptions' => array(
                        array('group' => 'Größe', 'groupId' => null, 'optionId' => null,'option' => 'S'),
                        array('group' => 'Farbe', 'groupId' => null, 'optionId' => null,'option' => 'Rot'),
                    ),
                ),
                array(
                    'isMain' => false,
                    'number' => '1919-3',
                    'inStock' => 15,
                    'standard' => null,
                    'additionnaltext' => 'XL / Rot',
                    'configuratorOptions' => array(
                        array('group' => 'Größe', 'groupId' => null, 'optionId' => null, 'option' => 'XL'),
                        array('group' => 'Farbe', 'groupId' => null, 'optionId' => null,'option' => 'Rot'),
                    ),
                )
            )
        );

        /** @var \Shopware\Models\Article\Article $article */
        $article = $articleResource->update($article->getId(), $updateArticle);

        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            Shopware()->Db()->executeQuery(
                'INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, category, exported)
                  VALUES (?, ?, ?, ?, ?)',
                array(
                    $article->getId(),
                    $detail->getId(),
                    $detail->getNumber(),
                    '/bücher',
                    1
                ));
        }

        return $article->getId();
    }

    //todo: test export marketplace attributes
}
 
