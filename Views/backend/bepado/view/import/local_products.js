//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/local_products"}
Ext.define('Shopware.apps.Bepado.view.import.LocalProducts', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.local-products',
    store: 'import.LocalProducts',

    border: false,

    viewConfig: {
        plugins: {
            ptype: 'gridviewdragdrop',
            appendOnly: true,
            dropGroup: 'local'
        },
        getRowClass: function(rec, rowIdx, params, store) {
            return rec.get('Attribute_bepadoMappedCategory') == 1 ? 'shopware-connect-color' : '';
        }
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            height: 200,
            width: 400,

            dockedItems: [
                me.getPagingToolbar()
            ],
            columns: me.getColumns()
        });

        me.callParent(arguments);
    },

    getColumns: function() {
        return [
            {
                header: 'Aritkel Nr.',
                dataIndex: 'Detail_number',
                flex: 1
            }, {
                header: 'Name',
                dataIndex: 'Article_name',
                flex: 4
            }, {
                header: 'Hersteller',
                dataIndex: 'Supplier_name',
                flex: 3
            }, {
                header: 'Aktiv',
                dataIndex: 'Article_active',
                flex: 1,
                renderer: function(value, metaData, record) {
                    var checked = 'sprite-ui-check-box-uncheck';
                    if (value == true) {
                        checked = 'sprite-ui-check-box';
                    }
                    return '<span style="display:block; margin: 0 auto; height:25px; width:25px;" class="' + checked + '"></span>';
                }
            }, {
                header: 'Preis (brutto)',
                xtype: 'numbercolumn',
                dataIndex: 'Price_basePrice',
                flex: 3
            }, {
                header: 'Steuersatz',
                dataIndex: 'Tax_name',
                flex: 1
            }
        ];
    },

    getPagingToolbar: function() {
        var me = this;

        return Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock:'bottom',
            displayInfo:true
        });
    }
});
//{/block}