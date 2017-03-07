<?php

use ShopwarePlugins\Connect\Components\ImageImport;
use Shopware\Models\Article\Price;

class Shopware_Controllers_Backend_LastChanges extends \Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var \ShopwarePlugins\Connect\Components\ConnectFactory
     */
    private $factory;

    /**
     * Get a list of products with remote changes which have not been applied
     */
    public function getChangedProductsAction()
    {
        $connectAttributeRepository = $this->getModelManager()->getRepository('\Shopware\CustomModels\Connect\Attribute');
        $query = $connectAttributeRepository->getChangedProducts(
            $this->Request()->getParam('start', 0),
            $this->Request()->getParam('limit', 20),
            $this->Request()->getParam('sort', [])

        )->getQuery();

        $total = $this->getModelManager()->getQueryCount($query);
        $data = $query->getArrayResult();

        foreach ($data as $key => $record) {
            $data[$key]['images'] = implode('|', $this->getImageImport()->getImagesForDetail($record['id']));
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => $total
        ));
    }

    /**
     * Apply given changes to product
     *
     * @throws \RuntimeException
     */
    public function applyChangesAction()
    {
        $type = $this->Request()->getParam('type');
        $value = $this->Request()->getParam('value');
        $detailId = $this->Request()->getParam('detailId');

        /** @var \Shopware\Models\Article\Detail $detail */
        $detail = $this->getModelManager()->getRepository('\Shopware\Models\Article\Detail')->find($detailId);

        if (!$detail) {
            $this->View()->assign('success', false);
            $message = Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
                'changed_products/error/wrongArticleDetailId',
                'The product was not found',
                true
            );
            $this->View()->assign('message', $message);
            return;
        }

        /** @var \Shopware\Models\Article\Article $article */
        $article = $detail->getArticle();
        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($detail);

        $updateFlags = $this->getHelper()->getUpdateFlags();
        $updateFlagsByName = array_flip($updateFlags);
        $flag = $updateFlagsByName[$type];

        switch ($type) {
            case 'shortDescription':
                $article->setDescription($value);
                break;
            case 'longDescription':
                $article->setDescriptionLong($value);
                break;
            case 'additionalDescription':
                $detail->getAttribute()->setConnectProductDescription($value);
                break;
            case 'name':
                $article->setName($value);
                break;
            case 'image':
                $lastUpdate = json_decode($connectAttribute->getLastUpdate(), true);
                $this->getImageImport()->importImagesForArticle(
                    array_diff($lastUpdate['image'], $lastUpdate['variantImages']),
                    $article
                );
                $this->getImageImport()->importImagesForDetail(
                    $lastUpdate['variantImages'],
                    $detail
                );
                break;
            case 'price':
                $netPrice = $value / (1 + ($article->getTax()->getTax()/100));
                $customerGroup = $this->getHelper()->getDefaultCustomerGroup();

                $detail->getPrices()->clear();
                $price = new Price();
                $price->fromArray(array(
                    'from' => 1,
                    'price' => $netPrice,
                    'basePrice' => $connectAttribute->getPurchasePrice(),
                    'customerGroup' => $customerGroup,
                    'article' => $article
                ));
                $detail->setPrices(array($price));
                break;
        }

        if ($connectAttribute->getLastUpdateFlag() & $flag) {
            $connectAttribute->flipLastUpdateFlag($flag);
        }
        if ($type == 'image') {
            if ($connectAttribute->getLastUpdateFlag() & $updateFlagsByName['imageInitialImport']) {
                $connectAttribute->flipLastUpdateFlag($updateFlagsByName['imageInitialImport']);
            }
        }

        $this->getModelManager()->flush();
        $this->View()->assign('success', true);
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Helper
     */
    private function getHelper()
    {
        return $this->getConnectFactory()->getHelper();
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\ConnectFactory
     */
    private function getConnectFactory()
    {
        if ($this->factory === null) {
            $this->factory = new \ShopwarePlugins\Connect\Components\ConnectFactory();
        }

        return $this->factory;
    }

    /**
     * @return \Shopware\Components\Model\ModelManager
     */
    public function getModelManager()
    {
        return Shopware()->Models();
    }

    /**
     * @return ImageImport
     */
    public function getImageImport()
    {
        return new ImageImport(
            $this->getModelManager(),
            $this->getHelper(),
            $this->get('thumbnail_manager'),
            new \ShopwarePlugins\Connect\Components\Logger(Shopware()->Db())
        );
    }
}