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
                xtype: 'bepado-changed-products',
                itemId: 'changed'
            }, {
                xtype: 'bepado-config',
                itemId: 'config'
            }, {
                xtype: 'bepado-prices',
                itemId: 'prices'
            }, {
                xtype: 'bepado-mapping',
                itemId: 'mapping'
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
                xtype: 'bepado-log',
                itemId: 'log'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}