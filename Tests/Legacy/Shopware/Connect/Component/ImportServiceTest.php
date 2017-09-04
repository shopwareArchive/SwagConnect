<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component;

use Shopware\Models\Category\Category;
use ShopwarePlugins\Connect\Components\CategoryExtractor;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\ImportService;
use ShopwarePlugins\Connect\Components\RandomStringGenerator;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;
use Shopware\Connect\Gateway\PDO;

class ImportServiceTest extends ConnectTestHelper
{
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
        $this->articleRepository = $this->manager->getRepository('Shopware\Models\Article\Article');
        $autoCategoryResolver = new AutoCategoryResolver(
            $this->manager,
            $this->categoryRepository,
            $this->remoteCategoryRepository,
            ConfigFactory::getConfigInstance()
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

    public function testUnAssignArticleCategories()
    {
        Shopware()->Db()->exec('DELETE FROM `s_plugin_connect_categories`');

        $sourceIds = $this->insertOrUpdateProducts(3, false, false);

        // find articles by sourceId
        $connectAttributes = $this->connectAttributeRepository->findBy(['sourceId' => $sourceIds]);

        // map buecher category to some local category
        $localCategory = $this->categoryRepository->find(6);
        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => '/bücher']);
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
        $db = Shopware()->Db();
        $this->assertEquals(
            0,
            $db->query('SELECT COUNT(*) FROM s_articles_categories WHERE articleID IN (' . implode(', ', $articleIds) . ')')->fetchColumn()
        );

        $this->assertEquals(
            0,
            $db->query('SELECT COUNT(*) FROM s_articles_categories_ro WHERE articleID IN (' . implode(', ', $articleIds) . ')')->fetchColumn()
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
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => '/bücher']);
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
}
