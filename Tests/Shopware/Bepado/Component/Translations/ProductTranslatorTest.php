<?php

namespace Tests\Shopware\Bepado\Component\Translations;

use Bepado\SDK\Struct\Translation;
use Shopware\Models\Shop\Locale;
use Tests\Shopware\Bepado\BepadoTestHelper;

class ProductTranslatorTest extends BepadoTestHelper
{
    /**
     * @var \Shopware\Bepado\Components\Translations\ProductTranslatorInterface
     */
    private $productTranslator;

    private $configComponent;

    private $translationGateway;

    private $modelManager;

    private $shopRepository;

    private $localeRepository;

    public function setUp()
    {
        $this->configComponent = $this->getMockBuilder('\\Shopware\\Bepado\\Components\\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationGateway = $this->getMockBuilder('\\Shopware\\Bepado\\Components\\Gateway\\ProductTranslationsGateway\\PdoProductTranslationsGateway')
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

        $this->productTranslator = new \Shopware\Bepado\Components\Translations\ProductTranslator(
            $this->configComponent,
            $this->translationGateway,
            $this->modelManager,
            $this->getProductBaseUrl()
        );
    }

    public function testTranslate()
    {
        $translations = array(
            2 => array(
                'title' => 'Bepado Local Product EN',
                'shortDescription' => 'Bepado Local Product short description EN',
                'longDescription' => 'Bepado Local Product long description EN',
                'url' => $this->getProductBaseUrl() . '35&shId=2',
            ),
            176 => array(
                'title' => 'Bepado Local Product NL',
                'shortDescription' => 'Bepado Local Product short description NL',
                'longDescription' => 'Bepado Local Product long description NL',
                'url' => $this->getProductBaseUrl() . '35&shId=3',
            ),
        );
        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn(array(2, 176));
        $this->translationGateway->expects($this->any())->method('getTranslations')->with(108, array(2, 176))->willReturn($translations);

        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Locale')->willReturn($this->localeRepository);
        $this->modelManager->expects($this->at(1))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $enLocale = new Locale();
        $enLocale->setLocale('en_GB');
        $this->localeRepository->expects($this->at(0))->method('find')->with(2)->willReturn($enLocale);
        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');
        $this->localeRepository->expects($this->at(1))->method('find')->with(176)->willReturn($nlLocale);

        $shop = $this->getMockBuilder('\\Shopware\\Models\\Shop\\Shop')
            ->disableOriginalConstructor()
            ->getMock();
        $shop->expects($this->at(0))->method('getId')->willReturn(2);
        $shop->expects($this->at(1))->method('getId')->willReturn(3);
        $this->shopRepository->expects($this->any())->method('findOneBy')->willReturn($shop);

        $expected = array(
            'en' => new Translation(
                array(
                    'title' => 'Bepado Local Product EN',
                    'shortDescription' => 'Bepado Local Product short description EN',
                    'longDescription' => 'Bepado Local Product long description EN',
                    'url' => $this->getProductBaseUrl() . '35&shId=2',
                )
            ),
            'nl' => new Translation(
                array(
                    'title' => 'Bepado Local Product NL',
                    'shortDescription' => 'Bepado Local Product short description NL',
                    'longDescription' => 'Bepado Local Product long description NL',
                    'url' => $this->getProductBaseUrl() . '35&shId=3',
                )
            )
        );

        $this->assertEquals($expected, $this->productTranslator->translate(108, 35));
    }

    public function testTranslateWhenLocaleNotFound()
    {
        $translations = array(
            2 => array(
                'title' => 'Bepado Local Product EN',
                'shortDescription' => 'Bepado Local Product short description EN',
                'longDescription' => 'Bepado Local Product long description EN',
                'url' => $this->getProductBaseUrl() . '35&shId=2',
            ),
            176 => array(
                'title' => 'Bepado Local Product NL',
                'shortDescription' => 'Bepado Local Product short description NL',
                'longDescription' => 'Bepado Local Product long description NL',
                'url' => $this->getProductBaseUrl() . '35&shId=3',
            ),
        );
        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn(array(2, 176));
        $this->translationGateway->expects($this->any())->method('getTranslations')->with(108, array(2, 176))->willReturn($translations);
        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Locale')->willReturn($this->localeRepository);
        $this->localeRepository->expects($this->any())->method('find')->willReturn(null);

        $this->assertEmpty($this->productTranslator->translate(108, 35));
    }

    public function testTranslateWhenShopNotFound()
    {
        $translations = array(
            2 => array(
                'title' => 'Bepado Local Product EN',
                'shortDescription' => 'Bepado Local Product short description EN',
                'longDescription' => 'Bepado Local Product long description EN',
                'url' => $this->getProductBaseUrl() . '35&shId=2',
            ),
            176 => array(
                'title' => 'Bepado Local Product NL',
                'shortDescription' => 'Bepado Local Product short description NL',
                'longDescription' => 'Bepado Local Product long description NL',
                'url' => $this->getProductBaseUrl() . '35&shId=3',
            ),
        );
        $this->configComponent->expects($this->any())->method('getConfig')->with('exportLanguages')->willReturn(array(2, 176));
        $this->translationGateway->expects($this->any())->method('getTranslations')->with(108, array(2, 176))->willReturn($translations);
        $this->modelManager->expects($this->at(0))->method('getRepository')->with('Shopware\Models\Shop\Locale')->willReturn($this->localeRepository);
        $this->modelManager->expects($this->at(1))->method('getRepository')->with('Shopware\Models\Shop\Shop')->willReturn($this->shopRepository);

        $enLocale = new Locale();
        $enLocale->setLocale('en_GB');
        $this->localeRepository->expects($this->at(0))->method('find')->with(2)->willReturn($enLocale);
        $nlLocale = new Locale();
        $nlLocale->setLocale('nl_NL');
        $this->localeRepository->expects($this->at(1))->method('find')->with(176)->willReturn($nlLocale);

        $this->shopRepository->expects($this->any())->method('findOneBy')->willReturn(null);

        $this->assertEmpty($this->productTranslator->translate(108, 35));
    }


    public function getProductBaseUrl()
    {
        if (!Shopware()->Front()->Router()) {
            return null;
        }

        return Shopware()->Front()->Router()->assemble(array(
            'module' => 'frontend',
            'controller' => 'bepado_product_gateway',
            'action' => 'product',
            'id' => '',
            'fullPath' => true
        ));
    }
}
 