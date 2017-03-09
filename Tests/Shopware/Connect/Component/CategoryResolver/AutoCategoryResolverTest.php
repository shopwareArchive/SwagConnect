<?php

namespace Tests\ShopwarePlugins\Connect\Component\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class AutoCategoryResolverTest extends ConnectTestHelper
{
    /** @var  \ShopwarePlugins\Connect\Components\CategoryResolver */
    private $categoryResolver;
    private $manager;
    private $categoryRepo;

    public function setUp()
    {
        parent::setUp();

        $this->manager = Shopware()->Models();
        $this->categoryRepo = $this->manager->getRepository('Shopware\Models\Category\Category');

        $this->categoryResolver = new AutoCategoryResolver(
            $this->manager,
            $this->categoryRepo,
            $this->manager->getRepository('Shopware\CustomModels\Connect\RemoteCategory'),
            Shopware()->Container()->get('shop')
        );
    }

    public function testResolve()
    {
        $categories = [
            '/spanish/nike/tshirts' => 'Tshirts',
            '/spanish/nike' => 'Nike',
            '/spanish/adidas/boots' => 'Boots',
            '/spanish/adidas' => 'Adidas',
            '/spanish' => 'Spanish',
        ];

        $this->categoryResolver->resolve($categories);

        //Spanish category must not be created
        $this->assertNull($this->categoryRepo->findOneByName('Spanish'));
        $this->assertEquals(
            Shopware()->Container()->get('shop')->getCategory()->getId(),
            $expected = Shopware()->Db()->fetchOne('SELECT parent FROM s_categories WHERE description = ?', array('Adidas'))
        );

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
 