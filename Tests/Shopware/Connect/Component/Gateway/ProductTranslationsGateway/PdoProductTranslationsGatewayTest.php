<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component\Gateway\ProductTranslationsGateway;

use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class PdoProductTranslationsGatewayTest extends ConnectTestHelper
{
    /**
     * @var \ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway
     */
    private $gateway;

    private $mockDbStatement;

    private $mockDbAdapter;

    public function setUp()
    {
        parent::setUp();

        $this->mockDbStatement = $this->getMockBuilder('Zend_Db_Statement_Pdo')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockDbAdapter = $this->getMockBuilder('Enlight_Components_Db_Adapter_Pdo_Mysql')
            ->disableOriginalConstructor()
            ->getMock();

        $this->gateway = new \ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway($this->mockDbAdapter);
    }

    public function testGetSingleTranslation()
    {
        $this->mockDbStatement->expects($this->any())->method('fetchColumn')->willReturn('a:4:{s:10:"txtArtikel";s:30:"shopware Connect local article";s:19:"txtshortdescription";s:48:"shopware Connect local article short description";s:19:"txtlangbeschreibung";s:47:"shopware Connect local article long description";s:39:"__attribute_connect_product_description";s:50:"shopware Connect local article connect description";}');

        $sql = 'SELECT objectdata
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage = ?
        ';
        $queryParams = ['article', 105, 3];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $expected = [
            'title' => 'shopware Connect local article',
            'shortDescription' => 'shopware Connect local article short description',
            'longDescription' => 'shopware Connect local article long description',
            'additionalDescription' => 'shopware Connect local article connect description',
        ];
        $this->assertEquals($expected, $this->gateway->getSingleTranslation(105, 3));
    }

    public function testGetSingleTranslationTitleOnly()
    {
        $this->mockDbStatement->expects($this->any())->method('fetchColumn')->willReturn('a:1:{s:10:"txtArtikel";s:30:"shopware Connect local article";}');

        $sql = 'SELECT objectdata
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage = ?
        ';
        $queryParams = ['article', 105, 3];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $expected = [
            'title' => 'shopware Connect local article',
            'shortDescription' => '',
            'longDescription' => '',
            'additionalDescription' => '',
        ];
        $this->assertEquals($expected, $this->gateway->getSingleTranslation(105, 3));
    }

    public function testNotFoundTranslation()
    {
        $this->mockDbStatement->expects($this->any())->method('fetchColumn')->willReturn(false);

        $sql = 'SELECT objectdata
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage = ?
        ';
        $queryParams = ['article', 111, 3];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $this->assertNull($this->gateway->getSingleTranslation(111, 3));
    }

    public function testGetTranslations()
    {
        $this->mockDbStatement->expects($this->any())
            ->method('fetchAll')
            ->willReturn([
                0 => [
                    'objectdata' => 'a:4:{s:10:"txtArtikel";s:30:"shopware Connect local article";s:19:"txtshortdescription";s:48:"shopware Connect local article short description";s:19:"txtlangbeschreibung";s:47:"shopware Connect local article long description";s:39:"__attribute_connect_product_description";s:50:"shopware Connect local article connect description";}',
                    'objectlanguage' => 2,
                ],
                1 => [
                    'objectdata' => 'a:4:{s:10:"txtArtikel";s:33:"shopware Connect local article EN";s:19:"txtshortdescription";s:51:"shopware Connect local article short description EN";s:19:"txtlangbeschreibung";s:50:"shopware Connect local article long description EN";s:39:"__attribute_connect_product_description";s:53:"shopware Connect local article connect description EN";}',
                    'objectlanguage' => 3,
                ],
            ]);

        $sql = 'SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN (?,?)
        ';

        $queryParams = ['article', 103, 2, 3];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $expected = [
            2 => [
                'title' => 'shopware Connect local article',
                'shortDescription' => 'shopware Connect local article short description',
                'longDescription' => 'shopware Connect local article long description',
                'additionalDescription' => 'shopware Connect local article connect description',
            ],
            3 => [
                'title' => 'shopware Connect local article EN',
                'shortDescription' => 'shopware Connect local article short description EN',
                'longDescription' => 'shopware Connect local article long description EN',
                'additionalDescription' => 'shopware Connect local article connect description EN',
            ],
        ];

        $this->assertEquals($expected, $this->gateway->getTranslations(103, [2,3]));
    }

    public function testGetTranslationsOnlyTitle()
    {
        $this->mockDbStatement->expects($this->any())
            ->method('fetchAll')
            ->willReturn([
                0 => [
                    'objectdata' => 'a:1:{s:10:"txtArtikel";s:30:"shopware Connect local article";}',
                    'objectlanguage' => 2,
                ],
                1 => [
                    'objectdata' => 'a:1:{s:10:"txtArtikel";s:33:"shopware Connect local article EN";}',
                    'objectlanguage' => 3,
                ],
            ]);

        $sql = 'SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN (?,?)
        ';

        $queryParams = ['article', 103, 2, 3];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $expected = [
            2 => [
                'title' => 'shopware Connect local article',
                'shortDescription' => '',
                'longDescription' => '',
                'additionalDescription' => '',
            ],
            3 => [
                'title' => 'shopware Connect local article EN',
                'shortDescription' => '',
                'longDescription' => '',
                'additionalDescription' => '',
            ],
        ];

        $this->assertEquals($expected, $this->gateway->getTranslations(103, [2,3]));
    }

    public function testGetInvalidArticleTranslations()
    {
        $this->mockDbStatement->expects($this->any())
            ->method('fetchAll')
            ->willReturn([]);

        $sql = 'SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN (?,?)
        ';

        $queryParams = ['article', 111, 2, 3];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $this->assertEquals([], $this->gateway->getTranslations(111, [2,3]));
    }

    public function testGetConfiguratorOptionTranslationsWithoutShopIds()
    {
        $this->assertEmpty($this->gateway->getConfiguratorOptionTranslations(15, []));
    }

    public function testGetConfiguratorOptionTranslationsWithoutSerializedData()
    {
        $this->mockDbStatement->expects($this->any())
            ->method('fetchAll')
            ->willReturn([
                0 => [
                    'objectdata' => 'a:0:{}',
                    'objectlanguage' => 2,
                ],
                1 => [
                    'objectdata' => 'a:0:{}',
                    'objectlanguage' => 3,
                ],
            ]);

        $sql = 'SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN (2,3)
        ';

        $queryParams = ['configuratoroption', 15];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $expected = [];

        $this->assertEquals($expected, $this->gateway->getConfiguratorOptionTranslations(15, [2,3]));
    }

    public function testGetConfiguratorOptionTranslations()
    {
        $this->mockDbStatement->expects($this->any())
            ->method('fetchAll')
            ->willReturn([
                0 => [
                    'objectdata' => 'a:1:{s:4:"name";s:3:"red";}',
                    'objectlanguage' => 2,
                ],
                1 => [
                    'objectdata' => 'a:1:{s:4:"name";s:3:"rot";}',
                    'objectlanguage' => 3,
                ],
            ]);

        $sql = 'SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN (2,3)
        ';

        $queryParams = ['configuratoroption', 15];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $expected = [
            2 => 'red',
            3 => 'rot',
        ];

        $this->assertEquals($expected, $this->gateway->getConfiguratorOptionTranslations(15, [2,3]));
    }

    public function testGetMissingSingleConfiguratorOptionTranslation()
    {
        $this->mockDbStatement->expects($this->any())
            ->method('fetchColumn')
            ->willReturn(false);

        $sql = 'SELECT objectdata
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage = ?
        ';

        $queryParams = ['configuratoroption', 15, 2];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $this->assertNull($this->gateway->getConfiguratorOptionTranslation(15, 2));
    }

    public function testGetSingleConfiguratorOptionTranslation()
    {
        $this->mockDbStatement->expects($this->any())
            ->method('fetchColumn')
            ->willReturn('a:1:{s:4:"name";s:3:"rot";}');

        $sql = 'SELECT objectdata
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage = ?
        ';

        $queryParams = ['configuratoroption', 15, 2];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));


        $this->assertEquals('rot', $this->gateway->getConfiguratorOptionTranslation(15, 2));
    }

    public function testGetConfiguratorGroupTranslationsWithoutShopIds()
    {
        $this->assertEmpty($this->gateway->getConfiguratorGroupTranslations(15, []));
    }

    public function testGetConfiguratorGroupTranslationsWithoutSerializedData()
    {
        $this->mockDbStatement->expects($this->any())
            ->method('fetchAll')
            ->willReturn([
                0 => [
                    'objectdata' => 'a:0:{}',
                    'objectlanguage' => 2,
                ],
                1 => [
                    'objectdata' => 'a:0:{}',
                    'objectlanguage' => 3,
                ],
            ]);

        $sql = 'SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN (2,3)
        ';

        $queryParams = ['configuratorgroup', 15];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $expected = [];

        $this->assertEquals($expected, $this->gateway->getConfiguratorGroupTranslations(15, [2,3]));
    }

    public function testGetConfiguratorGroupTranslations()
    {
        $this->mockDbStatement->expects($this->any())
            ->method('fetchAll')
            ->willReturn([
                0 => [
                    'objectdata' => 'a:1:{s:4:"name";s:5:"color";}',
                    'objectlanguage' => 2,
                ],
                1 => [
                    'objectdata' => 'a:1:{s:4:"name";s:5:"farbe";}',
                    'objectlanguage' => 3,
                ],
            ]);

        $sql = 'SELECT objectdata, objectlanguage
                FROM s_core_translations
                WHERE objecttype = ? AND objectkey = ? AND objectlanguage IN (2,3)
        ';

        $queryParams = ['configuratorgroup', 15];
        $this->mockDbAdapter->expects($this->any())->method('executeQuery')->with($sql, $queryParams)->will($this->returnValue($this->mockDbStatement));

        $expected = [
            2 => 'color',
            3 => 'farbe',
        ];

        $this->assertEquals($expected, $this->gateway->getConfiguratorGroupTranslations(15, [2,3]));
    }

    public function testAddGroupTranslation()
    {
        $sql = '
                INSERT IGNORE INTO `s_core_translations`
                (`objecttype`, `objectdata`, `objectkey`, `objectlanguage`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE `objectdata`=VALUES(objectdata);
                ';
        $queryParams = ['configuratorgroup', serialize(['name' => 'Color']), 14, 2];

        $this->mockDbAdapter->expects($this->once())
            ->method('query')
            ->with($sql, $queryParams);

        $this->gateway->addGroupTranslation('Color', 14, 2);
    }

    public function testAddOptionTranslation()
    {
        $sql = '
                INSERT IGNORE INTO `s_core_translations`
                (`objecttype`, `objectdata`, `objectkey`, `objectlanguage`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE `objectdata`=VALUES(objectdata);
                ';
        $queryParams = ['configuratoroption', serialize(['name' => 'Red']), 42, 2];

        $this->mockDbAdapter->expects($this->once())
            ->method('query')
            ->with($sql, $queryParams);

        $this->gateway->addOptionTranslation('Red', 42, 2);
    }

    public function testAddArticleTranslation()
    {
        $translation = new \Shopware\Connect\Struct\Translation([
            'title' => 'shopware Connect remote article EN',
            'longDescription' => 'Long description EN',
            'shortDescription' => 'Short description EN',
            'additionalDescription' => 'Connect description EN',
        ]);
        $objectData = [
            'txtArtikel' => $translation->title,
            'txtlangbeschreibung' => $translation->longDescription,
            'txtshortdescription' => $translation->shortDescription,
            PdoProductTranslationsGateway::CONNECT_DESCRIPTION => $translation->additionalDescription,
        ];
        $sql = '
                INSERT IGNORE INTO `s_core_translations`
                (`objecttype`, `objectdata`, `objectkey`, `objectlanguage`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE `objectdata`=VALUES(objectdata);
                ';
        $queryParams = ['article', serialize($objectData), 103, 2];

        $this->mockDbAdapter->expects($this->once())
            ->method('query')
            ->with($sql, $queryParams);

        $this->gateway->addArticleTranslation($translation, 103, 2);
    }
}
