<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Subscribers;

use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;
use ShopwarePlugins\Connect\Components\ErrorHandler;

/** Please don't rename it because this test will fail if the classname is LifecycleTest */
class LifecycleTestLegacy extends ConnectTestHelper
{
    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;

    private $db;

    /** @var Config */
    private $config;

    private $connectExport;

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

        // disable auth and acl
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();

        $this->manager = Shopware()->Models();
        $this->db = Shopware()->Db();
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

        $configs = [
            'priceGroupForPriceExport' => ['EK', null, 'export'],
            'priceGroupForPurchasePriceExport' => ['EK', null, 'export'],
            'priceFieldForPriceExport' => ['price', null, 'export'],
            'priceFieldForPurchasePriceExport' => ['detailPurchasePrice', null, 'export'],
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

    public function testUpdatePrices()
    {
        $articleId = $this->insertVariants();
        $modelManager = $this->manager;
        $this->config->setConfig('autoUpdateProducts', Config::UPDATE_AUTO);
        $article = $modelManager->getRepository('Shopware\Models\Article\Article')->find($articleId);

        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($article);
        $connectAttribute->setExportStatus(null);
        $connectAttribute->setExported(false);
        $connectAttribute->setShopId(null);
        $this->manager->persist($connectAttribute);
        $this->manager->flush();

        $this->connectExport->export([$connectAttribute->getSourceId()]);

        $detail = $article->getMainDetail();

        $price = $detail->getPrices()->first();

        $this->Request()
            ->setMethod('POST')
            ->setPost('prices',
                [
                    0 => [
                            'id' => $price->getId(),
                            'from' => 1,
                            'to' => 5,
                            'price' => 238.00,
                            'pseudoPrice' => 0,
                            'percent' => 0,
                            'cloned' => false,
                            'customerGroupKey' => 'EK',
                            'customerGroup' => [
                                    0 => [
                                            'id' => 1,
                                            'key' => 'EK',
                                            'name' => 'Shopkunden',
                                            'tax' => true,
                                            'taxInput' => true,
                                            'mode' => false,
                                            'discount' => 0,
                                        ],
                                ],
                        ],
                ])
            ->setPost('controller', 'Article')
            ->setPost('module', 'backend')
            ->setPost('action', 'saveDetail')
            ->setPost('number', 'llc1408017')
            ->setPost('price', 238.00)
            ->setPost('additionalText', 'L / Schwarz')
            ->setPost('supplierNumber', '')
            ->setPost('active', false)
            ->setPost('inStock', 15)
            ->setPost('stockMin', 0)
            ->setPost('weight', 0)
            ->setPost('kind', 1)
            ->setPost('position', 0)
            ->setPost('shippingFree', false)
            ->setPost('minPurchase', 1)
            ->setPost('purchasePrice', 38.99)
            ->setPost('articleId', $articleId)
            ->setPost('standard', false)
            ->setPost('id', $detail->getId());


        $this->dispatch('backend/Article/saveDetail');
        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);

        $changes = $this->db->query('SELECT * FROM sw_connect_change WHERE c_entity_id = ? ORDER BY c_revision', ['llc1408017'])->fetchAll();
        $this->assertCount(2, $changes);
        $updateChange = $changes[1];
        $this->assertEquals('update', $updateChange['c_operation']);
        $product = unserialize($updateChange['c_payload']);
        $this->assertEquals(200.00, $product->price);
    }

    private function insertVariants()
    {
        //clear connect_change table
        $this->db->exec('DELETE FROM sw_connect_change WHERE c_entity_id LIKE "llc1408017%"');
        //clear s_articles table
        $this->db->exec('DELETE FROM s_articles WHERE name = "LifecycleArticle"');
        //clear s_articles_detail table
        $this->db->exec('DELETE FROM s_articles_details WHERE ordernumber LIKE "llc1408017%"');
        //clear connect_items table
        $this->db->exec('DELETE FROM s_plugin_connect_items WHERE source_id LIKE "llc1408017%"');

        $minimalTestArticle = [
            'name' => 'LifecycleArticle',
            'active' => true,
            'tax' => 19,
            'supplier' => 'LifecycleArticle Inc.',
            'categories' => [
                ['id' => 15],
            ],
            'mainDetail' => [
                'number' => 'llc1408017',
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
                    'number' => 'llc1408017',
                    'inStock' => 15,
                    'standard' => null,
                    'additionaltext' => 'L / Schwarz',
                    'purchasePrice' => 38.99,
                    'configuratorOptions' => [
                        ['group' => 'Größe', 'groupId' => null, 'optionId' => null, 'option' => 'L'],
                        ['group' => 'Farbe', 'groupId' => null, 'optionId' => null, 'option' => 'Schwarz'],
                    ],
                ],
                [
                    'isMain' => false,
                    'number' => 'llc1408017-1',
                    'inStock' => 15,
                    'standard' => null,
                    'additionnaltext' => 'S / Schwarz',
                    'purchasePrice' => 38.99,
                    'configuratorOptions' => [
                        ['group' => 'Größe', 'groupId' => null, 'optionId' => null,'option' => 'S'],
                        ['group' => 'Farbe', 'groupId' => null, 'optionId' => null, 'option' => 'Schwarz'],
                    ],
                ],
                [
                    'isMain' => false,
                    'number' => 'llc1408017-2',
                    'inStock' => 15,
                    'standard' => null,
                    'additionnaltext' => 'S / Rot',
                    'purchasePrice' => 38.99,
                    'configuratorOptions' => [
                        ['group' => 'Größe', 'groupId' => null, 'optionId' => null,'option' => 'S'],
                        ['group' => 'Farbe', 'groupId' => null, 'optionId' => null,'option' => 'Rot'],
                    ],
                ],
                [
                    'isMain' => false,
                    'number' => 'llc1408017-3',
                    'inStock' => 15,
                    'standard' => null,
                    'additionnaltext' => 'XL / Rot',
                    'purchasePrice' => 38.99,
                    'configuratorOptions' => [
                        ['group' => 'Größe', 'groupId' => null, 'optionId' => null, 'option' => 'XL'],
                        ['group' => 'Farbe', 'groupId' => null, 'optionId' => null,'option' => 'Rot'],
                    ],
                ]
            ]
        ];

        /** @var \Shopware\Models\Article\Article $article */
        $article = $articleResource->update($article->getId(), $updateArticle);

        $this->db->insert(
            's_articles_prices',
            [
                'pricegroup' => 'EK',
                'from' => 1,
                'to' => 5,
                'price' => 123.99,
                'articleID' => $article->getId(),
                'articledetailsID' => $article->getMainDetail()->getId(),
                'pseudoprice' => 0
            ]
        );

        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            $this->db->executeQuery(
                'INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id, category, exported)
                  VALUES (?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  `category` = VALUES(`category`),
                  `exported` = VALUES(`exported`)',
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
}
