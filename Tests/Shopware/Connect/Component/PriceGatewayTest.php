<?php

namespace Tests\ShopwarePlugins\Connect\Component;

use ShopwarePlugins\Connect\Components\PriceGateway;

class PriceGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ShopwarePlugins\Connect\Components\PriceGateway
     */
    private $priceGateway;

    private $customerGroupRepository;

    public function setUp()
    {
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
