<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Functional\Subscribers;

use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Shopware\Components\Model\ModelManager;

class LifecycleTest extends \Enlight_Components_Test_Plugin_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @before
     */
    public function prepare()
    {
        $this->manager = Shopware()->Models();
        // disable auth and acl
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function testUpdatePrices()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/simple_variants.sql');

        $this->Request()
            ->setMethod('POST')
//            ->setPost('prices',
//                [
//                    0 => [
//                        'id' => $price->getId(),
//                        'from' => 1,
//                        'to' => 5,
//                        'price' => 238.00,
//                        'pseudoPrice' => 0,
//                        'percent' => 0,
//                        'cloned' => false,
//                        'customerGroupKey' => 'EK',
//                        'customerGroup' => [
//                            0 => [
//                                'id' => 1,
//                                'key' => 'EK',
//                                'name' => 'Shopkunden',
//                                'tax' => true,
//                                'taxInput' => true,
//                                'mode' => false,
//                                'discount' => 0,
//                            ],
//                        ],
//                    ],
//                ])
            ->setPost('controller', 'Article')
            ->setPost('module', 'backend')
            ->setPost('action', 'saveDetail')
            ->setPost('number', 'sw32870.3')
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
            ->setPost('articleId', 32870)
            ->setPost('standard', false)
            ->setPost('id', 2404537);


        $this->dispatch('backend/Article/saveDetail');
        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);

        $changes = $this->manager->getConnection()->query('SELECT * FROM sw_connect_change WHERE c_entity_id = ? ORDER BY c_revision', ['32870-2404537'])->fetchAll();
        $this->assertCount(1, $changes);
        $updateChange = $changes[0];
        $this->assertEquals('update', $updateChange['c_operation']);
        $product = unserialize($updateChange['c_payload']);
        $this->assertEquals(200.00, $product->price);
    }

    public function test_generate_delete_change_for_variants()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/simple_variants.sql');

        $this->Request()
            ->setMethod('POST')
            ->setPost('articleId', 32870)
            ->setPost('id', 2404537);

        $this->dispatch('backend/Article/deleteDetail');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);

        $changes = $this->manager->getConnection()->fetchAll(
            'SELECT c_entity_id, c_operation, c_revision, c_payload FROM sw_connect_change WHERE c_entity_id = ?',
            ['32870-2404537']
        );

        $this->assertCount(1, $changes);
        $this->assertEquals('delete', $changes[0]['c_operation']);
    }

    public function test_generate_delete_change_only_for_exported()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/simple_variants.sql');

        $detailExist = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles_details WHERE id = ? AND articleID = ?',
            [2404544, 32871]
        );
        $this->assertEquals(1, $detailExist, 'Article detail does not exist!');

        $this->Request()
            ->setMethod('POST')
            ->setPost('articleId', 32871)
            ->setPost('id', 2404544);

        $this->dispatch('backend/Article/deleteDetail');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);

        $changes = $this->manager->getConnection()->fetchAll(
            'SELECT c_entity_id, c_operation, c_revision, c_payload FROM sw_connect_change WHERE c_entity_id = ?',
            ['32871-2404544']
        );

        $this->assertEmpty($changes);
    }
}
