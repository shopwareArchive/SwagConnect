//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/import/local_products"}
Ext.define('Shopware.apps.Connect.view.import.LocalProducts', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.local-products',
    store: 'import.LocalProducts',

    snippets: {
        removeProducts: '{s name=import/remove_products}Remove Products{/s}',
        activateProductsLabel: '{s name=import/activate_products}Activate products{/s}',
        showOnlyConnectProductsLabel: '{s name=import/show_only_connect_products}Show products{/s}'
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
                flex: 2
            }, {
                header: 'Steuersatz',
                dataIndex: 'Tax_name',
                flex: 2
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
            padding: '0 0 0 10px',
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
            }]
        });
    }
});
//{/block}