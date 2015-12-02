//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/main/panel"}
Ext.define('Shopware.apps.Connect.view.main.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-panel',

    border: false,
    layout: 'card',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-config',
                itemId: 'config'
            }, {
                xtype: 'connect-config-import',
                itemId: 'config-import'
            }, {
                xtype: 'connect-config-export',
                itemId: 'config-export'
            }, {
                xtype: 'connect-config-marketplace-attributes',
                itemId: 'marketplace-attributes'
            }, {
                xtype: 'connect-export',
                itemId: 'export'
            }, {
                xtype: 'connect-import',
                itemId: 'import'
            }, {
                xtype: 'connect-changed-products',
                itemId: 'changed'
            }, {
                xtype: 'connect-log',
                itemId: 'log'
            }
            ]
        });

        me.callParent(arguments);
    }
});
//{/block}