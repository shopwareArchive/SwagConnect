//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/log/panel"}
Ext.define('Shopware.apps.Connect.view.log.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-log',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-log-filter',
                region: 'west',
                //collapsible: true,
                split: true
            },{
                xtype: 'connect-log-list',
                region: 'center'
            }, {
                xtype: 'connect-log-tabs',
                collapsible: true,
                split: true,
                region: 'south'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}