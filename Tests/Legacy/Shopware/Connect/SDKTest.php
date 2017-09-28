<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\SDK;
use Shopware\Connect\Struct\Product;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class SDKTest extends ConnectTestHelper
{

    use DatabaseTestCaseTrait;
    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;

    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    public function setUp()
    {
        parent::setUp();

        $this->manager = Shopware()->Models();
        $this->db = Shopware()->Db();
        $this->db->delete('sw_connect_shop_config', ['s_shop = ?' => '_price_type']);
        $this->db->insert('sw_connect_shop_config', ['s_shop' => '_price_type', 's_config' => SDK::PRICE_TYPE_BOTH]);

        $this->db->executeQuery(
            'DELETE FROM `s_plugin_connect_config` WHERE `name` = "priceFieldForPurchasePriceExport"'
        );

        $this->db->executeQuery(
            'INSERT INTO `s_plugin_connect_config`(`name`, `value`, `groupName`)
            VALUES ("priceFieldForPurchasePriceExport", "detailPurchasePrice", "export")'
        );

        parent::setUp();
    }

    public function testExportProductWithoutPurchasePrice()
    {
        $article = $this->getLocalArticle();
        $prices = $article->getMainDetail()->getPrices();
        if (method_exists('Shopware\Models\Article\Detail', 'setPurchasePrice')) {
            $article->getMainDetail()->setPurchasePrice(null);
            $this->manager->persist($article->getMainDetail());
        } else {
            $prices[0]->setBasePrice(null);
            $this->manager->Models()->persist($prices[0]);
        }

        $this->manager->flush();

        $this->getConnectExport()->export([$article->getId()]);


        /** @var \Shopware\CustomModels\Connect\Attribute $model */
        $model = $this->manager->getRepository('Shopware\CustomModels\Connect\Attribute')->findOneBy(['sourceId' => $article->getId()]);
        $message = $model->getExportMessage();

        $this->assertContains('Ein Preisfeld für dieses Produkt ist nicht gepfegt', $message);
    }

    public function testHandleProductUpdates()
    {
        // pseudo verify SDK
        $this->db->delete('sw_connect_shop_config', []);
        $this->db->insert('sw_connect_shop_config', ['s_shop' => '_self_', 's_config' => -1]);
        $this->db->insert('sw_connect_shop_config', ['s_shop' => '_last_update_', 's_config' => time()]);
        $this->db->insert('sw_connect_shop_config', ['s_shop' => '_categories_', 's_config' => serialize(['/bücher' => 'Bücher'])]);

        $offerValidUntil = time() + 1 * 365 * 24 * 60 * 60; // One year
        $purchasePrice = 6.99;
        $this->dispatchRpcCall('products', 'toShop', [
            [
                new \Shopware\Connect\Struct\Change\ToShop\InsertOrUpdate([
                    'product' => new \Shopware\Connect\Struct\Product([
                        'shopId' => 3,
                        'revisionId' => time(),
                        'sourceId' => 'ABCDEFGH' . time(),
                        'ean' => '1234',
                        'url' => 'http://shopware.de',
                        'title' => 'shopware Connect Test-Produkt',
                        'shortDescription' => 'Ein Produkt aus shopware Connect',
                        'longDescription' => 'Ein Produkt aus shopware Connect',
                        'additionalDescription' => 'Ein Produkt aus shopware Connect',
                        'vendor' => 'shopware Connect',
                        'stream' => 'Awesome products',
                        'price' => 9.99,
                        'purchasePrice' => $purchasePrice,
                        'purchasePriceHash' => hash_hmac(
                            'sha256',
                            sprintf('%.3F %d', $purchasePrice, $offerValidUntil), '54642546-0001-48ee-b4d0-4f54af66d822'
                        ),
                        'offerValidUntil' => $offerValidUntil,
                        'availability' => 100,
                        'images' => [self::IMAGE_PROVIDER_URL],
                        'categories' => ['/bücher' => 'Bücher'],
                    ]),
                    'revision' => time(),
                ])
            ]
        ]);
    }

    public function testExportProductWithPurchasePrice()
    {

        Shopware()->Db()->executeQuery(
            "INSERT INTO s_plugin_connect_items (article_id, article_detail_id, shop_id, source_id, export_status, export_message, exported, category, purchase_price, fixed_price, free_delivery, update_price, update_image, update_long_description, update_short_description, update_additional_description, update_name, last_update, last_update_flag, group_id, is_main_variant, purchase_price_hash, offer_valid_until, stream, cron_update, revision)
                    VALUES (3, 3, null, '3', null, null, 0, null, null, null, null, 'inherit', 'inherit', 'inherit', 'inherit', 'inherit', 'inherit', null, null, null, null, '', 0, '', null, null);"
        );

        $model = $this->manager->getRepository('Shopware\CustomModels\Connect\Attribute')->findOneBy(['articleId' => 3]);
        $model->setExportMessage(null);
        $this->manager->persist($model);
        $this->manager->flush();

        $article = $this->getLocalArticle();
        $detail = $article->getMainDetail();

        if (method_exists($detail, 'setPurchasePrice')) {
            $detail->setPurchasePrice(5.99);
            $this->manager->persist($detail);
        } else {
            $prices = $detail->getPrices();
            $prices[0]->setBasePrice(5.99);
            $this->manager->persist($prices[0]);
        }
        $this->manager->flush();

        // Insert the product
        $this->getConnectExport()->export([$article->getId()]);

        /** @var \Shopware\CustomModels\Connect\Attribute $model */
        $model = $this->manager->getRepository('Shopware\CustomModels\Connect\Attribute')->findOneBy(['articleId' => 3]);
        $message = $model->getExportMessage();

        $this->assertNull($message);
    }
}
