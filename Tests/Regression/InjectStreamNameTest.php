<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Regression;

use ShopwarePlugins\Connect\Components\CategoryExtractor;
use Shopware\CustomModels\Connect\AttributeRepository;
use ShopwarePlugins\Connect\Components\CategoryResolver;
use Shopware\Connect\Gateway;
use ShopwarePlugins\Connect\Components\RandomStringGenerator;
use Enlight_Components_Db_Adapter_Pdo_Mysql as Pdo;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

/**
 * This test is about injecting stream name in category id
 * used for import category tree.
 * Category Id must have following structure when shopId and stream name are provided:
 *
 * shopId5~stream~Dress~/Kleidung/Mantel0981
 *
 * https://jira.shopware.com/browse/CON-4761
 *
 * Class InjectStreamNameTest
 * @package ShopwarePlugins\Connect\Tests\Regression
 */
class InjectStreamNameTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    const PANTS_CATEGORY_KEY = '/Kleidung/Hosen';

    const PANTS_CATEGORY_LABEL = 'Hosen';

    const COAT_CATEGORY_KEY = '/Kleidung/Mantel';

    const COAT_CATEGORY_LABEL = 'Mantel';

    /**
     * @var CategoryExtractor
     */
    private $categoryExtractor;

    /**
     * @var CategoryResolver
     */
    private $categoryResolver;

    /**
     * @var Pdo
     */
    private $db;

    /**
     * @var RandomStringGenerator
     */
    private $randomStringGenerator;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->categoryResolver = $this->createMock(CategoryResolver::class);
        $this->db = Shopware()->Container()->get('db');
        $this->randomStringGenerator = $this->createMock(RandomStringGenerator::class);

        $this->categoryExtractor = new CategoryExtractor(
            $this->createMock(AttributeRepository::class),
            $this->categoryResolver,
            $this->createMock(Gateway::class),
            $this->randomStringGenerator,
            $this->db
        );
    }

    public function testAlwaysInjectStreamName()
    {
        $parent = '/Kleidung';

        $rows = [
            self::PANTS_CATEGORY_KEY => self::PANTS_CATEGORY_LABEL,
            self::COAT_CATEGORY_KEY => self::COAT_CATEGORY_LABEL,
        ];

        $shopId = 5;
        $streamName = 'Dress';
        $articleId = 275;

        $this->db->executeQuery(
            'INSERT INTO s_plugin_connect_items (article_id, purchase_price_hash, offer_valid_until, stream, shop_id)
              VALUES (?, "hash", 123, ?, ?)', [ $articleId, $streamName, $shopId]
        );

        $this->db->executeQuery(
            'INSERT INTO s_plugin_connect_categories (category_key, label)
              VALUES (?, ?)', [ self::PANTS_CATEGORY_KEY, self::PANTS_CATEGORY_LABEL]
        );

        $this->db->executeQuery(
            'INSERT INTO s_plugin_connect_product_to_categories (articleID, connect_category_id)
              VALUES (?, ?)', [ $articleId, $this->db->lastInsertId('s_plugin_connect_categories')]
        );

        $this->db->executeQuery(
            'INSERT INTO s_plugin_connect_categories (category_key, label)
              VALUES (?, ?)', [ self::COAT_CATEGORY_KEY, self::COAT_CATEGORY_LABEL]
        );
        $this->db->executeQuery(
            'INSERT INTO s_plugin_connect_product_to_categories (articleID, connect_category_id)
              VALUES (?, ?)', [ $articleId, $this->db->lastInsertId('s_plugin_connect_categories')]
        );

        $this->categoryResolver->expects($this->once())
            ->method('generateTree')
            ->with($rows, $parent)
            ->willReturn([
                self::PANTS_CATEGORY_KEY => [
                    'name' => self::PANTS_CATEGORY_LABEL,
                    'children' => [],
                    'categoryId' => self::PANTS_CATEGORY_KEY,
                    'leaf' => true,
                ],
                self::COAT_CATEGORY_KEY => [
                    'name' => self::COAT_CATEGORY_LABEL,
                    'children' => [],
                    'categoryId' => self::COAT_CATEGORY_KEY,
                    'leaf' => true,
                ],
            ]);

        $this->randomStringGenerator->expects($this->at(0))
            ->method('generate')
            ->with(sprintf('shopId%s~stream~%s~%s', $shopId, $streamName, self::PANTS_CATEGORY_KEY))
            ->willReturn(sprintf('shopId%s~stream~%s~%s%s', $shopId, $streamName, self::PANTS_CATEGORY_KEY, '1049'));

        $this->randomStringGenerator->expects($this->at(1))
            ->method('generate')
            ->with(sprintf('shopId%s~stream~%s~%s', $shopId, $streamName, self::COAT_CATEGORY_KEY))
            ->willReturn(sprintf('shopId%s~stream~%s~%s%s', $shopId, $streamName, self::COAT_CATEGORY_KEY, '0981'));

        $this->assertEquals(
            [
                [
                    'id' => sprintf('shopId5~stream~%s~%s1049', $streamName, self::PANTS_CATEGORY_KEY),
                    'categoryId' => self::PANTS_CATEGORY_KEY,
                    'name' => self::PANTS_CATEGORY_LABEL,
                    'leaf' => true,
                    'children' => [],
                    'cls' => 'sc-tree-node',
                    'expanded' => false
                ],
                [
                    'id' => sprintf('shopId5~stream~%s~%s0981', $streamName, self::COAT_CATEGORY_KEY),
                    'categoryId' => self::COAT_CATEGORY_KEY,
                    'name' => self::COAT_CATEGORY_LABEL,
                    'leaf' => true,
                    'children' => [],
                    'cls' => 'sc-tree-node',
                    'expanded' => false
                ],
            ],
            $this->categoryExtractor->getRemoteCategoriesTree($parent, false, false, $shopId, $streamName)
        );
    }
}
