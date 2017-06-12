//{namespace name=backend/connect/view/main}

Ext.tip.QuickTipManager.init();

//{block name="backend/connect/view/import/local_products"}
Ext.define('Shopware.apps.Connect.view.import.LocalProducts', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.local-products',
    store: 'import.LocalProducts',

    snippets: {
        removeProducts: '{s name=import/remove_products}Remove Products{/s}',
        activateProductsLabel: '{s name=import/activate_products}Activate products{/s}',
        showOnlyConnectProductsLabel: '{s name=import/show_only_connect_products}Products{/s}',
        orderNumberColumnLabel: '{s name=import/columns/order_number}Order number{/s}',
        nameColumnLabel: '{s name=import/columns/name}Name{/s}',
        supplierColumnLabel: '{s name=import/columns/supplier}Supplier{/s}',
        activeColumnLabel: '{s name=import/columns/active}Active{/s}',
        retailersPriceColumnLabel: '{s name=import/columns/retailers_buying_price}Retailers buying price{/s}',
        priceColumnLabel: '{s name=import/columns/price}Price{/s}',
        taxRateColumnLabel: '{s name=import/columns/tax_rate}Tax rate{/s}'
    },

    border: false,

    viewConfig: {
        plugins: {
            ptype: 'gridviewdragdrop',
            appendOnly: true,
            dragGroup: 'remote',
            dropGroup: 'local'
        },
        getRowClass: function(rec, rowIdx, params, store) {
            return rec.get('Attribute_connectMappedCategory') == 1 ? 'shopware-connect-color' : 'local-product-color';
        }
    },

    selModel: {
        selType: 'checkboxmodel',
        mode: 'MULTI'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            height: 200,
            width: '90%',

            dockedItems: [
                me.getToolbar(),
                me.getPagingToolbar()
            ],
            columns: me.getColumns()
        });

        me.callParent(arguments);
    },

    getColumns: function() {
        var me = this;

        return [
            {
                header: me.snippets.orderNumberColumnLabel,
                dataIndex: 'Detail_number',
                flex: 2
            }, {
                header: me.snippets.nameColumnLabel,
                dataIndex: 'Article_name',
                flex: 3,
                renderer: function(value, metaData, record) {
                    var isConnectProduct = record.get('Attribute_connectMappedCategory');
                    if (isConnectProduct) {
                        return '<span class="connect-icon" style="padding: 2px 0 6px 20px">' + value + '</span>';
                    }
                    return value;
                }
            }, {
                header: me.snippets.supplierColumnLabel,
                dataIndex: 'Supplier_name',
                flex: 3
            }, {
                header: me.snippets.activeColumnLabel,
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
                header: me.snippets.retailersPriceColumnLabel,
                xtype: 'numbercolumn',
                dataIndex: 'Detail_purchasePrice',
                flex: 2,
                renderer: function (value, meta) {
                    meta.tdAttr = 'data-qtip="Händlereinkaufspreis"';
                    return Ext.util.Format.number(value, this.format);
                }
            }, {
                header: me.snippets.priceColumnLabel,
                xtype: 'numbercolumn',
                dataIndex: 'Price_price',
                flex: 2
            }, {
                header: me.snippets.taxRateColumnLabel,
                dataIndex: 'Tax_name',
                flex: 2
            }, {
                xtype: 'actioncolumn',
                width: 52,
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
                }, {
                    iconCls: 'sprite-minus-circle-frame',
                    action: 'delete',
                    handler: function (view, rowIndex, colIndex, item, opts, record) {
                        me.fireEvent('deleteProduct', record);
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

                me.fireEvent('reloadOwnCategories');
            }
        });
        pagingBar.insert(pagingBar.items.length - 2, [ { xtype: 'tbspacer', width: 6 }, pageSize ]);

        return pagingBar;
    },

    getToolbar: function() {
        var me = this;

        return Ext.create('Ext.toolbar.Toolbar', {
            padding: '2px 0 2px 10px',
            dock: 'top',
            ui: 'shopware-ui',
            items: [{
                xtype: 'button',
                iconCls: 'sprite-plus-circle-frame',
                text: me.snippets.activateProductsLabel,
                action:'activateProducts'
            }, {
                xtype: 'button',
                iconCls: 'sprite-minus-circle-frame',
                margin: '0 10px 0 0',
                text: me.snippets.removeProducts,
                action:'unAssignArticlesFromCategory'
            }, {
                xtype : 'checkbox',
                boxLabelCls: "x-form-cb-label connect-icon connect-checkbox-label",
                name : 'attribute[connectAllowed]',
                action: 'show-only-connect-products',
                checked: true,
                boxLabel : "- " + me.snippets.showOnlyConnectProductsLabel
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
            action: 'search-local-products'
        }
    }
});
//{/block}