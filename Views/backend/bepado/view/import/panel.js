//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/panel"}
Ext.define('Shopware.apps.Bepado.view.import.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-import',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'bepado-import-filter',
                region: 'west',
                //collapsible: true,
                split: true
            },{
                xtype: 'bepado-import-list',
                region: 'center'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}