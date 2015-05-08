<?php

namespace Tests\Shopware\Bepado;

use Shopware\Bepado\Components\BepadoExport;
use Shopware\Bepado\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;

class BepadoExportTest extends BepadoTestHelper
{
    /**
     * @var \Shopware\Bepado\Components\BepadoExport
     */
    private $bepadoExport;

    public function setUp()
    {
        $this->bepadoExport = new BepadoExport(
            $this->getHelper(),
            $this->getSDK(),
            Shopware()->Models(),
            new ProductsAttributesValidator()
        );
    }

    public function testExport()
    {
        $model = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find(2);
        $bepadoAttribute = $this->getHelper()->getOrCreateBepadoAttributeByModel($model);
        $bepadoAttribute->setExportStatus('insert');
        Shopware()->Models()->persist($bepadoAttribute);
        Shopware()->Models()->flush($bepadoAttribute);

        $errors = $this->bepadoExport->export(array(2));

        $this->assertEmpty($errors);

        $sql = 'SELECT export_status, export_message FROM s_plugin_bepado_items WHERE source_id = ?';
        $row = Shopware()->Db()->fetchRow($sql, array(2));

        $this->assertEquals('update', $row['export_status']);
        $this->assertNull($row['export_message']);
    }

    public function testExportErrors()
    {
        $model = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find(4);
        $bepadoAttribute = $this->getHelper()->getOrCreateBepadoAttributeByModel($model);
        $errors = $this->bepadoExport->export(array(4));

        $this->assertNotEmpty($errors);

        $sql = 'SELECT export_status, export_message FROM s_plugin_bepado_items WHERE article_id = ?';
        $row = Shopware()->Db()->fetchRow($sql, array(4));

        $this->assertEquals('error', $row['export_status']);
        $this->assertContains('The purchasePrice is not allowed to be 0 or smaller.', $row['export_message']);
    }

    public function testSyncDeleteArticle()
    {
        $articleId = $this->insertVariants();
        $modelManager = Shopware()->Models();
        $article = $modelManager->getRepository('Shopware\Models\Article\Article')->find($articleId);

        $this->bepadoExport->syncDeleteArticle($article);
        $result = Shopware()->Db()->executeQuery(
            'SELECT export_status
              FROM s_plugin_bepado_items
              WHERE article_id = ?',
            array($articleId)
        )->fetchAll();

        foreach ($result as $bepadoAttribute) {
            $this->assertEquals('delete', $bepadoAttribute['export_status']);
        }

        $this->assertEquals(4, Shopware()->Db()->query('SELECT COUNT(*) FROM bepado_change WHERE c_source_id LIKE "1919%"')->fetchColumn());
    }

    public function testDeleteVariant()
    {
        $articleId = $this->insertVariants();
        $modelManager = Shopware()->Models();
        /** @var \Shopware\Models\Article\Article $article */
        $article = $modelManager->getRepository('Shopware\Models\Article\Article')->find($articleId);
        $detail = $article->getMainDetail();

        $this->bepadoExport->syncDeleteDetail($detail);

        $this->assertEquals(1, Shopware()->Db()->query('SELECT COUNT(*) FROM bepado_change WHERE c_source_id LIKE "1919%"')->fetchColumn());
        $this->assertEquals(1, Shopware()->Db()->query('SELECT COUNT(*) FROM s_plugin_bepado_items WHERE source_id = "1919" AND export_status = "delete"')->fetchColumn());
    }

    private function insertVariants()
    {
        //clear bepado_change table
        Shopware()->Db()->exec('DELETE FROM bepado_change WHERE c_source_id LIKE "1919%"');
        //clear s_articles table
        Shopware()->Db()->exec('DELETE FROM s_articles WHERE name = "Turnschuh"');
        //clear s_articles_detail table
        Shopware()->Db()->exec('DELETE FROM s_articles_details WHERE ordernumber LIKE "1919%"');
        //clear bepado_items table
        Shopware()->Db()->exec('DELETE FROM s_plugin_bepado_items WHERE source_id LIKE "1919%"');


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
                'prices' => array(
                    array(
                        'customerGroupKey' => 'EK',
                        'price' => 999,
                    ),
                )
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
                    'additionaltext' => 'L / Schwarz',
                    'configuratorOptions' => array(
                        array('group' => 'Größe', 'option' => 'L'),
                        array('group' => 'Farbe', 'option' => 'Schwarz'),
                    ),
                    'prices' => array(
                        array(
                            'customerGroupKey' => 'EK',
                            'price' => 1999,
                        ),
                    )
                ),
                array(
                    'isMain' => false,
                    'number' => '1919-1',
                    'inStock' => 15,
                    'additionnaltext' => 'S / Schwarz',
                    'configuratorOptions' => array(
                        array('group' => 'Größe', 'option' => 'S'),
                        array('group' => 'Farbe', 'option' => 'Schwarz'),
                    ),
                    'prices' => array(
                        array(
                            'customerGroupKey' => 'EK',
                            'price' => 999,
                        ),
                    )
                ),
                array(
                    'isMain' => false,
                    'number' => '1919-2',
                    'inStock' => 15,
                    'additionnaltext' => 'S / Rot',
                    'configuratorOptions' => array(
                        array('group' => 'Größe', 'option' => 'S'),
                        array('group' => 'Farbe', 'option' => 'Rot'),
                    ),
                    'prices' => array(
                        array(
                            'customerGroupKey' => 'EK',
                            'price' => 999,
                        ),
                    )
                ),
                array(
                    'isMain' => false,
                    'number' => '1919-3',
                    'inStock' => 15,
                    'additionnaltext' => 'XL / Rot',
                    'configuratorOptions' => array(
                        array('group' => 'Größe', 'option' => 'XL'),
                        array('group' => 'Farbe', 'option' => 'Rot'),
                    ),
                    'prices' => array(
                        array(
                            'customerGroupKey' => 'EK',
                            'price' => 999,
                        ),
                    )
                )
            )
        );

        /** @var \Shopware\Models\Article\Article $article */
        $article = $articleResource->update($article->getId(), $updateArticle);

        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            Shopware()->Db()->executeQuery(
                'INSERT INTO s_plugin_bepado_items (article_id, article_detail_id, source_id, category)
                  VALUES (?, ?, ?, ?)',
                array(
                    $article->getId(),
                    $detail->getId(),
                    $detail->getNumber(),
                    '/bücher'
                ));
        }

        return $article->getId();
    }
}
 
