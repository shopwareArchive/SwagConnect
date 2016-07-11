//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/price/window"}
Ext.define('Shopware.apps.Connect.view.export.price.Window', {
    extend: 'Enlight.app.Window',
    alias: 'widget.connect-export-price-window',
    cls: Ext.baseCSSPrefix + 'connect',
    layout: 'border',
    width: 700,
    height: 570,
    title: 'Export price setting ',
    maximizable: false,
    minimizable: false,
    resizable: false,

    initComponent: function() {
        var me = this;

        me.items = [
            Ext.create('Shopware.apps.Connect.view.export.price.Form', {
                customerGroupStore: me.customerGroupStore
        })];

        me.callParent(arguments);
    }
});
//{/block}