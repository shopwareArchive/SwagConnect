//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/tab_panel"}
Ext.define('Shopware.apps.Connect.view.export.TabPanel', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.export-tab-panel',

    border: false,
    layout: 'card',
    snippets: {
        products: "{s name=export/tab/products}Products{/s}",
        streams: "{s name=export/tab/streams}Product Streams{/s}"
    },

    initComponent: function () {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-export',
                title: me.snippets.products,
                iconMapping: me.getStatusIconMapping(),
                itemId: 'export'
            }, {
                xtype: 'connect-export-stream',
                title: me.snippets.streams,
                iconMapping: me.getStatusIconMapping(),
                itemId: 'stream'
            }]
        });

        me.callParent(arguments);
    },

    getStatusIconMapping: function() {
        return {
            'insert': 'sprite-arrow-circle-135',
            'synced': 'sprite-tick-circle',
            'error': 'sprite-minus-circle-frame',
            'error-price': 'icon-creative-commons-noncommercial-eu icon-size',
            'update': 'sprite-arrow-circle-135'
        };
    }
});
//{/block}