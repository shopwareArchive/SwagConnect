//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/panel"}
Ext.define('Shopware.apps.Bepado.view.import.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-import',

    border: false,

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {

        });

        me.callParent(arguments);
    }
});
//{/block}