<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Models\Article\Unit;
use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;
use Shopware\CustomModels\Connect\RemoteCategory;
use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;

class HelperTest extends ConnectTestHelper
{
    public function testGetDefaultCustomerGroup()
    {
        $group = $this->getHelper()->getDefaultCustomerGroup();
        $this->assertInstanceOf('\Shopware\Models\Customer\Group', $group);
    }

    /**
     * @depends testGetProductById
     */
    public function testGetArticleModelByProduct($product)
    {
        $model = $this->getHelper()->getArticleModelByProduct($product);
        $this->assertNotEmpty($model->getName());
    }

    public function testHasBasketConnectProductsIsFalse()
    {
        $result = $this->getHelper()->hasBasketConnectProducts(333);
        $this->assertFalse($result);
    }

    public function testGetConnectAttributeByModel()
    {
        $attributes = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute')->findBy(['articleId' => 14]);
        foreach ($attributes as $attribute) {
            Shopware()->Models()->remove($attribute);
        }
        Shopware()->Models()->flush();

        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find(14);

        $connectAttribute = $this->getHelper()->getConnectAttributeByModel($model);
        $this->assertEmpty($connectAttribute);
    }

    public function testGetOrCreateConnectAttributeByModel()
    {
        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find(14);

        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($model);
        $this->assertNotEmpty($connectAttribute->getId());
    }

    public function _testGetImagesById()
    {
        $images = [];
        for ($i = 0; $i < 10; ++$i) {
            $images[] = self::IMAGE_PROVIDER_URL . '?' . rand(0, 9999);
        }

        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);

        $this->getImageImport()->importImagesForArticle(
            $images,
            $model
        );

        $images = $this->callPrivate($this->getHelper(), 'getImagesById', 2);
        $this->assertCount(13, count($images));
    }

    public function testUpdateUnitInRelatedProducts()
    {
        $localUnit = new Unit();
        $localUnit->setName('Yard');
        $localUnit->setUnit('lyrd');
        Shopware()->Models()->persist($localUnit);
        Shopware()->Models()->flush($localUnit);

        $remoteUnit = 'yrd';

        $manager = $this->getMockBuilder('\\Shopware\\Components\\Model\\ModelManager')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\\Doctrine\\DBAL\\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->any())->method('getConnection')->willReturn($connection);

        $statement = $this->getMockBuilder('\\Doctrine\\DBAL\\Statement')
        ->disableOriginalConstructor()
        ->getMock();

        $connection->expects($this->any())->method('prepare')->with(
            'UPDATE s_articles_details sad
            LEFT JOIN s_articles_attributes saa ON sad.id = saa.articledetailsID
            SET sad.unitID = :unitId
            WHERE saa.connect_remote_unit = :remoteUnit'
        )->willReturn($statement);

        $statement->expects($this->at(0))->method('bindValue')->with(':unitId', $localUnit->getId(), \PDO::PARAM_INT);
        $statement->expects($this->at(1))->method('bindValue')->with(':remoteUnit', $remoteUnit, \PDO::PARAM_STR);

        $statement->expects($this->atLeast(1))->method('execute');

        $helper = new Helper(
            $manager,
            $this->getMockBuilder('\\ShopwarePlugins\\Connect\\Components\\CategoryQuery\\SwQuery')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('\\ShopwarePlugins\\Connect\\Components\\ProductQuery')->disableOriginalConstructor()->getMock()
        );

        $helper->updateUnitInRelatedProducts($localUnit, $remoteUnit);
        Shopware()->Models()->remove($localUnit);
        Shopware()->Models()->flush($localUnit);
    }

    public function testIsMainVariant()
    {
        $article = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->findOneBy(['id' => 5]);
        $this->getHelper()->getOrCreateConnectAttributeByModel($article);
        $detail = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail')->findOneBy(['id' => 253]);
        $this->getHelper()->getOrCreateConnectAttributeByModel($detail);

        $this->assertTrue($this->getHelper()->isMainVariant('5'));
        $this->assertFalse($this->getHelper()->isMainVariant('5-253'));
    }

    public function testGenerateSourceId()
    {
        /** @var \Shopware\Models\Article\Detail $detail */
        $detail = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail')->findOneBy(['kind' => 2]);

        $detailSourceId = sprintf(
            '%s-%s',
            $detail->getArticle()->getId(),
            $detail->getId()
        );
        $this->assertEquals($this->getHelper()->generateSourceId($detail), $detailSourceId);
        $articleSourceId = (string) $detail->getArticle()->getId();

        $this->assertEquals($this->getHelper()->generateSourceId($detail->getArticle()->getMainDetail()), $articleSourceId);
    }

    public function testGetConnectCategoriesForProductAndAutResolveCategories()
    {
        $manager = Shopware()->Models();
        $article = $manager->getRepository(Article::class)->findOneBy(['id' => 5]);
        $manager->getConnection()->executeQuery(
            'DELETE FROM `s_articles_categories` WHERE `articleID` = :articleID',
            [':articleID' => $article->getId()]
        );
        //test with leaf category and not leafcategory
        //ids come from default fixture they shouldn't change
        $notLeafCategoryId = 9;
        $count = $manager->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM `s_categories` WHERE `parent` = :parentID',
            [':parentID' => $notLeafCategoryId]
        )->fetchColumn();
        $this->assertGreaterThan(0, $count);
        $leafCategoryId = 14;
        $count = $manager->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM `s_categories` WHERE `parent` = :parentID',
            [':parentID' => $leafCategoryId]
        )->fetchColumn();
        $this->assertEquals(0, $count);
        $manager->getConnection()->executeQuery(
            'INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (?, ?)',
            [$article->getId(), $notLeafCategoryId]
        );
        $manager->getConnection()->executeQuery(
            'INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (?, ?)',
            [$article->getId(), $leafCategoryId]
        );

        $categories = $this->getHelper()->getConnectCategoryForProduct($article->getId());

        $autoCategoryResolver = new AutoCategoryResolver(
            $manager,
            $manager->getRepository(Category::class),
            $manager->getRepository(RemoteCategory::class),
            ConfigFactory::getConfigInstance(),
            $manager->getRepository(ProductToRemoteCategory::class)
        );

        $categoryKeys = $autoCategoryResolver->resolve($categories);

        $this->assertCount(2, $categoryKeys);
        $this->assertGreaterThan(count($categoryKeys), count($categories));

        $name = $manager->getConnection()->executeQuery(
            'SELECT `description` FROM `s_categories` WHERE `id` = :categoryID',
            [':categoryID' => $categoryKeys[0]]
        )->fetchColumn();
        $this->assertEquals('Edelbrände', $name);

        $name = $manager->getConnection()->executeQuery(
            'SELECT `description` FROM `s_categories` WHERE `id` = :categoryID',
            [':categoryID' => $categoryKeys[1]]
        )->fetchColumn();
        $this->assertEquals('Freizeitwelten', $name);
    }

    private function resetConnectCategoryMappings()
    {
        $conn = Shopware()->Db();
        $conn->exec('UPDATE s_categories_attributes SET connect_import_mapping = NULL, connect_export_mapping = NULL');
    }
}
