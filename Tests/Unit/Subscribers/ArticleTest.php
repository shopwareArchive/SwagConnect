<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Subscribers\Article;
use Shopware\Connect\Gateway;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\ConnectExport;
use Shopware\Models\Article\Article as ArticleModel;
use Doctrine\ORM\EntityRepository;
use ShopwarePlugins\Connect\Components\Helper;
use Shopware\CustomModels\Connect\Attribute as ConnectAttribute;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;

class ArticleTest extends AbstractConnectUnitTest
{
    /**
     * @var Article
     */
    private $subscriber;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var ConnectExport
     */
    private $connectExport;

    /**
     * @var Config
     */
    private $config;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->modelManager = $this->createMock(ModelManager::class);
        $this->helper = $this->createMock(Helper::class);
        $this->connectExport = $this->createMock(ConnectExport::class);
        $this->config = $this->createMock(Config::class);

        $this->subscriber = new Article(
            $this->createMock(Gateway::class),
            $this->modelManager,
            $this->connectExport,
            $this->helper,
            $this->config,
            (new ConnectFactory())->getSDK(),
            $this->createMock(\Shopware_Components_Snippet_Manager::class),
            ''
        );
    }

    public function testRegenerateChanges()
    {
        $articleId = 5;
        $article = new ArticleModel();
        $connectAttribute = new ConnectAttribute();

        $repositoryMock = $this->createMock(EntityRepository::class);

        $this->modelManager->method('getRepository')
            ->with(ArticleModel::class)
            ->willReturn($repositoryMock);

        $repositoryMock->method('find')
            ->with($articleId)
            ->willReturn($article);

        $this->helper->method('getConnectAttributeByModel')
            ->with($article)
            ->willReturn($connectAttribute);

        $this->helper->method('isProductExported')
            ->with($connectAttribute)
            ->willReturn(true);

        $this->config->method('getConfig')
            ->with('autoUpdateProducts', Config::UPDATE_AUTO)
            ->willReturn(Config::UPDATE_AUTO);

        $sourceIds = ['5', '5-101', '5-102', '5-103'];
        $this->helper->method('getArticleSourceIds')
            ->with([$articleId])
            ->willReturn($sourceIds);

        $this->connectExport->method('export')
            ->with($sourceIds);

        $this->subscriber->regenerateChangesForArticle($articleId);
    }

    public function testRegenerateSkipsNotExportedProduct()
    {
        $articleId = 5;
        $article = new ArticleModel();
        $connectAttribute = new ConnectAttribute();

        $repositoryMock = $this->createMock(EntityRepository::class);

        $this->modelManager->method('getRepository')
            ->with(ArticleModel::class)
            ->willReturn($repositoryMock);

        $repositoryMock->method('find')
            ->with($articleId)
            ->willReturn($article);

        $this->helper->method('getConnectAttributeByModel')
            ->with($article)
            ->willReturn($connectAttribute);

        $this->helper->method('isProductExported')
            ->with($connectAttribute)
            ->willReturn(false);

        $this->connectExport->expects($this->never())
            ->method('export');

        $this->subscriber->regenerateChangesForArticle($articleId);
    }

    public function testRegenerateSkipsMissingArticles()
    {
        $repositoryMock = $this->createMock(EntityRepository::class);

        $this->modelManager->method('getRepository')
            ->with(ArticleModel::class)
            ->willReturn($repositoryMock);

        $this->connectExport->expects($this->never())
            ->method('export');

        $this->subscriber->regenerateChangesForArticle(5);
    }

    public function testRegenerateSkipsWhenConnectAttributeIsMissing()
    {
        $articleId = 5;
        $article = new ArticleModel();

        $repositoryMock = $this->createMock(EntityRepository::class);

        $this->modelManager->method('getRepository')
            ->with(ArticleModel::class)
            ->willReturn($repositoryMock);

        $repositoryMock->method('find')
            ->with($articleId)
            ->willReturn($article);

        $this->connectExport->expects($this->never())
            ->method('export');

        $this->subscriber->regenerateChangesForArticle($articleId);
    }

    public function testRegenerateWhenUpdateIsManual()
    {
        $this->config->method('getConfig')
            ->with('autoUpdateProducts', Config::UPDATE_AUTO)
            ->willReturn(Config::UPDATE_MANUAL);

        $this->modelManager->expects($this->never())
            ->method('getRepository');
        $this->connectExport->expects($this->never())
            ->method('export');

        $this->subscriber->regenerateChangesForArticle(5);
    }
}
