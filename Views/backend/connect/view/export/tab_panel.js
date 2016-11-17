//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/tab_panel"}
Ext.define('Shopware.apps.Connect.view.export.TabPanel', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.export-tab-panel',

    border: false,
    layout: 'card',
    snippets: {
        products: "{s name=export/tab/products}Products{/s}",
        streams: "{s name=export/tab/streams}Product Streams{/s}",
        statuses: {
            statusExport: "{s name=export/statusExport}Export{/s}",
            statusSynced: "{s name=export/statusSynced}Syncedd{/s}"
        }
    },

    initComponent: function () {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-export',
                title: me.snippets.products,
                iconMapping: me.getStatusIconMapping(),
                iconLabelMapping: me.getIconLabelMapping(),
                itemId: 'export'
            }, {
                xtype: 'connect-export-stream',
                title: me.snippets.streams,
                iconMapping: me.getStatusIconMapping(),
                iconLabelMapping: me.getIconLabelMapping(),
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
            'inactive': 'sc-icon-inactive icon-size',
            'update': 'sprite-arrow-circle-135',
            'custom-product': 'sc-icon-custom-product',
            'export': 'sprite-arrow-circle-135',
            'cron-update': 'sprite-arrow-circle-135'
        };
    },

    getIconLabelMapping: function() {
        var me = this;

        return {
            'insert': 'insert',
            'synced': me.snippets.statuses.statusSynced,
            'error': 'error',
            'error-price': 'error-price',
            'inactive': 'inactive',
            'update': 'update',
            'custom-product': 'custom-product',
            'export': me.snippets.statuses.statusExport,
            'cron-update': 'Cron update'
        };
    }
});
//{/block}