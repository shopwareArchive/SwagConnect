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
                html: '<h1>Hello World</h1>'
            }, {
                xtype: 'bepado-config',
                itemId: 'config'
            }, {
                xtype: 'bepado-mapping',
                itemId: 'mapping'
            }, {
                xtype: 'bepado-export',
                itemId: 'export'
            }, {
                xtype: 'bepado-import',
                itemId: 'import'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}