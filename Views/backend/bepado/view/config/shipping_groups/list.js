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
//{namespace name=backend/bepado/view/main}

//{block name='backend/bepado/view/config/units/mapping'}
Ext.define('Shopware.apps.Bepado.view.config.shippingGroups.List', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.bepado-shipping-groups-list',

    snippets: {
        contryHeader: '{s name=config/shjipping_groups/country_header}Country{/s}',
        deliveryTimeHeader: '{s name=config/shipping_groups/delivery_time}Delivery time in days{/s}',
        priceHeader: '{s name=config/shjipping_groups/price}Price{/s}',
        zipPrefixHeader: '{s name=config/shjipping_groups/zip_prefix}Zip prefix{/s}',
        save: '{s name=config/shipping_groups/save}Save{/s}'
    },

    initComponent: function() {
        var me = this;

        me.store = [];
        me.dockedItems = [ me.getButtons() ];

        me.columns = me.createColumns();

        me.callParent(arguments);
    },

    createColumns: function () {
        var me = this;

        return [{
                header: me.snippets.contryHeader,
                dataIndex: 'country',
                flex: 1
            }, {
                header: me.snippets.deliveryTimeHeader,
                dataIndex: 'deliveryTime',
                flex: 1,
                editor: {
                    xtype: 'combo',
                    store: me.bepadoUnitsStore,
                    displayField: 'name',
                    valueField: 'key'
                },
                renderer: function (value) {
                    var index = me.bepadoUnitsStore.findExact('key', value);
                    if (index > -1) {
                        return me.bepadoUnitsStore.getAt(index).get('name');
                    }

                    return value;
                }
            }, {
                header: me.snippets.priceHeader,
                dataIndex: 'price',
                flex: 1
            }, {
                header: me.snippets.zipPrefixHeader,
                dataIndex: 'zipPrefix',
                flex: 1
            }];
    },

    getButtons: function() {
        var me = this;

        return {
            dock: 'bottom',
            xtype: 'toolbar',
            items: ['->', {
                text: me.snippets.save,
                cls: 'primary',
                action: 'save'
            }]
        };
    }
});
//{/block}