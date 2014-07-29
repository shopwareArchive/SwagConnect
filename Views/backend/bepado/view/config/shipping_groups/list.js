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
        save: '{s name=config/shipping_groups/save}Save{/s}',
        add: '{s name=config/shipping_groups/add}Add{/s}'
    },

    initComponent: function() {
        var me = this;

        me.store = Ext.create('Shopware.apps.Bepado.store.shippingGroup.Groups').load();
        me.dockedItems = [
            me.getToolbar(),
            me.getButtons()
        ];

        me.columns = me.getColumns();

        me.rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToMoveEditor: 1,
            autoCancel: false
        });
        me.plugins = [ me.rowEditing ];

        me.groupingFeature = me.createGroupingFeature();
        me.features =  [ me.groupingFeature ];

        me.callParent(arguments);
    },

    getColumns: function () {
        var me = this;

        return [{
                header: me.snippets.contryHeader,
                dataIndex: 'country',
                flex: 1,
                editor: {
                    allowBlank: false
                }
            }, {
                header: me.snippets.deliveryTimeHeader,
                dataIndex: 'deliveryDays',
                flex: 1,
                editor: {
                    allowBlank: false
                }
            }, {
                header: me.snippets.priceHeader,
                dataIndex: 'price',
                flex: 1,
                editor: {
                    allowBlank: false
                }
            }, {
                header: me.snippets.zipPrefixHeader,
                dataIndex: 'zipPrefix',
                flex: 1,
                editor: {
                    allowBlank: false
                }
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
    },

    getToolbar: function() {
        var me = this;
        return {
            xtype: 'toolbar',
            enableOverflow: true,
            ui: 'shopware-ui',
            dock: 'top',
            border: false,
            items: me.getTopBar()
        };
    },

    getTopBar:function () {
        var me = this;
        var items = [];

        items.push({
            iconCls: 'sprite-plus-circle-frame',
            text: me.snippets.add,
            action: 'addGroup'
        });

        return items;
    },

    createGroupingFeature: function() {
        var me = this;

        return Ext.create('Ext.grid.feature.Grouping', {
            groupHeaderTpl: Ext.create('Ext.XTemplate',
                '<span>{ name:this.formatHeader }</span>',
                '<span>&nbsp;({ rows.length } Rules)</span>',
                {
                    formatHeader: function(groupName) {
                        return groupName;
                    }
                }
            )
        });
    },
});
//{/block}