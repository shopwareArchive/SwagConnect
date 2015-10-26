//{namespace name=backend/bepado/view/main}

/**
 * Shopware Controller - Cache backend module
 */
//{block name="backend/bepado/controller/import"}
Ext.define('Shopware.apps.Bepado.controller.Import', {

    extend: 'Enlight.app.Controller',

    stores: [
        'import.RemoteProducts', 'import.LocalProducts'
    ],

    refs: [
        { ref: 'remoteProductsGrid', selector: 'connect-products' },
        { ref: 'localProductsGrid', selector: 'local-products' },
        { ref: 'localCategoryTree', selector: 'connect-own-categories' }
    ],

    /**
     * Init component. Basically will create the app window and register to events
     */
    init: function () {
        var me = this;

        me.control({
            'connect-remote-categories': {
                itemmousedown: me.onSelectRemoteCategory
            },
            'connect-own-categories': {
                itemmousedown: me.onSelectLocalCategory
            },
            'local-products dataview': {
                beforedrop: me.onBeforeDropLocalProduct
            }
        });

        me.callParent(arguments);
    },

    onSelectRemoteCategory: function(treePanel, record) {
        var me = this;

        me.getRemoteProductsGrid().getStore().getProxy().extraParams.category = record.get('id');
        me.getRemoteProductsGrid().getStore().load();
    },

    onSelectLocalCategory: function(treePanel, record) {
        var me = this;

        me.getLocalProductsGrid().getStore().getProxy().extraParams.categoryId = record.get('id');
        me.getLocalProductsGrid().getStore().load();
    },

    onBeforeDropLocalProduct: function(node, data, overModel, dropPosition, dropHandlers)
    {
        var me = this;
        var selected = me.getLocalCategoryTree().getSelectionModel().getSelection()
        if (selected.length > 0) {
            dropHandlers.processDrop();
        } else {
            dropHandlers.cancelDrop();
        }
    }
});
//{/block}