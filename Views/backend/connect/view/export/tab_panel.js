//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/tab_panel"}
Ext.define('Shopware.apps.Connect.view.export.TabPanel', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.export-tab-panel',

    border: false,
    layout: 'card',

    initComponent: function () {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-export',
                title: '{s name=export/tab/products}Products{/s}',
                iconMapping: me.getStatusIconMapping(),
                iconLabelMapping: me.getIconLabelMapping(),
                itemId: 'export'
            }, {
                xtype: 'connect-export-stream',
                title: '{s name=export/tab/streams}Product Streams{/s}',
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
            'delete': 'sprite­bin­metal­full'
        };
    },

    getIconLabelMapping: function() {
        return {
            'insert': '{s name=export/filter/status_insert}Inserting{/s}',
            'synced': '{s name=export/statusSynced}Synced{/s}',
            'error': '{s name=export/filter/status_error}Error{/s}',
            'error-price': '{s name=export/message/error_price_status}There is an empty price field{/s}',
            'inactive': '{s name=export/filter/status_inactive}Inactive{/s}',
            'update': '{s name=export/filter/status_update}Updating{/s}',
            'custom-product': '{s name=export/list/customProduct}Custom products are excluded from export{/s}',
            'export': '{s name=export/statusExport}Export{/s}',
            'delete': '{s name=export/filter/status_delete}Delete{/s}'
        };
    }
});
//{/block}