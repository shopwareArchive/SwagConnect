//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/remote_products"}
Ext.define('Shopware.apps.Bepado.view.import.RemoteProducts', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.connect-products',
    store: 'import.RemoteProducts',

    border: false,

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
                dataIndex: 'number',
                flex: 1
            }, {
                header: 'Name',
                dataIndex: 'name',
                flex: 4
            }, {
                header: 'Hersteller',
                dataIndex: 'supplier',
                flex: 3
            }, {
                header: 'Preis (brutto)',
                dataIndex: 'price',
                flex: 3
            }, {
                header: 'Steuersatz',
                dataIndex: 'tax',
                flex: 1
            }
        ];
    },

    getPagingToolbar: function() {
        var me = this;

        var pagingBar = Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock:'bottom',
            displayInfo:true
        });

        return pagingBar;
    }
});
//{/block}