/**
 * Shopware 4
 * Copyright © shopware AG
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
 * Shopware SwagBepado Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
//{block name="backend/bepado/model/config/general"}
Ext.define('Shopware.apps.Bepado.model.config.General', {
    extend: 'Ext.data.Model',

    idProperty: 'shopId',

    fields: [
        //{block name="backend/bepado/model/config/general/fields"}{/block}
        { name: 'shopId', type: 'int', useNull: true },
        { name: 'isDefaultShop', type: 'boolean' },
        { name: 'apiKey', type: 'string' },
        { name: 'bepadoAttribute', type: 'int' },
        { name: 'bepadoDebugHost', type: 'string' },
        { name: 'logRequest', type: 'string' },
        { name: 'detailShopInfo', type: 'string' },
        { name: 'detailProductNoIndex', type: 'string' },
        { name: 'checkoutShopInfo', type: 'string' },
        { name: 'shippingCostsPage', type: 'int', useNull: true },
        { name: 'shippingCostsPageName', type: 'string' },
        { name: 'exportDomain', type: 'string' },
        { name: 'createCategoriesAutomatically', type: 'string' },
        { name: 'activateProductsAutomatically', type: 'string' },
        { name: 'createUnitsAutomatically', type: 'string' },
        { name: 'showShippingCostsSeparately', type: 'string' },
        { name: 'hasSsl', type: 'string' }
    ],

    proxy: {
        /**
         * Set proxy type to ajax
         * @string
         */
        type: 'ajax',

        /**
         * Configure the url mapping for the different
         * store operations based on
         * @object
         */
        api: {
            create: '{url controller="BepadoConfig" action="saveGeneral"}',
            update: '{url controller="BepadoConfig" action="saveGeneral"}',
            read: '{url controller="BepadoConfig" action="getGeneral"}'
        },

        /**
         * Configure the data reader
         * @object
         */
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        },

        /**
         * Configure the data writer
         * @object
         */
        writer: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}