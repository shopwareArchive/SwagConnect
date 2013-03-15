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
}