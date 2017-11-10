<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\Models\Category\Category;
use ShopwarePlugins\Connect\Components\CategoryExtractor;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\ImportService;
use ShopwarePlugins\Connect\Components\RandomStringGenerator;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;
use Shopware\Connect\Gateway\PDO;
use Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository;

class ImportServiceTest extends ConnectTestHelper
{
    use DatabaseTestCaseTrait;
    /**
     * @var \ShopwarePlugins\Connect\Components\ImportService
     */
    private $importService;

    private $connectAttributeRepository;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;

    private $remoteCategoryRepository;

    private $categoryRepository;

    private $articleRepository;

    /**
     * @var ProductToRemoteCategoryRepository
     */
    private $productToRemoteCategoriesRepository;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $conn = Shopware()->Db();
        $conn->delete('sw_connect_shop_config', ['s_shop = ?' => '_price_type']);
        $conn->insert('sw_connect_shop_config', ['s_shop' => '_price_type', 's_config' => 3]);
    }

    public function setUp()
    {
        parent::setUp();

        $this->manager = Shopware()->Models();

        $this->categoryRepository = $this->manager->getRepository('Shopware\Models\Category\Category');
        $this->remoteCategoryRepository = $this->manager->getRepository('Shopware\CustomModels\Connect\RemoteCategory');
        $this->connectAttributeRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute');
        $this->productToRemoteCategoriesRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\ProductToRemoteCategory');
        $this->articleRepository = $this->manager->getRepository('Shopware\Models\Article\Article');
        $autoCategoryResolver = new AutoCategoryResolver(
            $this->manager,
            $this->categoryRepository,
            $this->remoteCategoryRepository,
            ConfigFactory::getConfigInstance(),
            $this->manager->getRepository(ProductToRemoteCategory::class)
        );

        $this->importService = new ImportService(
            $this->manager,
            Shopware()->Container()->get('multi_edit.product'),
            $this->categoryRepository,
            $this->articleRepository,
            $this->remoteCategoryRepository,
            $this->manager->getRepository('Shopware\CustomModels\Connect\ProductToRemoteCategory'),
            $autoCategoryResolver,
            new CategoryExtractor(
                $this->connectAttributeRepository,
                $autoCategoryResolver,
                new PDO(Shopware()->Db()->getConnection()),
                new RandomStringGenerator(),
                Shopware()->Db()
            ),
            Shopware()->Container()->get('CategoryDenormalization'),
            Shopware()->Container()->get('shopware_attribute.data_persister')
        );
    }

    public function testUnAssignAllArticleCategories()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_plugin_connect_categories`');
        $sourceIds = $this->insertOrUpdateProducts(3, false, false);
        // find articles by sourceId
        $connectAttributes = $this->connectAttributeRepository->findBy(['sourceId' => $sourceIds]);
        // map buecher category to some local category
        $localCategory = $this->categoryRepository->find(6);
        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => '/deutsch/bücher']);
        $remoteCategory->addLocalCategory($localCategory);
        $this->manager->persist($remoteCategory);
        $this->manager->flush();
        // assign local category to products
        $articleIds = [];
        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        foreach ($connectAttributes as $connectAttribute) {
            $article = $connectAttribute->getArticle();
            $article->addCategory($localCategory);
            $attribute = $article->getAttribute();
            $attribute->setConnectMappedCategory(true);
            $this->manager->persist($attribute);
            $this->manager->persist($article);
            $articleIds[] = $article->getId();
        }
        $this->manager->flush();
        // call unAssignArticleCategories
        $this->importService->unAssignArticleCategories($articleIds);
        $this->assertEquals(
            0,
            $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM s_articles_categories WHERE articleID IN (' . implode(', ', $articleIds) . ')')->fetchColumn()
        );
        $this->assertEquals(
            0,
            $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM s_articles_categories_ro WHERE articleID IN (' . implode(', ', $articleIds) . ')')->fetchColumn()
        );
        $this->manager->clear();
        /** @var \Shopware\Models\Article\Article $article */
        foreach ($this->articleRepository->findBy(['id' => $articleIds]) as $article) {
            // check connect_mapped_category flag, must be null
            $this->assertNull($article->getAttribute()->getConnectMappedCategory());
            // check article->getCategories for each article, it should be an empty array
            $this->assertEmpty($article->getCategories());
        }
    }

    public function testUnAssignArticleCategories()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_plugin_connect_categories`');

        $sourceIds = $this->insertOrUpdateProducts(3, false, false);

        // find articles by sourceId
        $connectAttributes = $this->connectAttributeRepository->findBy(['sourceId' => $sourceIds]);

        // map buecher category to some local category
        $localCategory = $this->categoryRepository->find(6);
        $localCategory2 = $this->categoryRepository->find(8);
        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => '/deutsch/bücher']);
        $remoteCategory->addLocalCategory($localCategory);
        $this->manager->persist($remoteCategory);
        $this->manager->flush();

        // assign local category to products
        $articleIds = [];
        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        foreach ($connectAttributes as $connectAttribute) {
            $article = $connectAttribute->getArticle();
            $article->addCategory($localCategory);
            $article->addCategory($localCategory2);

            $attribute = $article->getAttribute();
            $attribute->setConnectMappedCategory(true);

            $this->manager->persist($attribute);
            $this->manager->persist($article);

            $articleIds[] = $article->getId();
        }

        $this->manager->flush();

        //unAssign first local category from all articles
        $this->importService->unAssignArticleCategories($articleIds, $localCategory->getId());
        $articleWithCategory = $articleIds[2];
        unset($articleIds[2]);
        //unAssign second local category just from two articles
        $this->importService->unAssignArticleCategories($articleIds, $localCategory2->getId());

        $this->assertEquals(
            0,
            $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM s_articles_categories WHERE articleID IN (' . implode(', ', $articleIds) . ')')->fetchColumn()
        );
        $this->assertEquals(
            1,
            $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM s_articles_categories WHERE articleID = ?',
                [$articleWithCategory])->fetchColumn()
        );

        $this->assertEquals(
            0,
            $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM s_articles_categories_ro WHERE articleID IN (' . implode(', ', $articleIds) . ')')->fetchColumn()
        );
        //minimum category count in s_articles_categories_ro because one category is directly assigned
        $categoryCount = 1;
        //get the parent category count from the path
        $path = $this->manager->getConnection()->executeQuery('SELECT path FROM s_categories WHERE id = ?',
            [$localCategory2->getId()])->fetchColumn();
        //remove leading  pipe in path
        $path = substr($path, 1);
        $categoryCount += substr_count($path, '|');
        $this->assertEquals(
            $categoryCount,
            $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM s_articles_categories_ro WHERE articleID = ?',
                [$articleWithCategory])->fetchColumn()
        );

        $this->manager->clear();
        /** @var \Shopware\Models\Article\Article $article */
        foreach ($this->articleRepository->findBy(['id' => $articleIds]) as $article) {
            // check connect_mapped_category flag, must be null
            $this->assertNull($article->getAttribute()->getConnectMappedCategory());

            // check article->getCategories for each article, it should be an empty array
            $this->assertEmpty($article->getCategories());
        }
    }

    public function testFindRemoteArticleIdsByCategoryId()
    {
        // insert 3 articles
        $sourceIds = $this->insertOrUpdateProducts(3, false, false);

        // find articles by sourceId
        $connectAttributes = $this->connectAttributeRepository->findBy(['sourceId' => $sourceIds]);

        // map them to local category
        // map buecher category to local category
        $parentCategory = $this->categoryRepository->find(3);
        $localCategory = new Category();
        $localCategory->setName('MassImport #' . rand(1, 999999999));
        $localCategory->setParent($parentCategory);
        $this->manager->persist($localCategory);

        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => '/deutsch/bücher']);
        $remoteCategory->addLocalCategory($localCategory);
        $this->manager->persist($remoteCategory);
        $this->manager->flush();

        // assign local category to products
        $articleIds = [];
        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        foreach ($connectAttributes as $connectAttribute) {
            $article = $connectAttribute->getArticle();
            $article->addCategory($localCategory);

            $attribute = $article->getAttribute();
            $attribute->setConnectMappedCategory(true);

            $this->manager->persist($attribute);
            $this->manager->persist($article);

            // each entity is flushed separately, otherwise
            // $articleIds and $articleIds have same values, but different order
            $this->manager->flush($article);
            $this->manager->flush($attribute);

            $articleIds[] = $article->getId();
        }

        //call findRemoteArticleIdsByCategoryId
        // and compare returned array of ids
        $assignedArticleIds = $this->importService->findRemoteArticleIdsByCategoryId($localCategory->getId());

        $this->assertEquals($articleIds, $assignedArticleIds);
    }

    public function testImportRemoteCategory()
    {
        $this->importFixtures(__DIR__ . '/../_fixtures/simple_connect_items.sql');

        $bookCategoryId = $this->manager->getConnection()->executeQuery(
            'SELECT id FROM s_plugin_connect_categories WHERE category_key = ?', ['/deutsch/bücher']
        )->fetchColumn();
        $this->manager->getConnection()->executeQuery(
            'INSERT IGNORE INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (?, ?)',
            [14471, $bookCategoryId]
        );

        $this->manager->getConnection()->executeQuery(
            'INSERT IGNORE INTO `s_plugin_connect_categories` (`category_key`, `label`, `shop_id`) VALUES (?, ?, ?)',
            ['/deutsch/bücher/fantasy', 'Fantasy', 1234]
        );
        $fantasyCategoryId = $this->manager->getConnection()->lastInsertId();

        $this->manager->getConnection()->executeQuery(
            'INSERT IGNORE INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (?, ?)',
            [14471, $fantasyCategoryId]
        );

        // insert invalid articleId in s_plugin_connect_product_to_categories
        $this->manager->getConnection()->executeQuery(
            'INSERT IGNORE INTO `s_plugin_connect_product_to_categories` (`articleID`, `connect_category_id`) VALUES (?, ?)',
            [9087041234, $fantasyCategoryId]
        );

        $localCategory = $this->categoryRepository->find(35);
        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => '/deutsch/bücher']);

        $this->importService->importRemoteCategory(
            $localCategory->getId(),
            $remoteCategory->getCategoryKey(),
            $remoteCategory->getLabel(),
            3,
            'Awesome products'
        );

        /** @var Category $createdLocalCategory */
        $createdLocalCategory = $this->categoryRepository->findOneBy([
            'name' => $remoteCategory->getLabel(),
            'parent' => $localCategory->getId()
            ]);

        $this->assertInstanceOf(Category::class, $createdLocalCategory);

        self::assertEmpty($createdLocalCategory->getChildren());

        $articleIds = $this->productToRemoteCategoriesRepository->findArticleIdsByRemoteCategory($remoteCategory->getCategoryKey());
        $expectedArticleCount = count($articleIds);
        $actualArticleCount = (int) $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(*) FROM `s_articles_categories` WHERE `categoryID` = :categoryID',
            [':categoryID' => $createdLocalCategory->getId()]
        );
        $this->assertEquals($expectedArticleCount, $actualArticleCount);

        // verify that only valid articleIds will be returned. There isn't article with id 9087041234
        $fantasyArticleIds = $this->productToRemoteCategoriesRepository->findArticleIdsByRemoteCategory('/deutsch/bücher/fantasy');
        self::assertCount(1, $fantasyArticleIds);
        self::assertEquals(14471, $fantasyArticleIds[0]);
    }
}
