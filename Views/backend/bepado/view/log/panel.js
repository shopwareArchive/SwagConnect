//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/log/panel"}
Ext.define('Shopware.apps.Bepado.view.log.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-log',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'bepado-log-filter',
                region: 'west',
                //collapsible: true,
                split: true
            },{
                xtype: 'bepado-log-list',
                region: 'center'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}