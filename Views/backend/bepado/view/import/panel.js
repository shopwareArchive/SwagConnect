//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/panel"}
Ext.define('Shopware.apps.Bepado.view.import.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-import',

    border: false,
    layout: 'border',
    padding: '10px',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'connect-remote-categories',
                    padding: '10px',
                    columnWidth: .50
                } ,
                {
                    xtype: 'connect-own-categories',
                    padding: '10px',
                    columnWidth: .50
                }
            ]
        });

        me.callParent(arguments);
    }
});
//{/block}