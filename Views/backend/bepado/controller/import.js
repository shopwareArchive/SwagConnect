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
        { ref: 'window', selector: 'bepado-window' },
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
                beforedrop: me.onBeforeDropLocalProduct,
                drop: me.onDropToLocalProducts
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
        var selected = me.getLocalCategoryTree().getSelectionModel().getSelection();
        if (selected.length > 0) {
            dropHandlers.processDrop();
        } else {
            dropHandlers.cancelDrop();
        }
    },

    onDropToLocalProducts: function( node, data, overModel, dropPosition, eOpts)
    {
        var me = this;
        var articleIds = [];

        for (var i = 0; i < data.records.length; i++) {
            articleIds.push(data.records[i].get('Article_id'));
        }

        if (articleIds.length == 0) {
            //todo: add message
            return;
        }

        var selected = me.getLocalCategoryTree().getSelectionModel().getSelection();

        console.log(selected.length);
        if (selected.length == 0) {
            //todo: add message
            return;
        }

        Ext.Ajax.request({
            url: '{url controller=Import action=assignArticlesToCategory}',
            method: 'POST',
            params: {
                categoryId: selected[0].get('id'),
                'articleIds[]': articleIds
            },
            success: function(response, opts) {
                //todo: change messages
                if (response.success == true) {
                    me.createGrowlMessage('{s name=success}Success{/s}', '{s name=changed_products/success/message}Successfully applied changes{/s}');
                } else {
                    me.createGrowlMessage('{s name=error}Error{/s}', 'Changes are not applied');
                }
            },
            failure: function(response, opts) {
                me.createGrowlMessage('{s name=error}Error{/s}', 'error');
            }

        });
    },

    /**
     * Helper to show a growl message
     *
     * @param title
     * @param message
     */
    createGrowlMessage: function(title, message, sticky) {
        var me = this,
            win = me.getWindow();
        if (!sticky) {
            Shopware.Notification.createGrowlMessage(title, message, win.title);
        } else {
            Shopware.Notification.createStickyGrowlMessage({
                title: title,
                text: message,
                width: 400
            });
        }
    }
});
//{/block}