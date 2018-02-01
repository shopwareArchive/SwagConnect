<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component\Translations;

use Shopware\Connect\Struct\Translation;
use Shopware\Models\Shop\Locale;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class ProductTranslatorTest extends ConnectTestHelper
{
    /**
     * @var \ShopwarePlugins\Connect\Components\Translations\ProductTranslatorInterface
     */
    private $productTranslator;

    private $configComponent;

    private $translationGateway;

    private $modelManager;

    private $shopRepository;

    private $localeRepository;

    public function setUp()
    {
        parent::setUp();

        $this->configComponent = $this->getMockBuilder('\\ShopwarePlugins\\Connect\\Components\\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationGateway = $this->getMockBuilder('\\ShopwarePlugins\\Connect\\Components\\Gateway\\ProductTranslationsGateway\\PdoProductTranslationsGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $this->modelManager = $this->getMockBuilder('\\Shopware\\Components\\Model\\ModelManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeRepository = $this->getMockBuilder('\\Shopware\\Components\\Model\\ModelRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shopRepository = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Repository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productTranslator = new \ShopwarePlugins\Connect\Components\Translations\ProductTranslator(
            $this->configComponent,
            $this->translationGateway,
            $this->modelManager,
            $this->getProductBaseUrl()
        );
    }

    public function testTranslate()
    {
        $translations = [
            2 => [
                'title' => 'shopware Connect Local Product EN',
                'shortDescription' => 'shopware Connect Local Product short description EN',
                'longDescription' => 'shopware Connect Local Product long description EN',
                'url' => $this->getProductBaseUrl() . '35/shId/2',
            ],
            176 => [
                'title' => 'shopware Connect Local Product NL',
                'shortDescription' => 'shopware Connect Local Product short description NL',
                'longDescription' => 'shopware Connect Local Product long description NL',
                'url' => $this->getProductBaseUrl() . '35/shId/3',
            ],
        ];
        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 176]);
        $this->translationGateway->expects($this->any())->method('getTranslations')->with(108, [2, 176])->willReturn($translations);

        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $enLocale = new Locale();
        $enLocale->setLocale('en_GB');
        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');


        $shop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();

        $shop->method('getId')
            ->will($this->onConsecutiveCalls(2, 3));

        $shop->method('getLocale')
            ->will($this->onConsecutiveCalls($enLocale, $nlLocale));

        $this->shopRepository->expects($this->any())->method('find')->willReturn($shop);

        $expected = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->assertEquals($expected, $this->productTranslator->translate(108, 35));
    }

    public function testTranslateWhenLocaleNotFound()
    {
        $translations = [
            2 => [
                'title' => 'shopware Connect Local Product EN',
                'shortDescription' => 'shopware Connect Local Product short description EN',
                'longDescription' => 'shopware Connect Local Product long description EN',
                'url' => $this->getProductBaseUrl() . '35/shId/2',
            ],
            176 => [
                'title' => 'shopware Connect Local Product NL',
                'shortDescription' => 'shopware Connect Local Product short description NL',
                'longDescription' => 'shopware Connect Local Product long description NL',
                'url' => $this->getProductBaseUrl() . '35/shId/3',
            ],
        ];
        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 176]);
        $this->translationGateway->expects($this->any())->method('getTranslations')->with(108, [2, 176])->willReturn($translations);
        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $locale = new Locale();
        $locale->setLocale(null);
        $shop = new \Shopware\Models\Shop\Shop();
        $shop->setLocale($locale);

        $this->shopRepository->expects($this->any())->method('find')->willReturn($shop);

        $this->assertEmpty($this->productTranslator->translate(108, 35));
    }

    public function testTranslateWhenShopNotFound()
    {
        $translations = [
            2 => [
                'title' => 'shopware Connect Local Product EN',
                'shortDescription' => 'shopware Connect Local Product short description EN',
                'longDescription' => 'shopware Connect Local Product long description EN',
                'url' => $this->getProductBaseUrl() . '35/shId/2',
            ],
            176 => [
                'title' => 'shopware Connect Local Product NL',
                'shortDescription' => 'shopware Connect Local Product short description NL',
                'longDescription' => 'shopware Connect Local Product long description NL',
                'url' => $this->getProductBaseUrl() . '35/shId/3',
            ],
        ];
        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 176]);
        $this->translationGateway->expects($this->any())->method('getTranslations')->with(108, [2, 176])->willReturn($translations);
        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);
        $this->shopRepository->expects($this->any())->method('find')->willReturn(null);

        $this->assertEmpty($this->productTranslator->translate(108, 35));
    }

    public function testTranslateConfiguratorGroup()
    {
        $groupTranslations = [
            2 => 'color',
            3 => 'kleur',
        ];

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 3]);
        $this->translationGateway->expects($this->any())->method('getConfiguratorGroupTranslations')->with(15, [2, 3])->willReturn($groupTranslations);

        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $enLocale = new Locale();
        $enLocale->setLocale('en_GB');


        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');

        $enShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $enShop->expects($this->any())->method('getLocale')->willReturn($enLocale);

        $nlShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $nlShop->expects($this->any())->method('getLocale')->willReturn($nlLocale);

        $this->shopRepository->expects($this->at(0))->method('find')->with(2)->willReturn($enShop);
        $this->shopRepository->expects($this->at(1))->method('find')->with(3)->willReturn($nlShop);

        $expected = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                    'variantLabels' => [
                        'farbe' => 'color'
                    ],
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                    'variantLabels' => [
                        'farbe' => 'kleur'
                    ],
                ]
            )
        ];

        $translations = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->assertEquals($expected, $this->productTranslator->translateConfiguratorGroup(15, 'farbe', $translations));
    }

    public function testTranslateConfiguratorGroupWithDefaultShopOnly()
    {
        // translate should be the same after group translation
        // when exportLanguages contains only default shop language
        $translations = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([1]);
        $this->assertEquals($translations, $this->productTranslator->translateConfiguratorGroup(15, 'farbe', $translations));
    }

    public function testTranslateConfiguratorGroupWhenLocaleNotFound()
    {
        $groupTranslations = [
            2 => 'color',
            3 => 'kleur',
        ];

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 3]);
        $this->translationGateway->expects($this->any())->method('getConfiguratorGroupTranslations')->with(15, [2, 3])->willReturn($groupTranslations);

        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');

        $enShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $enShop->expects($this->any())->method('getLocale')->willReturn(null);

        $nlShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $nlShop->expects($this->any())->method('getLocale')->willReturn($nlLocale);

        $this->shopRepository->expects($this->at(0))->method('find')->with(2)->willReturn($enShop);
        $this->shopRepository->expects($this->at(1))->method('find')->with(3)->willReturn($nlShop);

        $expected = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                    'variantLabels' => [
                        'farbe' => 'kleur'
                    ],
                ]
            )
        ];

        $translations = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->assertEquals($expected, $this->productTranslator->translateConfiguratorGroup(15, 'farbe', $translations));
    }

    public function testTranslateConfiguratorGroupWithoutStruct()
    {
        $groupTranslations = [
            2 => 'color',
            3 => 'kleur',
        ];

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 3]);
        $this->translationGateway->expects($this->any())->method('getConfiguratorGroupTranslations')->with(15, [2, 3])->willReturn($groupTranslations);

        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');

        $enShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $enShop->expects($this->any())->method('getLocale')->willReturn(null);

        $nlShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $nlShop->expects($this->any())->method('getLocale')->willReturn($nlLocale);

        $this->shopRepository->expects($this->at(0))->method('find')->with(2)->willReturn($enShop);
        $this->shopRepository->expects($this->at(1))->method('find')->with(3)->willReturn($nlShop);

        $expected = [
            'gb' => [],
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                    'variantLabels' => [
                        'farbe' => 'kleur'
                    ],
                ]
            )
        ];

        $translations = [
            'gb' => [],
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->assertEquals($expected, $this->productTranslator->translateConfiguratorGroup(15, 'farbe', $translations));
    }

    public function testTranslateConfiguratorGroupWithInvalidLocaleCode()
    {
        $groupTranslations = [
            2 => 'color',
            3 => 'kleur',
        ];

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 3]);
        $this->translationGateway->expects($this->any())->method('getConfiguratorGroupTranslations')->with(15, [2, 3])->willReturn($groupTranslations);

        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $enLocale = new Locale();
        $enLocale->setLocale('en-EN');

        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');

        $enShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $enShop->expects($this->any())->method('getLocale')->willReturn($enLocale);

        $nlShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $nlShop->expects($this->any())->method('getLocale')->willReturn($nlLocale);

        $this->shopRepository->expects($this->at(0))->method('find')->with(2)->willReturn($enShop);
        $this->shopRepository->expects($this->at(1))->method('find')->with(3)->willReturn($nlShop);

        $expected = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                    'variantLabels' => [
                        'farbe' => 'kleur'
                    ],
                ]
            )
        ];

        $translations = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->assertEquals($expected, $this->productTranslator->translateConfiguratorGroup(15, 'farbe', $translations));
    }

    public function testTranslateConfiguratorOption()
    {
        $optionTranslations = [
            2 => 'red',
            3 => 'rood',
        ];

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 3]);
        $this->translationGateway->expects($this->any())->method('getConfiguratorOptionTranslations')->with(15, [2, 3])->willReturn($optionTranslations);

        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $enLocale = new Locale();
        $enLocale->setLocale('en_GB');

        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');

        $enShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $enShop->expects($this->any())->method('getLocale')->willReturn($enLocale);

        $nlShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $nlShop->expects($this->any())->method('getLocale')->willReturn($nlLocale);

        $this->shopRepository->expects($this->at(0))->method('find')->with(2)->willReturn($enShop);
        $this->shopRepository->expects($this->at(1))->method('find')->with(3)->willReturn($nlShop);

        $expected = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                    'variantValues' => [
                        'rot' => 'red'
                    ],
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                    'variantValues' => [
                        'rot' => 'rood'
                    ],
                ]
            )
        ];

        $translations = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->assertEquals($expected, $this->productTranslator->translateConfiguratorOption(15, 'rot', $translations));
    }

    public function testTranslateConfiguratorOptionWithDefaultShopOnly()
    {
        // translate should be the same after group translation
        // when exportLanguages contains only default shop language
        $translations = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([1]);
        $this->assertEquals($translations, $this->productTranslator->translateConfiguratorGroup(15, 'red', $translations));
    }

    public function testTranslateConfiguratorOptionWithInvalidShop()
    {
        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);
        $this->shopRepository->expects($this->at(0))->method('find')->with(6)->willReturn(null);

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([1, 6]);
        $this->assertEmpty($this->productTranslator->translateConfiguratorGroup(15, 'red', []));
    }

    public function testTranslateConfiguratorOptionWhenLocaleNotFound()
    {
        $optionTranslations = [
            2 => 'red',
            3 => 'rood',
        ];

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 3]);
        $this->translationGateway->expects($this->any())->method('getConfiguratorOptionTranslations')->with(15, [2, 3])->willReturn($optionTranslations);

        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');

        $enShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $enShop->expects($this->any())->method('getLocale')->willReturn(null);

        $nlShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $nlShop->expects($this->any())->method('getLocale')->willReturn($nlLocale);

        $this->shopRepository->expects($this->at(0))->method('find')->with(2)->willReturn($enShop);
        $this->shopRepository->expects($this->at(1))->method('find')->with(3)->willReturn($nlShop);

        $expected = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                    'variantValues' => [
                        'rot' => 'rood'
                    ],
                ]
            )
        ];

        $translations = [
            'gb' => new Translation(
                [
                    'title' => 'shopware Connect Local Product EN',
                    'shortDescription' => 'shopware Connect Local Product short description EN',
                    'longDescription' => 'shopware Connect Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35/shId/2',
                ]
            ),
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->assertEquals($expected, $this->productTranslator->translateConfiguratorOption(15, 'rot', $translations));
    }

    public function testTranslateConfiguratorOptionWithoutStruct()
    {
        $optionTranslations = [
            2 => 'red',
            3 => 'rood',
        ];

        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn([2, 3]);
        $this->translationGateway->expects($this->any())->method('getConfiguratorOptionTranslations')->with(15, [2, 3])->willReturn($optionTranslations);

        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');

        $enShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $enShop->expects($this->any())->method('getLocale')->willReturn(null);

        $nlShop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $nlShop->expects($this->any())->method('getLocale')->willReturn($nlLocale);

        $this->shopRepository->expects($this->at(0))->method('find')->with(2)->willReturn($enShop);
        $this->shopRepository->expects($this->at(1))->method('find')->with(3)->willReturn($nlShop);

        $expected = [
            'gb' => [],
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                    'variantValues' => [
                        'rot' => 'rood'
                    ],
                ]
            )
        ];

        $translations = [
            'gb' => [],
            'nl' => new Translation(
                [
                    'title' => 'shopware Connect Local Product NL',
                    'shortDescription' => 'shopware Connect Local Product short description NL',
                    'longDescription' => 'shopware Connect Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35/shId/3',
                ]
            )
        ];

        $this->assertEquals($expected, $this->productTranslator->translateConfiguratorOption(15, 'rot', $translations));
    }

    public function testValidate()
    {
        $translation = new Translation([
            'title' => 'shopware Connect Local Product EN',
            'shortDescription' => 'shopware Connect Local Product short description EN',
            'longDescription' => 'shopware Connect Local Product long description EN',
            'url' => $this->getProductBaseUrl() . '35/shId/2',
            'variantLabels' => [
                'größe' => 'size',
                'farbe' => 'color',
            ],
            'variantValues' => [
                '52' => 'XL',
                'blau' => 'blue',
            ],
        ]);

        $this->assertTrue($this->productTranslator->validate($translation, 2));
    }

    public function testValidateMissingTitle()
    {
        $translation = new Translation([
            'shortDescription' => 'shopware Connect Local Product short description EN',
            'longDescription' => 'shopware Connect Local Product long description EN',
            'url' => $this->getProductBaseUrl() . '35/shId/2',
            'variantLabels' => [
                'größe' => 'size',
                'farbe' => 'color',
            ],
            'variantValues' => [
                '52' => 'XL',
                'blau' => 'blue',
            ],
        ]);

        $this->assertTrue($this->productTranslator->validate($translation, 2));
    }

    /**
     * @expectedException \Exception
     */
    public function testValidateWrongVariantLabels()
    {
        $translation = new Translation([
            'title' => 'shopware Connect Local Product EN',
            'shortDescription' => 'shopware Connect Local Product short description EN',
            'longDescription' => 'shopware Connect Local Product long description EN',
            'url' => $this->getProductBaseUrl() . '35/shId/2',
            'variantLabels' => [
                'farbe' => 'color',
            ],
            'variantValues' => [
                '52' => 'XL',
                'blau' => 'blue',
            ],
        ]);

        $this->assertTrue($this->productTranslator->validate($translation, 2));
    }

    /**
     * @expectedException \Exception
     */
    public function testValidateWrongVariantValues()
    {
        $translation = new Translation([
            'title' => 'shopware Connect Local Product EN',
            'shortDescription' => 'shopware Connect Local Product short description EN',
            'longDescription' => 'shopware Connect Local Product long description EN',
            'url' => $this->getProductBaseUrl() . '35/shId/2',
            'variantLabels' => [
                'größe' => 'size',
                'farbe' => 'color',
            ],
            'variantValues' => [
                'blau' => 'blue',
            ],
        ]);

        $this->assertTrue($this->productTranslator->validate($translation, 2));
    }

    public function getProductBaseUrl()
    {
        if (!Shopware()->Front()->Router()) {
            return null;
        }

        return Shopware()->Front()->Router()->assemble([
            'module' => 'frontend',
            'controller' => 'connect_product_gateway',
            'action' => 'product',
            'id' => '',
            'fullPath' => true
        ]);
    }
}
