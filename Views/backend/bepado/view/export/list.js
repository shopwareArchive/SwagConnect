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
            header: 'number',
            dataIndex: 'number',
            flex: 2
        }, {
            header: 'name',
            dataIndex: 'name',
            flex: 4
        }, {
            header: 'supplier',
            dataIndex: 'supplier',
            flex: 3
        }, {
            header: 'active',
            xtype: 'booleancolumn',
            dataIndex: 'active',
            width: 50
        }, {
            header: 'price',
            xtype: 'numbercolumn',
            dataIndex: 'price',
            align: 'right',
            width: 55
        }, {
            header: 'tax',
            xtype: 'numbercolumn',
            dataIndex: 'tax',
            align: 'right',
            flex: 1
        }, {
            header: 'inStock',
            xtype: 'numbercolumn',
            dataIndex: 'inStock',
            format: '0,000',
            align: 'right',
            flex: 1
        }, {
            header: 'status',
            dataIndex: 'exportStatus',
            flex: 2,
            renderer: function(value, metaData, record) {
                metaData.tdAttr = 'data-qtip="' +  record.get('exportMessage') + '"';
                return value;
            }
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
            text:'Produkt hinzufügen / aktualisieren',
            //tooltip:'{s name=list/add_tooltip}Add (ALT + INSERT){/s}',
            action:'add'
        });
        items.push({
            iconCls:'sprite-minus-circle-frame',
            text:'Aus dem Export löschen',
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