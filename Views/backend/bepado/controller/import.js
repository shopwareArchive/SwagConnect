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
        { ref: 'localCategoryTree', selector: 'connect-own-categories' },
        { ref: 'RemoteCategoryTree', selector: 'connect-remote-categories' }
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
            'connect-own-categories dataview': {
                drop: me.onDropToLocalCategory
            },
            'local-products dataview': {
                beforedrop: me.onBeforeDropLocalProduct,
                drop: me.onDropToLocalProducts
            },
            'bepado-import button[action=importRemoteCategory]': {
                click: me.onImportRemoteCategoryButtonClick
            },
            'bepado-import checkbox[action=filter-only-local-products]': {
                change: me.onFilterLocalProducts
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

    onDropToLocalProducts: function(node, data, overModel, dropPosition, eOpts)
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

    onDropToLocalCategory: function(node, data, overModel, dropPosition, eOpts) {
        var me = this;

        if (!data.records) {
            //todo: add message
            return;
        }

        if (!overModel) {
            //todo: add message
            return;
        }

        var remoteCategoryKey = data.records[0].get('id'),
            remoteCategoryLabel = data.records[0].get('text'),
            localCategoryId = overModel.get('id');

        me.importRemoteToLocalCategories(remoteCategoryKey, remoteCategoryLabel, localCategoryId);
    },

    importRemoteToLocalCategories: function(remoteCategoryKey, remoteCategoryLabel, localCategoryId) {
        var me = this;

        //todo: show loading animation
        Ext.Ajax.request({
            url: '{url controller=Import action=assignRemoteToLocalCategory}',
            method: 'POST',
            params: {
                remoteCategoryKey: remoteCategoryKey,
                remoteCategoryLabel: remoteCategoryLabel,
                localCategoryId: localCategoryId
            },
            success: function(response, opts) {
                var data = Ext.JSON.decode(response.responseText);
                //todo: change messages
                if (data.success == true) {
                    me.createGrowlMessage('{s name=success}Success{/s}', '{s name=changed_products/success/message}Successfully applied changes{/s}');
                } else {
                    me.createGrowlMessage('{s name=error}Error{/s}', 'Changes are not applied');
                }

                me.getRemoteCategoryTree().getStore().getRootNode().removeAll();
                me.getRemoteCategoryTree().getStore().load();
                me.getLocalCategoryTree().getStore().getRootNode().removeAll();
                me.getLocalCategoryTree().getStore().load();
            },
            failure: function(response, opts) {
                me.createGrowlMessage('{s name=error}Error{/s}', 'error');
            }
        });
    },

    onImportRemoteCategoryButtonClick: function() {
        var me = this;

        var remoteCategoryTreeSelection = me.getRemoteCategoryTree().getSelectionModel().getSelection();
        if (remoteCategoryTreeSelection.length == 0) {
            console.log('please select remote category');
            //todo: show message
            return;
        }

        var localCategoryTreeSelection = me.getLocalCategoryTree().getSelectionModel().getSelection();
        if (localCategoryTreeSelection.length == 0) {
            console.log('please select local category');
            //todo: show message
            return;
        }

        var remoteCategoryKey = remoteCategoryTreeSelection[0].get('id');
        var remoteCategoryLabel = remoteCategoryTreeSelection[0].get('text');
        var localCategoryId = localCategoryTreeSelection[0].get('id');

        me.importRemoteToLocalCategories(remoteCategoryKey, remoteCategoryLabel, localCategoryId);
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
    },

    onFilterLocalProducts: function(checkbox, newValue, oldValue) {
        var me = this;
        var store = me.getLocalProductsGrid().getStore();

        if (newValue == true) {
            Ext.apply(store.getProxy().extraParams, {
                hideConnectArticles: 1
            });
        } else {
            Ext.apply(store.getProxy().extraParams, {
                hideConnectArticles: null
            });
        }
        store.loadPage(1);
        store.load();
    }
});
//{/block}