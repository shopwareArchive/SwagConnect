<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Subscribers\Javascript;

class JavascriptTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_can_be_created()
    {
        $subscriber = new Javascript();
        $this->assertInstanceOf(Javascript::class, $subscriber);
        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
    }

    public function test_it_should_add_javascript_files()
    {
        $subscriber = new Javascript();

        $result = $subscriber->addJsFiles(new \Enlight_Event_EventArgs());
        $this->assertCount(1, $result);
    }
}
