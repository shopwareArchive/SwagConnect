<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Struct\Verificator\Product;
use ShopwarePlugins\Connect\Components\ProductQuery\RemoteProductQuery;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use ShopwarePlugins\Connect\Tests\ProductBuilderTrait;
use ShopwarePlugins\Connect\Tests\RpcDispatcherTrait;

class RemoteProductQueryTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use RpcDispatcherTrait;
    use ProductBuilderTrait;

    /**
     * @var RemoteProductQuery
     */
    private $remoteProductQuery;

    private $db;

    private $manager;

    public function setUp()
    {
        $this->db = Shopware()->Db();
        $this->manager = Shopware()->Models();

        $this->remoteProductQuery = new RemoteProductQuery($this->manager);
    }

    public function test_get_remote_should_return_empty_array()
    {
        $result = $this->remoteProductQuery->get([2], 2);
        $this->assertEmpty($result);
    }

    public function test_get_remote()
    {
        $newProduct = $this->getProductNonRand();
        $newProduct->minPurchaseQuantity = 4;

        $this->dispatchRpcCall('products', 'toShop', [
            [
                new \Shopware\Connect\Struct\Change\ToShop\InsertOrUpdate([
                    'product' => $newProduct,
                    'revision' => time(),
                ])
            ]
        ]);

        $result = $this->remoteProductQuery->get([$newProduct->sourceId], $newProduct->shopId);
        /** @var \Shopware\Connect\Struct\Product $product */
        $product = $result[0];

        $this->assertInstanceOf('\Shopware\Connect\Struct\Product', $product);
        $this->assertEquals($newProduct->title, $product->title);
        $this->assertEquals($newProduct->price, $product->price);
        $this->assertEquals($newProduct->purchasePrice, $product->purchasePrice);
        $this->assertEquals($newProduct->purchasePriceHash, $product->purchasePriceHash);
        $this->assertEquals($newProduct->offerValidUntil, $product->offerValidUntil);
        $this->assertEquals($newProduct->minPurchaseQuantity, $product->minPurchaseQuantity);
    }
}
