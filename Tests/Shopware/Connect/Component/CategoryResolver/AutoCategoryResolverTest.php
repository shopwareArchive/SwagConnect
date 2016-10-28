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
                'children' => array(
                    '/Kleidung/Hosen' => array(
                        'name' => 'Hosen',
                        'children' => array(
                            '/Kleidung/Hosen/Hosentraeger' => array(
                                'name' => 'Hosenträger',
                                'children' => array(),
                            )
                        ),
                    ),
                ),
            ),
            '/Nahrung & Getraenke' => array(
                'name' => 'Nahrung & Getränke',
                'children' => array(
                    '/Nahrung & Getraenke/Alkoholische Getraenke' => array(
                        'name' => 'Alkoholische Getränke',
                        'children' => array(
                            '/Nahrung & Getraenke/Alkoholische Getraenke/Bier' => array(
                                'name' => 'Bier',
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
 