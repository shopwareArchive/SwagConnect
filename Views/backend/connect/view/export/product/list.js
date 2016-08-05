//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/product/list"}
Ext.define('Shopware.apps.Connect.view.export.product.List', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.connect-export-list',

    border: false,

    store: 'export.List',

    selModel: {
        selType: 'checkboxmodel',
        mode: 'MULTI'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            dockedItems: [
                me.getPagingToolbar()
            ],
            columns: me.getColumns()
        });

        me.callParent(arguments);
        me.on('render', me.loadStore, me);
    },

    registerEvents: function() {
        this.addEvents('getExportStatus', 'localProducts');
    },

    loadStore: function() {
        var me = this;
        me.getStore().load({
            callback: function(records, options, success) {
                me.fireEvent('getExportStatus');
            }
        });
    },

    getColumns: function() {
        var me = this;
        return [{
            header: '{s name=export/columns/number}Number{/s}',
            dataIndex: 'number',
            flex: 2
        }, {
            header: '{s name=export/columns/name}Name{/s}',
            dataIndex: 'name',
            flex: 4
        }, {
            header: '{s name=export/columns/supplier}Supplier{/s}',
            dataIndex: 'supplier',
            flex: 3
        }, {
            header: '{s name=export/columns/active}Active{/s}',
            xtype: 'booleancolumn',
            dataIndex: 'active',
            width: 50,
            sortable: false
        }, {
            header: '{s name=export/columns/price}Price{/s}',
            xtype: 'numbercolumn',
            dataIndex: 'price',
            align: 'right',
            width: 55
        }, {
            header: '{s name=export/columns/tax}Tax{/s}',
            xtype: 'numbercolumn',
            dataIndex: 'tax',
            align: 'right',
            flex: 1
        }, {
            header: '{s name=export/columns/stock}Stock{/s}',
            xtype: 'numbercolumn',
            dataIndex: 'inStock',
            format: '0,000',
            align: 'right',
            flex: 1
        }, {
            header: '{s name=export/columns/status}Status{/s}',
            dataIndex: 'exportStatus',
            flex: 1,
            renderer: function(value, metaData, record) {
                var className;

                if (!value) {
                    return;
                }

                if (me.iconMapping.hasOwnProperty(value)) {
                    className = me.iconMapping[value];
                }

                if(record.get('exportMessage')) {
                    metaData.tdAttr = 'data-qtip="' +  record.get('exportMessage') + '"';
                } else {
                    metaData.tdAttr = 'data-qtip="' +  value + '"';
                }

                return '<div class="' + className + '" style="width: 16px; height: 16px;"></div>';
            }
        }, {
            xtype: 'actioncolumn',
            width: 26,
            items: [{
                action: 'edit',
                cls: 'editBtn',
                iconCls: 'sprite-pencil',
                handler: function(view, rowIndex, colIndex, item, opts, record) {
                    Shopware.app.Application.addSubApplication({
                        name: 'Shopware.apps.Article',
                        action: 'detail',
                        params: {
                            articleId: record.get('id')
                        }
                    });
                }
            }]
        }];
    },

    /**
     * Creates a paging toolbar with additional page size selector
     *
     * @returns Array
     */
    getPagingToolbar: function() {
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
                    { value: '20' },
                    { value: '40' },
                    { value: '60' },
                    { value: '80' },
                    { value: '100' },
                    { value: '250' }
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
            displayInfo:true,
            doRefresh : function(){
                var toolbar = this,
                    current = toolbar.store.currentPage;

                if (toolbar.fireEvent('beforechange', toolbar, current) !== false) {
                    toolbar.store.loadPage(current);
                }
                me.fireEvent('reloadLocalProducts');
            }
        });

        pagingBar.insert(pagingBar.items.length - 2, [ { xtype: 'tbspacer', width: 6 }, pageSize ]);
        return pagingBar;
    }
});
//{/block}