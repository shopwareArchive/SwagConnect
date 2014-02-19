//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/config/export/panel"}
Ext.define('Shopware.apps.Bepado.view.config.export.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-config-export',

    border: false,
    layout: 'fit',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'label',
                html: '<h1>Export configuration</h1>',
                padding: 10,
                region: 'north'
            }, {
                xtype: 'bepado-config-tabs'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}