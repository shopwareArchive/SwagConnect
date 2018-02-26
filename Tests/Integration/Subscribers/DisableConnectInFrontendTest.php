<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Subscribers;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Subscribers\DisableConnectInFrontend;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class DisableConnectInFrontendTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    const SPACHTELMASSE_ARTICLE_ID = 272;

    public function test_it_can_be_created()
    {
        $subscriber = new DisableConnectInFrontend($this->createMock(\Enlight_Components_Db_Adapter_Pdo_Mysql::class));

        $this->assertInstanceOf(DisableConnectInFrontend::class, $subscriber);
        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
    }

    public function test_it_should_not_disable_button_if_product_is_not_a_connect_product()
    {
        $subscriber = new DisableConnectInFrontend(Shopware()->Container()->get('db'));

        $args = new \Enlight_Event_EventArgs();

        $controllerMock = $this->createMock(\Enlight_Controller_Action::class);
        $view = new \Enlight_View_Default(new \Enlight_Template_Manager());
        $view->assign('sArticle', [ 'articleID' => self::SPACHTELMASSE_ARTICLE_ID ]);

        $controllerMock->method('View')->willReturn($view);

        $args->set('subject', $controllerMock);

        $subscriber->disableBuyButtonForConnect($args);

        $this->assertNull($view->getAssign('hideConnect'));
        $this->assertEquals(self::SPACHTELMASSE_ARTICLE_ID, $view->getAssign('sArticle')['articleID']);
    }

    public function test_it_should_disable_buy_button_if_api_key_is_invalid()
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->executeQuery(
            'INSERT INTO s_plugin_connect_items (article_id, purchase_price_hash, offer_valid_until, stream, shop_id) VALUES (?, "hash", 123, 1, 1)',
            [ self::SPACHTELMASSE_ARTICLE_ID]
        );

        $subscriber = new DisableConnectInFrontend(Shopware()->Container()->get('db'));

        $args = new \Enlight_Event_EventArgs();
        $controllerMock = $this->createMock(\Enlight_Controller_Action::class);

        $view = new \Enlight_View_Default(new \Enlight_Template_Manager());
        $view->assign('sArticle', [ 'articleID' => self::SPACHTELMASSE_ARTICLE_ID ]);

        $controllerMock->method('View')->willReturn($view);
        $args->set('subject', $controllerMock);

        $subscriber->disableBuyButtonForConnect($args);

        $this->assertTrue($view->getAssign('hideConnect'));
        $this->assertEquals(self::SPACHTELMASSE_ARTICLE_ID, $view->getAssign('sArticle')['articleID']);
    }
}
