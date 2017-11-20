<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class ConnectExportTest extends ConnectTestHelper
{
    use DatabaseTestCaseTrait;

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
        $conn->delete('sw_connect_shop_config', ['s_shop = ?' => '_price_type']);
        $conn->insert('sw_connect_shop_config', ['s_shop' => '_price_type', 's_config' => 3]);
    }

    public function setUp()
    {
        parent::setUp();

        $this->manager = Shopware()->Models();
        $this->config = ConfigFactory::getConfigInstance();
        $this->connectExport = new ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            $this->manager,
            new ProductsAttributesValidator(),
            $this->config,
            new ErrorHandler(),
            Shopware()->Container()->get('events')
        );

        if (method_exists('Shopware\Models\Article\Detail', 'setPurchasePrice')) {
            $purchasePrice = 'detailPurchasePrice';
        } else {
            $purchasePrice = 'basePrice';
        }
        $configs = [
            'priceGroupForPriceExport' => ['EK', null, 'export'],
            'priceGroupForPurchasePriceExport' => ['EK', null, 'export'],
            'priceFieldForPriceExport' => ['price', null, 'export'],
            'priceFieldForPurchasePriceExport' => [$purchasePrice, null, 'export'],
        ];

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

        //fixes wrong sort mode in demo data
        $propertyGroup = $model->getPropertyGroup();
        $propertyGroup->setSortMode(0);
        $this->manager->persist($propertyGroup);

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
        $connectAttribute->setExportStatus(null);
        $connectAttribute->setExported(false);
        $this->manager->persist($connectAttribute);
        $this->manager->flush();
        $this->manager->clear();

        $this->connectExport->export([3]);

        Shopware()->Db()->executeUpdate('UPDATE s_articles_details SET purchaseprice = ? WHERE id = ?', [10.01, $detail->getId()]);
        $errors = $this->connectExport->export([3]);

        $this->assertEmpty($errors);
        $sql = 'SELECT export_status, export_message, exported FROM s_plugin_connect_items WHERE article_detail_id = ?';
        $row = Shopware()->Db()->fetchRow($sql, [$detail->getId()]);

        $this->assertEquals('update', $row['export_status']);
        $this->assertNull($row['export_message']);
        $this->assertEquals(1, $row['exported']);
    }

    /**
     * @depends testExport
     */
    public function testExportErrors()
    {
        Shopware()->Db()->executeUpdate('UPDATE s_filter SET sortmode = 1');

        $model = $this->manager->getRepository('Shopware\Models\Article\Article')->find(4);
        $detail = $model->getMainDetail();
        $detail->setPurchasePrice(0);
        $this->manager->persist($detail);
        $this->manager->flush();

        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($model);
        $errors = $this->connectExport->export([4]);

        $this->assertNotEmpty($errors);

        $sql = 'SELECT export_status, export_message FROM s_plugin_connect_items WHERE article_id = ?';
        $row = Shopware()->Db()->fetchRow($sql, [4]);

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
            [$articleId]
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

    public function testDeleteNotExportedVariant()
    {
        $articleId = $this->insertVariants();
        $modelManager = $this->manager;
        /** @var \Shopware\Models\Article\Article $article */
        $article = $modelManager->getRepository('Shopware\Models\Article\Article')->find($articleId);
        $detail = $article->getMainDetail();
        Shopware()->Db()->executeUpdate('UPDATE s_plugin_connect_items SET export_status = NULL, exported = 0 where source_id = "1919"');
        $this->connectExport->syncDeleteDetail($detail);

        $this->assertEquals(0, Shopware()->Db()->query('SELECT COUNT(*) FROM sw_connect_change WHERE c_entity_id LIKE "1919%"')->fetchColumn());
        $this->assertEquals(1, Shopware()->Db()->query('SELECT COUNT(*) FROM s_plugin_connect_items WHERE source_id = "1919" AND export_status IS NULL')->fetchColumn());
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


        $minimalTestArticle = [
            'name' => 'Turnschuh',
            'active' => true,
            'tax' => 19,
            'supplier' => 'Turnschuh Inc.',
            'categories' => [
                ['id' => 15],
            ],
            'mainDetail' => [
                'number' => '1919',
            ],
        ];

        $articleResource = \Shopware\Components\Api\Manager::getResource('article');
        /** @var \Shopware\Models\Article\Article $article */
        $article = $articleResource->create($minimalTestArticle);

        $updateArticle = [
            'configuratorSet' => [
                'groups' => [
                    [
                        'name' => 'Größe',
                        'options' => [
                            ['name' => 'S'],
                            ['name' => 'M'],
                            ['name' => 'L'],
                            ['name' => 'XL'],
                            ['name' => 'XXL'],
                        ]
                    ],
                    [
                        'name' => 'Farbe',
                        'options' => [
                            ['name' => 'Weiß'],
                            ['name' => 'Gelb'],
                            ['name' => 'Blau'],
                            ['name' => 'Schwarz'],
                            ['name' => 'Rot'],
                        ]
                    ],
                ]
            ],
            'taxId' => 1,
            'variants' => [
                [
                    'isMain' => true,
                    'number' => '1919',
                    'inStock' => 15,
                    'standard' => null,
                    'additionaltext' => 'L / Schwarz',
                    'configuratorOptions' => [
                        ['group' => 'Größe', 'groupId' => null, 'optionId' => null, 'option' => 'L'],
                        ['group' => 'Farbe', 'groupId' => null, 'optionId' => null, 'option' => 'Schwarz'],
                    ],
                ],
                [
                    'isMain' => false,
                    'number' => '1919-1',
                    'inStock' => 15,
                    'standard' => null,
                    'additionnaltext' => 'S / Schwarz',
                    'configuratorOptions' => [
                        ['group' => 'Größe', 'groupId' => null, 'optionId' => null,'option' => 'S'],
                        ['group' => 'Farbe', 'groupId' => null, 'optionId' => null, 'option' => 'Schwarz'],
                    ],
                ],
                [
                    'isMain' => false,
                    'number' => '1919-2',
                    'inStock' => 15,
                    'standard' => null,
                    'additionnaltext' => 'S / Rot',
                    'configuratorOptions' => [
                        ['group' => 'Größe', 'groupId' => null, 'optionId' => null,'option' => 'S'],
                        ['group' => 'Farbe', 'groupId' => null, 'optionId' => null,'option' => 'Rot'],
                    ],
                ],
                [
                    'isMain' => false,
                    'number' => '1919-3',
                    'inStock' => 15,
                    'standard' => null,
                    'additionnaltext' => 'XL / Rot',
                    'configuratorOptions' => [
                        ['group' => 'Größe', 'groupId' => null, 'optionId' => null, 'option' => 'XL'],
                        ['group' => 'Farbe', 'groupId' => null, 'optionId' => null,'option' => 'Rot'],
                    ],
                ]
            ]
        ];

        /** @var \Shopware\Models\Article\Article $article */
        $article = $articleResource->update($article->getId(), $updateArticle);

        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            Shopware()->Db()->executeQuery(
                'INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, category, exported)
                  VALUES (?, ?, ?, ?, ?)',
                [
                    $article->getId(),
                    $detail->getId(),
                    $detail->getNumber(),
                    '/bücher',
                    1
                ]);
        }

        return $article->getId();
    }

    //todo: test export marketplace attributes
}
