//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/config/tab_panel"}
Ext.define('Shopware.apps.Connect.view.config.TabPanel', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.connect-config-tab-panel',

    border: false,
    layout: 'card',
    snippets: {
        settings: "{s name=connect/tab_panel/settings}Settings{/s}",
        import: "{s name=connect/tab_panel/import}Import{/s}",
        export: "{s name=connect/tab_panel/export}Export{/s}",
        log: "{s name=connect/tab_panel/log}Log{/s}"
    },

    initComponent: function () {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-config',
                title: me.snippets.settings,
                itemId: 'config'
            }, {
                xtype: 'connect-config-import',
                title: me.snippets.import,
                itemId: 'config-import'
            }, {
                xtype: 'connect-config-export',
                title: me.snippets.export,
                customerGroupStore: me.customerGroupStore,
                itemId: 'config-export'
            }, {
                xtype: 'connect-log',
                title: me.snippets.log,
                itemId: 'log'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}