//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/main/panel"}
Ext.define('Shopware.apps.Bepado.view.main.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-panel',

    border: false,
    layout: 'card',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'bepado-home-page',
                itemId: 'home'
            }, {
                xtype: 'bepado-config',
                itemId: 'config'
            }, {
                xtype: 'bepado-config-import',
                itemId: 'config-import'
            }, {
                xtype: 'bepado-config-export',
                itemId: 'config-export'
            }, {
                xtype: 'bepado-config-units',
                itemId: 'config-units'
            }, {
                xtype: 'bepado-shipping-groups',
                itemId: 'shipping-groups'
            }, {
                xtype: 'bepado-mapping-import',
                itemId: 'mapping-import'
            }, {
                xtype: 'bepado-mapping-export',
                itemId: 'mapping-export'
            }, {
                xtype: 'bepado-export',
                itemId: 'export'
            }, {
                xtype: 'bepado-import',
                itemId: 'import'
            }, {
                xtype: 'bepado-changed-products',
                itemId: 'changed'
            }, {
                xtype: 'bepado-log',
                itemId: 'log'
            }, {
                xtype: 'bepado-shipping-groups',
                itemId: 'config-shipping-groups'
            }, {
                xtype: 'bepado-settings-main',
                itemId: 'settings-main'
            }, {
                xtype: 'bepado-import-main',
                itemId: 'import-main'
            }, {
                xtype: 'bepado-export-main',
                itemId: 'export-main'
            }
            ]
        });

        me.callParent(arguments);
    }
});
//{/block}