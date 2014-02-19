//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/config/general/panel"}
Ext.define('Shopware.apps.Bepado.view.config.general.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-config-general',

    border: false,
    layout: 'fit',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'label',
                html: '<h1>General configuration</h1>',
                padding: 10,
                region: 'north'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}