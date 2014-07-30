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
    id: 'bepado-shipping-groups-list',

    snippets: {
        countryHeader: '{s name=config/shipping_groups/country_header}Country{/s}',
        deliveryTimeHeader: '{s name=config/shipping_groups/delivery_time}Delivery time in days{/s}',
        priceHeader: '{s name=config/shipping_groups/price}Price{/s}',
        zipPrefixHeader: '{s name=config/shipping_groups/zip_prefix}Zip prefix{/s}',
        save: '{s name=config/shipping_groups/save}Save{/s}',
        addGroup: '{s name=config/shipping_groups/add_group}Add group{/s}',
        addRule: '{s name=config/shipping_groups/add_rule}Add rule{/s}'
    },

    initComponent: function() {
        var me = this;

        me.countryStore = Ext.create('Shopware.apps.Base.store.Country').load();
        me.store = Ext.create('Shopware.apps.Bepado.store.shippingGroup.Rules').load();
        me.dockedItems = [
            me.getToolbar(),
            me.getBottomToolbar()
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
        var actionColumItems = [];
        actionColumItems.push({
            iconCls:'sprite-minus-circle-frame',
            action:'delete',
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                me.fireEvent('deleteShippingRule', record);
            }
        });

        return [{
                header: me.snippets.countryHeader,
                dataIndex: 'country',
                flex: 1,
                renderer: function(value) {
                  var recordIndex = me.countryStore.findExact('iso', value);
                    if (recordIndex === -1) {
                        return '';
                    }

                    var model = me.countryStore.getAt(recordIndex);
                    return model.get('name');
                },
                editor: {
                    xtype: 'combobox',
                    store: me.countryStore,
                    displayField: 'name',
                    valueField: 'iso',
                    allowBlank: false
                }
            }, {
                header: me.snippets.deliveryTimeHeader,
                dataIndex: 'deliveryDays',
                flex: 1,
                editor: {
                    xtype: 'numberfield',
                    maxValue: 99,
                    minValue: 1,
                    step: 1,
                    allowBlank: false
                }
            }, {
                header: me.snippets.priceHeader,
                dataIndex: 'price',
                flex: 1,
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false,
                    forcePrecision: true,
                    minValue: 0.00,
                    step: 0.01
                }
            }, {
                header: me.snippets.zipPrefixHeader,
                dataIndex: 'zipPrefix',
                flex: 1,
                editor: {
                    allowBlank: false
                }
            }, {
            /**
             * Special column type which provides
             * clickable icons in each row
             */
            xtype: 'actioncolumn',
            width: 26 * actionColumItems.length,
            items: actionColumItems
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
            text: me.snippets.addGroup,
            action: 'addGroup'
        });

        items.push({
            iconCls: 'sprite-plus-circle-frame',
            text: me.snippets.addRule,
            action: 'addRule'
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

    getBottomToolbar: function() {
        var me = this;
        var pageSize = Ext.create('Ext.form.field.ComboBox', {
            labelWidth: 120,
            cls: Ext.baseCSSPrefix + 'page-size',
            queryMode: 'local',
            width: 180,
            listeners: {
                scope: me,
                select: function(combo, records) {
                    var record = records[0],
                        me = this;

                    me.store.pageSize = record.get('value');
                    me.store.loadPage(1);
                }
            },
            store: Ext.create('Ext.data.Store', {
                fields: [ 'value' ],
                data: [
                    { value: '10' },
                    { value: '20' },
                    { value: '40' },
                    { value: '60' },
                    { value: '80' },
                    { value: '100' },
                    { value: '250' },
                    { value: '500' },
                ]
            }),
            displayField: 'value',
            valueField: 'value',
            editable: false,
            emptyText: '20'
        });
        pageSize.setValue(me.store.pageSize);

        var pagingBar = Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock:'bottom',
            displayInfo:true
        });



        pagingBar.insert(pagingBar.items.length - 2, [ { xtype: 'tbspacer', width: 6 }, pageSize ]);

        return {
            dock: 'bottom',
            xtype: 'toolbar',
            items: [pagingBar, '->', {
                text: me.snippets.save,
                cls: 'primary',
                action: 'save'
            }]
        };

        return pagingBar;
    }
});
//{/block}