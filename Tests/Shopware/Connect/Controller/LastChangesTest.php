<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

class LastChangesTest extends ConnectTestHelper
{
    private $manager;

    private $db;

    public function setUp()
    {
        parent::setUp();

        // disable auth and acl
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();

        $this->manager = Shopware()->Models();
        $this->db = Shopware()->Db();
    }

    public function testApplyShortDescriptionChange()
    {
        $article = $this->createArticle();

        $this->Request()
            ->setMethod('POST')
            ->setPost('type', 'shortDescription')
            ->setPost('value', 'changed short description')
            ->setPost('detailId', $article->getMainDetail()->getId());
        $this->dispatch('backend/LastChanges/applyChanges');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $changedArticle = $this->manager->getRepository('Shopware\Models\Article\Article')->find($article->getId());
        $this->assertEquals($changedArticle->getDescription(), 'changed short description');
    }

    public function testApplyLongDescriptionChange()
    {
        $article = $this->createArticle();

        $this->Request()
            ->setMethod('POST')
            ->setPost('type', 'longDescription')
            ->setPost('value', 'changed long description')
            ->setPost('detailId', $article->getMainDetail()->getId());
        $this->dispatch('backend/LastChanges/applyChanges');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $changedArticle = $this->manager->getRepository('Shopware\Models\Article\Article')->find($article->getId());
        $this->assertEquals($changedArticle->getDescriptionLong(), 'changed long description');
    }

    public function testApplyAdditionalDescriptionChange()
    {
        $article = $this->createArticle();

        $this->Request()
            ->setMethod('POST')
            ->setPost('type', 'additionalDescription')
            ->setPost('value', 'changed additional description')
            ->setPost('detailId', $article->getMainDetail()->getId());
        $this->dispatch('backend/LastChanges/applyChanges');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $changedArticle = $this->manager->getRepository('Shopware\Models\Article\Article')->find($article->getId());
        $this->assertEquals(
            $changedArticle->getMainDetail()->getAttribute()->getConnectProductDescription(),
            'changed additional description'
        );
    }

    public function testApplyNameChange()
    {
        $article = $this->createArticle();

        $this->Request()
            ->setMethod('POST')
            ->setPost('type', 'name')
            ->setPost('value', 'changed article name')
            ->setPost('detailId', $article->getMainDetail()->getId());
        $this->dispatch('backend/LastChanges/applyChanges');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $changedArticle = $this->manager->getRepository('Shopware\Models\Article\Article')->find($article->getId());
        $this->assertEquals($changedArticle->getName(), 'changed article name');
    }

    public function testApplyImageChange()
    {
        $article = $this->createArticle();
        // load article via doctrine
        $article = $this->manager->getRepository('Shopware\Models\Article\Article')->find($article->getId());
        $this->assertEquals(0, $article->getImages()->count());
        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($article);
        $connectAttribute->setLastUpdateFlag(16);
        $lastUpdate = [
            'image' => [ConnectTestHelper::IMAGE_PROVIDER_URL],
            'variantImages' => [],
        ];
        $connectAttribute->setLastUpdate(json_encode($lastUpdate));
        $this->manager->persist($connectAttribute);
        $this->manager->flush();

        $this->Request()
            ->setMethod('POST')
            ->setPost('type', 'image')
            ->setPost('detailId', $article->getMainDetail()->getId());
        $this->dispatch('backend/LastChanges/applyChanges');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $changedArticle = $this->manager->getRepository('Shopware\Models\Article\Article')->find($article->getId());
        $this->assertEquals(1, $changedArticle->getImages()->count());
        $this->assertEquals(0, $connectAttribute->getLastUpdateFlag());
    }

    public function testApplyPriceChange()
    {
        $article = $this->createArticle();

        $this->Request()
            ->setMethod('POST')
            ->setPost('type', 'price')
            ->setPost('value', 139.99)
            ->setPost('detailId', $article->getMainDetail()->getId());
        $this->dispatch('backend/LastChanges/applyChanges');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $changedArticle = $this->manager->getRepository('Shopware\Models\Article\Article')->find($article->getId());
        $this->assertEquals(117.63865546218489, $changedArticle->getMainDetail()->getPrices()->first()->getPrice());
    }

    public function testApplyOneOfMultipleChanges()
    {
        $article = $this->createArticle();
        $article = $this->manager->getRepository('Shopware\Models\Article\Article')->find($article->getId());

        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($article);
        $connectAttribute->setLastUpdateFlag(6);
        $lastUpdate = [
            'shortDescription' => 'foo bar',
            'longDescription' => 'bar foo',
        ];
        $connectAttribute->setLastUpdate(json_encode($lastUpdate));
        $this->manager->persist($connectAttribute);
        $this->manager->flush();

        $this->Request()
            ->setMethod('POST')
            ->setPost('type', 'shortDescription')
            ->setPost('value', 'foo bar')
            ->setPost('detailId', $article->getMainDetail()->getId());
        $this->dispatch('backend/LastChanges/applyChanges');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $changedArticle = $this->manager->getRepository('Shopware\Models\Article\Article')->find($article->getId());
        $this->assertEquals('foo bar', $changedArticle->getDescription());
        $this->assertEquals(4, $connectAttribute->getLastUpdateFlag());
    }

    public function testDetailNotFound()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('type', 'shortDescription')
            ->setPost('value', 'foo bar')
            ->setPost('detailId', 99999999999);
        $this->dispatch('backend/LastChanges/applyChanges');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertFalse($this->View()->success);
        $this->assertEquals('Das Produkt wurde nicht gefunden', $this->View()->message);
    }

    private function createArticle()
    {
        $minimalTestArticle = [
            'name' => 'Glas -Teetasse 0,25l',
            'active' => true,
            'tax' => 19,
            'supplier' => 'Teapavilion',
            'mainDetail' => [
                'number' => uniqid('lct'),
            ],
        ];

        $articleResource = \Shopware\Components\Api\Manager::getResource('article');
        /** @var \Shopware\Models\Article\Article $article */
        $article = $articleResource->create($minimalTestArticle);

        $this->db->insert(
            's_articles_prices',
            [
                'pricegroup' => 'EK',
                'from' => 1,
                'to' => 5,
                'price' => 123.99,
                'articleID' => $article->getId(),
                'articledetailsID' => $article->getMainDetail()->getId(),
                'pseudoprice' => 0
            ]
        );

        $this->db->insert(
            's_articles_prices',
            [
                'pricegroup' => 'EK',
                'from' => 6,
                'to' => 'beliebig',
                'price' => 113.99,
                'articleID' => $article->getId(),
                'articledetailsID' => $article->getMainDetail()->getId(),
                'pseudoprice' => 0
            ]
        );

        return $article;
    }

    public function tearDown()
    {
        $query = $query = Shopware()->Db()->query('
                SELECT articleID
                FROM s_articles_details
                WHERE ordernumber LIKE "lct%" GROUP BY articleID');
        $articleIds = array_map(function ($item) {
            return $item['articleID'];
        }, $query->fetchAll());

        if (empty($articleIds)) {
            return;
        }

        $this->db->exec('DELETE FROM s_articles WHERE id IN (' . implode(', ', $articleIds) . ')');
        $this->db->exec('DELETE FROM s_articles_details WHERE ordernumber LIKE "lct%"');
        $this->db->exec('DELETE FROM s_articles_prices WHERE articleID IN (' . implode(', ', $articleIds) . ')');
        $this->db->exec('DELETE FROM s_plugin_connect_items WHERE article_id IN (' . implode(', ', $articleIds) . ')');
    }
}
