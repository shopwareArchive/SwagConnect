<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Subscribers\Less;

class LessTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_can_be_created()
    {
        $subscriber = new Less();
        $this->assertInstanceOf(Less::class, $subscriber);
        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
    }

    public function test_it_adds_less_to_array_collection()
    {
        $subscriber = new Less();

        $result = $subscriber->addLessFiles(new \Enlight_Event_EventArgs());

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertEquals([], $result[0]->getConfig());
        $this->assertStringEndsWith('/Views/responsive/frontend/_public/src/less/all.less', $result[0]->getFiles()[0]);
    }
}
