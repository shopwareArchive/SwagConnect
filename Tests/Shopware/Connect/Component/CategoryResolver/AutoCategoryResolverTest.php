<?php

namespace Tests\ShopwarePlugins\Connect\Component\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;
use Shopware\Models\Category\Category;

class AutoCategoryResolverTest extends ConnectTestHelper
{
    /** @var  \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver */
    private $categoryResolver;
    private $manager;
    private $categoryRepo;

    /** @var \ShopwarePlugins\Connect\Components\Config   */
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

        if (!$nikeBootsCategory){
            $nikeBootsCategory = $this->categoryResolver->convertNodeToEntity([
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
            Shopware()->Db()->fetchOne('SELECT parent FROM s_categories WHERE description = ?', array('Adidas'))
        );
        // only addidas/boots and nike/tshirts must be returned - CON-4549.
        $this->assertCount(2, $categoryModels);
        $this->assertEquals($nikeCategory->getId(), $categoryModels[0]->getParent()->getId());
        $this->assertEquals('Tshirts', $categoryModels[0]->getName());
        $this->assertEquals('Adidas', $categoryModels[1]->getParent()->getName());
        $this->assertEquals('Boots', $categoryModels[1]->getName());

        Shopware()->Db()->exec('DELETE FROM s_categories WHERE description IN ("'. implode('","', $categories) .'")');
    }

    public function testGenerateTree()
    {
        $categories = array(
            '/Kleidung' => 'Kleidung',
            '/Kleidung/Hosen' => 'Hosen',
            '/Kleidung/Hosen/Hosentraeger' => 'Hosenträger',
            '/Nahrung & Getraenke' => 'Nahrung & Getränke',
            '/Nahrung & Getraenke/Alkoholische Getraenke' => 'Alkoholische Getränke',
            '/Nahrung & Getraenke/Alkoholische Getraenke/Bier' => 'Bier',
        );

        $expected = array(
            '/Kleidung' => array(
                'name' => 'Kleidung',
                'categoryId' => '/Kleidung',
                'leaf' => false,
                'children' => array(
                    '/Kleidung/Hosen' => array(
                        'name' => 'Hosen',
                        'categoryId' => '/Kleidung/Hosen',
                        'leaf' => false,
                        'children' => array(
                            '/Kleidung/Hosen/Hosentraeger' => array(
                                'name' => 'Hosenträger',
                                'categoryId' => '/Kleidung/Hosen/Hosentraeger',
                                'leaf' => true,
                                'children' => array(),
                            )
                        ),
                    ),
                ),
            ),
            '/Nahrung & Getraenke' => array(
                'name' => 'Nahrung & Getränke',
                'categoryId' => '/Nahrung & Getraenke',
                'leaf' => false,
                'children' => array(
                    '/Nahrung & Getraenke/Alkoholische Getraenke' => array(
                        'name' => 'Alkoholische Getränke',
                        'categoryId' => '/Nahrung & Getraenke/Alkoholische Getraenke',
                        'leaf' => false,
                        'children' => array(
                            '/Nahrung & Getraenke/Alkoholische Getraenke/Bier' => array(
                                'name' => 'Bier',
                                'categoryId' => '/Nahrung & Getraenke/Alkoholische Getraenke/Bier',
                                'leaf' => true,
                                'children' => array(),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $this->assertEquals($expected, $this->categoryResolver->generateTree($categories));
    }
}
 