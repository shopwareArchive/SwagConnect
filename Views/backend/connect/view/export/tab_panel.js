//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/tab_panel"}
Ext.define('Shopware.apps.Connect.view.export.TabPanel', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.export-tab-panel',

    border: false,
    layout: 'card',
    snippets: {
        products: "{s name=export/tab/products}Products{/s}",
        streams: "{s name=export/tab/streams}Product streams{/s}"
    },

    initComponent: function () {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-export',
                title: me.snippets.products,
                itemId: 'export'
            //}]
            },{
                xtype: 'connect-export-stream',
                title: me.snippets.streams,
                itemId: 'stream'
            }]

        });

        me.callParent(arguments);
    }
});
//{/block}