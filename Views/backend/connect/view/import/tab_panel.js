//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/import/tab_panel"}
Ext.define('Shopware.apps.Connect.view.import.TabPanel', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.import-tab-panel',

    border: false,
    layout: 'card',
    snippets: {
        products: "{s name=import/tab/products}Products{/s}",
        units: "{s name=import/tab/units}Units{/s}"
    },

    initComponent: function () {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-import',
                title: me.snippets.products,
                itemId: 'import'
            }, {
                xtype: 'connect-import-unit',
                title: me.snippets.units,
                itemId: 'unit'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}