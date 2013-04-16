//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/export/panel"}
Ext.define('Shopware.apps.Bepado.view.export.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-export',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'bepado-export-filter',
                region: 'west',
                //collapsible: true,
                split: true
            },{
                xtype: 'bepado-export-list',
                region: 'center'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}