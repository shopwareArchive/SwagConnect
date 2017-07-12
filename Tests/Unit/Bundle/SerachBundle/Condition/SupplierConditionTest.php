<?php

namespace ShopwarePlugins\Tests\Unit\Bundle\SearchBundle\Condition;

use Shopware\Bundle\SearchBundle\ConditionInterface;

class SupplierConditionTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_can_be_created()
    {
        $condition = new SupplierConditionTest([]);

        $this->assertInstanceOf(SupplierConditionTest::class, $condition);
        $this->assertInstanceOf(ConditionInterface::class, $condition);
    }
}