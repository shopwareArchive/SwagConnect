<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;
use Shopware\Models\Category\Category;

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
        $this->config = new \ShopwarePlugins\Connect\Components\Config($this->manager);
        $this->categoryRepo = $this->manager->getRepository('Shopware\Models\Category\Category');

        $this->categoryResolver = new AutoCategoryResolver(
            $this->manager,
            $this->categoryRepo,
            $this->manager->getRepository('Shopware\CustomModels\Connect\RemoteCategory'),
            $this->config
        );
    }

    public function testResolve()
    {
        $defaultCategoryId = $this->config->getDefaultShopCategory()->getId();
        $defaultCategory = $this->categoryRepo->findOneBy([
            'id' => $defaultCategoryId
        ]);

        $bootsCategory = $this->categoryRepo->findOneBy([
            'name' => 'Boots',
            'parentId' => $defaultCategoryId
        ]);
        if (!$bootsCategory) {
            $this->categoryResolver->convertNodeToEntity([
                'name' => 'Boots',
                'categoryId' => '/spanish/boots'
            ], $defaultCategory);
        }

        $nikeCategory = $this->categoryRepo->findOneBy([
            'name' => 'Nike',
            'parentId' => $defaultCategoryId
        ]);

        if (!$nikeCategory) {
            $nikeCategory = $this->categoryResolver->convertNodeToEntity([
                'name' => 'Nike',
                'categoryId' => '/spanish/nike'
            ], $defaultCategory);
        }

        $nikeBootsCategory = $this->categoryRepo->findOneBy([
            'name' => 'Boots',
            'parentId' => $nikeCategory
        ]);

        if (!$nikeBootsCategory) {
            $this->categoryResolver->convertNodeToEntity([
                'name' => 'Boots',
                'categoryId' => '/spanish/nike/boots'
            ], $nikeCategory);
        }

        $categories = [
            '/spanish/nike/tshirts' => 'Tshirts',
            '/spanish/nike' => 'Nike',
            '/spanish/adidas/boots' => 'Boots',
            '/spanish/adidas' => 'Adidas',
            '/spanish' => 'Spanish',
        ];

        $categoryModels = $this->categoryResolver->resolve($categories);

        //Spanish category must not be created
        $this->assertNull($this->categoryRepo->findOneByName('Spanish'));
        $this->assertEquals(
            $defaultCategoryId,
            Shopware()->Db()->fetchOne('SELECT parent FROM s_categories WHERE description = ?', ['Adidas'])
        );

        $this->assertCount(4, $categoryModels);
        $this->assertEquals($nikeCategory->getId(), $categoryModels[0]->getId());
        $this->assertEquals('Tshirts', $categoryModels[1]->getName());
        $this->assertEquals('Adidas', $categoryModels[2]->getName());
        $this->assertEquals('Boots', $categoryModels[3]->getName());

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
