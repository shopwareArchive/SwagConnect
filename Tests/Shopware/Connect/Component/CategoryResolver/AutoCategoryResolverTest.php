<?php

namespace Tests\ShopwarePlugins\Connect\Component\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;

class AutoCategoryResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \ShopwarePlugins\Connect\Components\CategoryResolver */
    private $categoryResolver;

    public function setUp()
    {
        parent::setUp();

        $this->categoryResolver = new AutoCategoryResolver(
            Shopware()->Models(),
            Shopware()->Models()->getRepository('Shopware\Models\Category\Category'),
            Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\RemoteCategory')
        );
    }

    public function testResolve()
    {
        // todo@sb: implement
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
 