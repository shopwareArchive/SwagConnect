//{namespace name=backend/connect/view/main}

Ext.tip.QuickTipManager.init();

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

    snippets: {
        hideMappedProducts: '{s name=import/hide_mapped}Hide Assigned products{/s}',
        assignProducts: '{s name=import/assign_selected_products}Add product{/s}'
    },

    viewConfig: {
        plugins: {
            ptype: 'gridviewdragdrop',
            appendOnly: true,
            dragGroup: 'local',
            dropGroup: 'remote'
        },
        getRowClass: function(rec, rowIdx, params, store) {
            return 'shopware-connect-color';
        }
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            height: 200,
            width: 450,

            dockedItems: [
                me.getToolbar(),
                me.getPagingToolbar()

            ],
            columns: me.getColumns()
        });

        me.callParent(arguments);

    },

    getColumns: function() {
        return [
            {
                header: 'Artikel Nr.',
                dataIndex: 'Detail_number',
                flex: 2
            }, {
                header: 'Name',
                dataIndex: 'Article_name',
                flex: 3
            }, {
                header: 'Hersteller',
                dataIndex: 'Supplier_name',
                flex: 3
            }, {
                header: 'HEK',
                dataIndex: 'Price_basePrice',
                xtype: 'numbercolumn',
                format: '0.00',
                flex: 2,
                renderer: function (value, meta) {
                    meta.tdAttr = 'data-qtip="Händlereinkaufspreis"';
                    return Ext.util.Format.number(value, this.format);
                }
            }, {
                header: 'Steuersatz',
                dataIndex: 'Tax_name',
                flex: 2
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
                                articleId: record.get('Article_id')
                            }
                        });
                    }
                }]
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
    },

    getToolbar: function () {
        var me = this;

        return Ext.create('Ext.toolbar.Toolbar', {
            padding: '0 0 0 10px',
            dock: 'top',
            ui: 'shopware-ui',
            items: [{
                xtype: 'button',
                iconCls: 'sprite-plus-circle-frame',
                alias: 'widget.arrow-unassign-categories',
                text: me.snippets.assignProducts,
                action: 'assignArticlesToCategory',
                margin: '0 10px 0 0'
            }, {
                xtype : 'checkbox',
                name : 'attribute[hideMapped]',
                action: 'hide-mapped-products',
                checked: false,
                boxLabel : me.snippets.hideMappedProducts
            }, '->',
                me.getSearchFilter()
            ]
        });
    },

    getSearchFilter: function() {
        return {
            xtype:'textfield',
            anchor: '100%',
            cls:'searchfield',
            emptyText:'{s name=import/filter/search_empty}Search...{/s}',
            enableKeyEvents:true,
            checkChangeBuffer:500,
            action: 'search-remote-products'
        }
    }
});
//{/block}