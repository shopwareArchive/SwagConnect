//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/export/list"}
Ext.define('Shopware.apps.Bepado.view.export.List', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.bepado-export-list',

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
                me.getToolbar(),
                me.getPagingToolbar()
            ],
            columns: me.getColumns()
        });

        me.callParent(arguments);

        me.store.load();
    },

    getColumns: function() {
        var me = this;
        return [{
            header: 'Number',
            dataIndex: 'number',
            flex: 2
        }, {
            header: 'Name',
            dataIndex: 'name',
            flex: 4
        }, {
            header: 'Supplier',
            dataIndex: 'supplier',
            flex: 3
        }, {
            header: 'Active',
            xtype: 'booleancolumn',
            dataIndex: 'active',
            width: 50
        }, {
            header: 'Price',
            xtype: 'numbercolumn',
            dataIndex: 'price',
            align: 'right',
            width: 55
        }, {
            header: 'Tax',
            xtype: 'numbercolumn',
            dataIndex: 'tax',
            align: 'right',
            flex: 1
        }, {
            header: 'Stock',
            xtype: 'numbercolumn',
            dataIndex: 'inStock',
            format: '0,000',
            align: 'right',
            flex: 1
        }, {
            header: 'Status',
            dataIndex: 'exportStatus',
            flex: 2,
            renderer: function(value, metaData, record) {
                if(record.get('exportMessage')) {
                    metaData.tdAttr = 'data-qtip="' +  record.get('exportMessage') + '"';
                }
                return value;
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

    getPagingToolbar: function() {
        var me = this;
        return {
            xtype: 'pagingtoolbar',
            displayInfo: true,
            store: me.store,
            dock: 'bottom'
        };
    },

    getToolbar: function() {
        var me = this;
        return {
            xtype: 'toolbar',
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
            iconCls:'sprite-plus-circle-frame',
            text:'Produkt(e) exportieren / aktualisieren',
            //tooltip:'{s name=list/add_tooltip}Add (ALT + INSERT){/s}',
            action:'add'
        });
        items.push({
            iconCls:'sprite-minus-circle-frame',
            text:'Produkt(e) aus dem Export löschen',
            //tooltip:'{s name=list/delete_tooltip}Delete (ALT + DELETE){/s}',
            action:'delete'
        });
        //items.push('->', {
        //    xtype:'textfield',
        //    name:'searchfield',
        //    cls:'searchfield',
        //    width:100,
        //    emptyText:'{s name=search/empty_text}Search...{/s}',
        //    enableKeyEvents:true,
        //    checkChangeBuffer:500
        //}, {
        //    xtype:'tbspacer', width:6
        //});
        return items;
    }
});
//{/block}