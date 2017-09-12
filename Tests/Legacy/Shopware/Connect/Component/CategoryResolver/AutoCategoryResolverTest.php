<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component\CategoryResolver;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class AutoCategoryResolverTest extends ConnectTestHelper
{
    /** @var \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver */
    private $categoryResolver;
    private $manager;
    private $categoryRepo;

    /** @var \ShopwarePlugins\Connect\Components\Config */
    private $config;

    public function setUp()
    {
        parent::setUp();

        $this->manager = Shopware()->Models();
        $this->config = ConfigFactory::getConfigInstance();
        $this->categoryRepo = $this->manager->getRepository('Shopware\Models\Category\Category');

        $this->categoryResolver = new AutoCategoryResolver(
            $this->manager,
            $this->categoryRepo,
            $this->manager->getRepository('Shopware\CustomModels\Connect\RemoteCategory'),
            $this->config,
            $this->manager->getRepository(ProductToRemoteCategory::class)
        );
    }

    public function testResolve()
    {
        $defaultCategoryId = $this->config->getDefaultShopCategory()->getId();
        $defaultCategory = $this->categoryRepo->findOneBy([
            'id' => $defaultCategoryId
        ]);
        $this->assertEquals('Deutsch', $defaultCategory->getName());

        $bootsCategory = $this->categoryRepo->findOneBy([
            'name' => 'Boots',
            'parentId' => $defaultCategoryId
        ]);
        if (!$bootsCategory) {
            $this->categoryResolver->convertNodeToEntity([
                'name' => 'Boots',
                'categoryId' => '/deutsch/boots'
            ], $defaultCategory);
        }

        $nikeCategory = $this->categoryRepo->findOneBy([
            'name' => 'Nike',
            'parentId' => $defaultCategoryId
        ]);

        if (!$nikeCategory) {
            $nikeCategory = $this->categoryResolver->convertNodeToEntity([
                'name' => 'Nike',
                'categoryId' => '/deutsch/nike'
            ], $defaultCategory);
        }

        $nikeBootsCategory = $this->categoryRepo->findOneBy([
            'name' => 'Boots',
            'parentId' => $nikeCategory
        ]);

        if (!$nikeBootsCategory) {
            $this->categoryResolver->convertNodeToEntity([
                'name' => 'Boots',
                'categoryId' => '/deutsch/nike/boots'
            ], $nikeCategory);
        }

        $categories = [
            '/deutsch/nike/tshirts' => 'Tshirts',
            '/deutsch/nike' => 'Nike',
            '/deutsch/adidas/boots' => 'Boots',
            '/deutsch/adidas' => 'Adidas',
            '/deutsch' => 'Deutsch',
        ];

        $categoryModels = $this->categoryResolver->resolve($categories);

        $this->assertEquals(
            $defaultCategoryId,
            Shopware()->Db()->fetchOne('SELECT parent FROM s_categories WHERE description = ?', ['Adidas'])
        );

        $this->assertCount(2, $categoryModels);
        $this->assertEquals('Tshirts', $this->categoryRepo->findOneById($categoryModels[0])->getName());
        $this->assertEquals('Boots', $this->categoryRepo->findOneById($categoryModels[1])->getName());

        unset($categories['/deutsch']);
        Shopware()->Db()->exec('DELETE FROM s_categories WHERE description IN ("' . implode('","', $categories) . '")');
    }

    public function testGenerateTree()
    {
        $categories = [
            '/Kleidung' => 'Kleidung',
            '/Kleidung/Hosen' => 'Hosen',
            '/Kleidung/Hosen/Hosentraeger' => 'Hosenträger',
            '/Nahrung & Getraenke' => 'Nahrung & Getränke',
            '/Nahrung & Getraenke/Alkoholische Getraenke' => 'Alkoholische Getränke',
            '/Nahrung & Getraenke/Alkoholische Getraenke/Bier' => 'Bier',
        ];

        $expected = [
            '/Kleidung' => [
                'name' => 'Kleidung',
                'categoryId' => '/Kleidung',
                'leaf' => false,
                'children' => [
                    '/Kleidung/Hosen' => [
                        'name' => 'Hosen',
                        'categoryId' => '/Kleidung/Hosen',
                        'leaf' => false,
                        'children' => [
                            '/Kleidung/Hosen/Hosentraeger' => [
                                'name' => 'Hosenträger',
                                'categoryId' => '/Kleidung/Hosen/Hosentraeger',
                                'leaf' => true,
                                'children' => [],
                            ]
                        ],
                    ],
                ],
            ],
            '/Nahrung & Getraenke' => [
                'name' => 'Nahrung & Getränke',
                'categoryId' => '/Nahrung & Getraenke',
                'leaf' => false,
                'children' => [
                    '/Nahrung & Getraenke/Alkoholische Getraenke' => [
                        'name' => 'Alkoholische Getränke',
                        'categoryId' => '/Nahrung & Getraenke/Alkoholische Getraenke',
                        'leaf' => false,
                        'children' => [
                            '/Nahrung & Getraenke/Alkoholische Getraenke/Bier' => [
                                'name' => 'Bier',
                                'categoryId' => '/Nahrung & Getraenke/Alkoholische Getraenke/Bier',
                                'leaf' => true,
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $this->categoryResolver->generateTree($categories));
    }
}
