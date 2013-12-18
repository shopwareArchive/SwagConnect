//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/prices/list"}
Ext.define('Shopware.apps.Bepado.view.prices.List', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.bepado-prices-list',

    border: false,

    store: 'config.Prices',


    initComponent: function() {
        var me = this;

        me.createPlugins();

        Ext.applyIf(me, {
            columns: me.getColumns()
        });

        // Save the record when the 'update' button is clicked
        me.on('edit', function(editor, e) {
            var record = e.record;

            record.save();
        });

        me.callParent(arguments);

        me.store.load();
    },

    createPlugins: function() {
        var me = this;

        me.rowEditor = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToEdit: 2,
            autoCancel: true
        });
        me.plugins = [ me.rowEditor ];
    },

    getColumns: function() {
        var me = this;
        return [{
            header: '{s name=prices/columns/bepadoField}bepado field{/s}',
            dataIndex: 'bepadoField',
            flex: 2,
            sortable: false
        }, {
            header: '{s name=prices/columns/localCustomergroup}Local customergroup{/s}',
            dataIndex: 'customerGroup',
            flex: 4,
            sortable: false,
            editor: {
                xtype: 'combobox',
                queryMode: 'remote',
                editable: false,
                allowBlank: false,
                displayField: 'name',
                valueField: 'key',
                store: 'base.CustomerGroup'

            }
        }, {
            header: '{s name=prices/columns/fieldToUse}Local price field{/s}',
            dataIndex: 'priceField',
            flex: 3,
            sortable: false,
            editor: {
                xtype: 'combobox',
                queryMode: 'local',
                editable: false,
                allowBlank: false,
                displayField: 'field',
                valueField: 'field',
                store: Ext.create('Ext.data.Store', {
                    fields: ['field'],
                    data: [
                        { field: 'basePrice' },
                        { field: 'price' },
                        { field: 'pseudoPrice' },
                    ]
                })
            }
        }];
    }
});
//{/block}