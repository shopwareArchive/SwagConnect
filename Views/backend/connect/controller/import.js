//{namespace name=backend/connect/view/main}

/**
 * Shopware Controller - Cache backend module
 */
//{block name="backend/connect/controller/import"}
Ext.define('Shopware.apps.Connect.controller.Import', {

    extend: 'Enlight.app.Controller',

    stores: [
        'import.RemoteProducts', 'import.LocalProducts'
    ],

    refs: [
        { ref: 'window', selector: 'connect-window' },
        { ref: 'importPanel', selector: 'connect-import' },
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
            'connect-import button[action=importRemoteCategory]': {
                click: me.onImportRemoteCategoryButtonClick
            },
            'connect-import button[action=activateProducts]': {
                click: me.onActivateProducts
            },
            'connect-import checkbox[action=filter-only-local-products]': {
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
            me.createGrowlMessage('{s name=error}Error{/s}', '{s name=import/select_articles}Please select at least one article{/s}');
            return;
        }

        var selected = me.getLocalCategoryTree().getSelectionModel().getSelection();

        if (selected.length == 0) {
            me.createGrowlMessage('{s name=error}Error{/s}', '{s name=import/select_local_category}Please select category from your shop{/s}');
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
                var data = Ext.JSON.decode(response.responseText);
                if (data.success == true) {
                    me.createGrowlMessage('{s name=success}Success{/s}', '{s name=changed_products/success/message}Successfully applied changes{/s}');
                } else {
                    me.createGrowlMessage('{s name=error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
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
            me.createGrowlMessage('{s name=error}Error{/s}', '{s name=import/select_remote_category}Please select Shopware Connect category{/s}');
            return;
        }

        if (!overModel) {
            me.createGrowlMessage('{s name=error}Error{/s}', '{s name=import/select_local_category}Please select category from your shop{/s}');
            return;
        }

        var remoteCategoryKey = data.records[0].get('id'),
            remoteCategoryLabel = data.records[0].get('text'),
            localCategoryId = overModel.get('id');

        me.importRemoteToLocalCategories(remoteCategoryKey, remoteCategoryLabel, localCategoryId);
    },

    importRemoteToLocalCategories: function(remoteCategoryKey, remoteCategoryLabel, localCategoryId) {
        var me = this;
        var panel = me.getImportPanel();

        panel.setLoading();
        Ext.Ajax.request({
            url: '{url controller=Import action=assignRemoteToLocalCategory}',
            method: 'POST',
            params: {
                remoteCategoryKey: remoteCategoryKey,
                remoteCategoryLabel: remoteCategoryLabel,
                localCategoryId: localCategoryId
            },
            success: function(response, opts) {
                panel.setLoading(false);
                var data = Ext.JSON.decode(response.responseText);
                if (data.success == true) {
                    me.createGrowlMessage('{s name=success}Success{/s}', '{s name=changed_products/success/message}Successfully applied changes{/s}');
                } else {
                    me.createGrowlMessage('{s name=error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
                }

                me.getRemoteCategoryTree().getStore().getRootNode().removeAll();
                me.getRemoteCategoryTree().getStore().load();
                me.getLocalCategoryTree().getStore().getRootNode().removeAll();
                me.getLocalCategoryTree().getStore().load();
            },
            failure: function(response, opts) {
                panel.setLoading(false);
                me.createGrowlMessage('{s name=error}Error{/s}', 'error');
            }
        });
    },

    onImportRemoteCategoryButtonClick: function() {
        var me = this;

        var remoteCategoryTreeSelection = me.getRemoteCategoryTree().getSelectionModel().getSelection();
        if (remoteCategoryTreeSelection.length == 0) {
            me.createGrowlMessage('{s name=error}Error{/s}', '{s name=import/select_remote_category}Please select Shopware Connect category{/s}');
            return;
        }

        var localCategoryTreeSelection = me.getLocalCategoryTree().getSelectionModel().getSelection();
        if (localCategoryTreeSelection.length == 0) {
            me.createGrowlMessage('{s name=error}Error{/s}', '{s name=import/select_local_category}Please select category from your shop{/s}');
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

    /**
     * Helper function to check
     * if at least one category in local
     * category tree is selected.
     *
     * @returns boolean
     */
    isLocalCategorySelected: function() {
        var me = this;
        var localCategoryTreeSelection = me.getLocalCategoryTree().getSelectionModel().getSelection();

        return localCategoryTreeSelection.length > 0;
    },

    onFilterLocalProducts: function(checkbox, newValue, oldValue) {
        var me = this;
        var store = me.getLocalProductsGrid().getStore();

        if (me.isLocalCategorySelected() === false) {
            me.createGrowlMessage('{s name=error}Error{/s}', '{s name=import/select_local_category}Please select category from your shop{/s}');
            return;
        }

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
    },

    onActivateProducts: function(button, event) {
        var me = this;
        var selection = me.getLocalProductsGrid().getSelectionModel().getSelection();
        var articleIds = [];
        for (var i = 0;i < selection.length; i++) {
            articleIds.push(selection[i].get('Article_id'));
        }

        if (articleIds.length == 0) {
            me.createGrowlMessage('{s name=error}Error{/s}', '{s name=import/select_articles}Please select at least one article{/s}');
            return;
        }

        var panel = me.getImportPanel();
        panel.setLoading();
        Ext.Ajax.request({
            url: '{url controller=Import action=activateArticles}',
            method: 'POST',
            params: {
                'ids[]': articleIds
            },
            success: function(response, opts) {
                panel.setLoading(false);
                var data = Ext.JSON.decode(response.responseText);
                if (data.success == true) {
                    me.createGrowlMessage('{s name=success}Success{/s}', '{s name=changed_products/success/message}Successfully applied changes{/s}');
                } else {
                    me.createGrowlMessage('{s name=error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
                }
                me.getLocalProductsGrid().getStore().load();
            },
            failure: function(response, opts) {
                panel.setLoading(false);
                me.createGrowlMessage('{s name=error}Error{/s}', 'error');
            }
        });
    }
});
//{/block}