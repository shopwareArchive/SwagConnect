<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
class Shopware_Controllers_Backend_Bepado extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @return Shopware\Components\Model\ModelManager
     */
    public function getModelManager()
    {
        return Shopware()->Models();
    }

    /**
     * @return \Bepado\SDK\SDK
     */
    public function getSDK()
    {
        return Shopware()->Bootstrap()->getResource('BepadoSDK');
    }

    public function categoryListAction()
    {
        $sdk = $this->getSDK();
        $categories = $sdk->getCategories();

        $this->View()->assign(array(
            'success' => true,
            'data' => $categories
        ));
    }

    public function addAllProductsAction()
    {
        $sdk = $this->getSDK();
        $manager = Shopware()->Models();
        $repository = $manager->getRepository(
            'Shopware\Models\Article\Article'
        );

        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.mainDetail', 'd');
        $builder->join('a.supplier', 's');
        $builder->join('d.prices', 'p', 'with', "p.from = 1 AND p.customerGroupKey = 'EK'");
        $builder->join('a.tax', 't');
        $builder->select(array(
            'a.id as sourceId',
            'd.ean',
            'a.name as title',
            'a.description as shortDescription',
            'a.descriptionLong as longDescription',
            's.name as vendor',
            't.tax / 100 as vat',
            'p.basePrice as price',
            'p.price * (100 + t.tax) / 100 as purchasePrice',
            //'"EUR" as currency',
            'd.shippingFree as freeDelivery',
            'd.releaseDate as deliveryDate',
            'd.inStock as availability',
            //'images = array()',
            //'categories = array()',
            //'attributes = array()'
        ));
        $builder->where('d.inStock > 0');
        $query = $builder->getQuery();
        $result = $query->getArrayResult();
        foreach($result as $productData) {
            if(isset($productData['releaseDate'])) {
                $productData['releaseDate'] = $productData['releaseDate']->getTimestamp();
            }
            if(empty($productData['price'])) {
                $productData['price'] = $productData['purchasePrice'];
            }
            $productData['categories'] = array(
                '/auto_motorrad'
            );
            $product = new \Bepado\SDK\Struct\Product(
                $productData
            );
            $sdk->recordInsert($product);
        }
    }

    public function searchProductAction()
    {
        $sdk = $this->getSDK();
        $search = new Bepado\SDK\Struct\Search(array(
            'query' => $this->Request()->getParam('query')
        ));
        $result = $sdk->search($search);
        $this->View()->assign((array)$result);
    }

    public function createProductAction()
    {
        $sdk = $this->getSDK();
        $product =  new \Bepado\SDK\Struct\Product();
        $sdk->recordInsert($product);
    }

    public function updateProductAction()
    {
        $sdk = $this->getSDK();
        $product =  new \Bepado\SDK\Struct\Product();
        $sdk->recordUpdate($product);
    }

    public function deleteProductAction()
    {
        $sdk = $this->getSDK();
        $id = $this->Request()->getParam('id');
        $sdk->recordDelete($id);
    }

    public function recreateChangesFeedAction()
    {
        $sdk = $this->getSDK();
        $sdk->recreateChangesFeed();
    }
}