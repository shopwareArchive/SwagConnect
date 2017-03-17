//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/import/tab_panel"}
Ext.define('Shopware.apps.Connect.view.import.TabPanel', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.import-tab-panel',

    border: false,
    layout: 'card',
    snippets: {
        products: "{s name=import/tab/products}Products{/s}",
        units: "{s name=import/tab/units}Units{/s}",
        lastChanges: "{s name=connect/tab_panel/last_changes}Last changes{/s}"
    },

    initComponent: function () {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                style: 'background: #f0f2f4',
                xtype: 'connect-import',
                title: me.snippets.products,
                itemId: 'import'
            }, {
                xtype: 'connect-import-unit',
                title: me.snippets.units,
                itemId: 'unit'
            }, {
                xtype: 'connect-changed-products',
                title: me.snippets.lastChanges,
                itemId: 'changed'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}