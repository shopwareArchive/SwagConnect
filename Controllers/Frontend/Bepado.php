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
class Shopware_Controllers_Frontend_Bepado extends Enlight_Controller_Action
{
    /**
     * @return \Bepado\SDK\SDK
     */
    public function getSDK()
    {
        return Shopware()->Bootstrap()->getResource('BepadoSDK');
    }

    public function searchAction()
    {
        $sdk = $this->getSDK();
        $request = $this->Request();

        $search = new \Bepado\SDK\Struct\Search(array(
            'query' => $request->get('query'),
            'offset' => $request->get('offset', 0),
            'limit' => min($request->get('limit', 12), 64),
            'vendor' => $request->get('vendor'),
            'priceFrom' => (float)$request->get('priceFrom'),
            'priceTo' => (float)$request->get('priceTo'),
        ));
        $searchResult = $sdk->search($search);
        $this->View()->assign('searchQuery', $request->get('query'));
        $this->View()->assign('searchResult', $searchResult);
    }
}