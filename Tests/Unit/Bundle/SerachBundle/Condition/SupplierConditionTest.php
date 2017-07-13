<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Bundle\SearchBundle\Condition;

use Assert\InvalidArgumentException;
use Shopware\Bundle\SearchBundle\ConditionInterface;
use ShopwarePlugins\Connect\Bundle\SearchBundle\Condition\SupplierCondition;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;

class SupplierConditionTest extends AbstractConnectUnitTest
{
    public function test_it_can_be_created()
    {
        $condition = new SupplierCondition([]);

        $this->assertInstanceOf(SupplierCondition::class, $condition);
        $this->assertInstanceOf(ConditionInterface::class, $condition);
    }

    public function test_it_should_only_accept_integer()
    {
        $condition = new SupplierCondition([ "1" ]);

        $this->assertInstanceOf(SupplierCondition::class, $condition);
        $this->assertInstanceOf(ConditionInterface::class, $condition);
    }

    public function test_it_should_throw_exception_if_non_intergish_values_are_given()
    {
        $this->expectException(InvalidArgumentException::class);
        new SupplierCondition([ "asdfasdf" ]);
    }
}
