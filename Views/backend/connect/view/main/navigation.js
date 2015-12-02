//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/main/navigation"}
Ext.define('Shopware.apps.Connect.view.main.Navigation', {
    extend: 'Ext.tree.Panel',
    alias: 'widget.connect-navigation',

    rootVisible: false,

    width: 200,
    layout: 'fit',
    //autoScroll: true,

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            store: 'main.Navigation',
            listeners: {
                afterrender: function(tree, eOpts) {
                    var record = tree.getStore().getNodeById('config');
                    tree.getSelectionModel().select(record);
                }
            }
        });

        me.callParent(arguments);
    }
});
//{/block}