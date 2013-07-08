//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/main/navigation"}
Ext.define('Shopware.apps.Bepado.view.main.Navigation', {
    extend: 'Ext.tree.Panel',
    alias: 'widget.bepado-navigation',

    rootVisible: false,

    width: 200,
    layout: 'fit',
    //autoScroll: true,

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            store: 'main.Navigation'
            //dockedItems: me.getToolbar()
        });

        me.callParent(arguments);
    },

    getToolbar: function() {
        var me = this;
        return {
            xtype: 'toolbar',
            dock: 'bottom',
            border: false,
            cls: 'shopware-toolbar',
            items: me.getToolbarItems()
        };
    }
});
//{/block}