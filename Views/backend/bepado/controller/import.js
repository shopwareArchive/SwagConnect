//{namespace name=backend/bepado/view/main}

/**
 * Shopware Controller - Cache backend module
 */
//{block name="backend/bepado/controller/import"}
Ext.define('Shopware.apps.Bepado.controller.Import', {

    extend: 'Enlight.app.Controller',

    stores: [
        'import.RemoteProducts'
    ],

    refs: [
        { ref: 'remoteProductsGrid', selector: 'connect-products' }
    ],

    /**
     * Init component. Basically will create the app window and register to events
     */
    init: function () {
        var me = this;

        me.control({
            'connect-remote-categories': {
                itemmousedown: me.onSelectRemoteCategory
            }
        });

        me.callParent(arguments);
    },

    onSelectRemoteCategory: function(treePanel, record) {
        var me = this;

        me.getRemoteProductsGrid().getStore().getProxy().extraParams.category = record.get('id');
        me.getRemoteProductsGrid().getStore().load();
    }
});
//{/block}