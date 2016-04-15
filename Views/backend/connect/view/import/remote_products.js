//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/import/remote_products"}
Ext.define('Shopware.apps.Connect.view.import.RemoteProducts', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.connect-products',
    store: 'import.RemoteProducts',

    border: false,

    selModel: {
        selType: 'checkboxmodel',
        mode: 'MULTI'
    },

    viewConfig: {
        plugins: {
            ptype: 'gridviewdragdrop',
            appendOnly: true,
            dragGroup: 'local',
            dropGroup: 'remote'
        }
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            height: 200,
            width: 450,

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
                header: 'Preis (brutto)',
                dataIndex: 'Price_basePrice',
                xtype: 'numbercolumn',
                format: '0.00',
                flex: 3
            }, {
                header: 'Steuersatz',
                dataIndex: 'Tax_name',
                flex: 1
            }
        ];
    },

    ///**
    // * Creates a paging toolbar with additional page size selector
    // *
    // * @returns Array
    // */
    getPagingToolbar: function() {
        var me = this;
        var pageSize = Ext.create('Ext.form.field.ComboBox', {
            cls: Ext.baseCSSPrefix + 'page-size',
            queryMode: 'local',
            width: 60,
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
                    { value: '500' }
                ]
            }),
            displayField: 'value',
            valueField: 'value',
            editable: false,
            emptyText: '10'
        });
        pageSize.setValue(me.store.pageSize);

        var pagingBar = Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock:'bottom',
            displayInfo:true,
            doRefresh : function(){
                var toolbar = this,
                    current = toolbar.store.currentPage;

                if (toolbar.fireEvent('beforechange', toolbar, current) !== false) {
                    toolbar.store.loadPage(current);
                }

                me.fireEvent('reloadRemoteCategories');
            }
        });
        pagingBar.insert(pagingBar.items.length - 2, [ { xtype: 'tbspacer', width: 6 }, pageSize ]);

        return pagingBar;
    }
});
//{/block}