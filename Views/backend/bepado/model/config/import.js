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
//{block name="backend/bepado/model/config/import"}
Ext.define('Shopware.apps.Bepado.model.config.Import', {
    extend: 'Ext.data.Model',

    fields: [
        //{block name="backend/bepado/model/config/import/fields"}{/block}
        { name: 'overwriteProductName', type: 'boolean' },
        { name: 'overwriteProductPrice', type: 'boolean' },
        { name: 'overwriteProductImage', type: 'boolean' },
        { name: 'overwriteProductShortDescription', type: 'boolean' },
        { name: 'overwriteProductLongDescription', type: 'boolean' }
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
            create: '{url controller="BepadoConfig" action="saveImport"}',
            update: '{url controller="BepadoConfig" action="saveImport"}',
            read: '{url controller="BepadoConfig" action="getImport"}'
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