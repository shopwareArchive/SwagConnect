<?php

namespace Tests\ShopwarePlugins\Connect\Component;

use ShopwarePlugins\Connect\Components\PriceGateway;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class PriceGatewayTest extends ConnectTestHelper
{
    /**
     * @var \ShopwarePlugins\Connect\Components\PriceGateway
     */
    private $priceGateway;

    private $customerGroupRepository;

    public function setUp()
    {
        parent::setUp();

        $this->priceGateway = new PriceGateway(
            Shopware()->Db()
        );

        $this->customerGroupRepository = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group');
    }

    /**
     * @test
     */
    public function all_products_should_have_configured_price()
    {
        // insert missing prices
        $query = $query = Shopware()->Db()->query("
                SELECT sad.articleID as articleId, sad.id as detailId
                FROM s_articles_details sad
                LEFT JOIN s_articles_prices sap ON sad.id = sap.articledetailsID AND sap.pricegroup = ?
                LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
                WHERE spci.shop_id IS NULL AND sap.price IS NULL OR sap.price = 0
            ", ['EK']);

        Shopware()->Db()->beginTransaction();

        foreach ($query->fetchAll() as $record) {
            Shopware()->Db()->executeQuery(
                'INSERT INTO `s_articles_prices`(
              `pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `price`
              ) VALUES ("EK", 1, "beliebig", ?, ?, 50)', [$record['articleId'], $record['detailId']]
            );
        }

        Shopware()->Db()->commit();

        $customerGroup = $this->customerGroupRepository->findOneBy(array('key' => 'EK'));
        $this->assertEquals(0, $this->priceGateway->countProductsWithoutConfiguredPrice($customerGroup, 'price'));
    }

    /**
     * @test
     */
    public function all_products_should_not_have_configured_base_price()
    {
        $customerGroup = $this->customerGroupRepository->findOneBy(array('key' => 'EK'));
        $this->assertGreaterThan(0, $this->priceGateway->countProductsWithoutConfiguredPrice($customerGroup, 'basePrice'));
    }
}
