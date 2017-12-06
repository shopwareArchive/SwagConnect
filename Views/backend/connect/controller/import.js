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
        { ref: 'remoteCategoryTree', selector: 'connect-remote-categories' }
    ],

    snippets: {
        messages: {
            removeArticleTitle: '{s name=import/message/remove_article_title}Remove selected product?{/s}',
            removeArticle: '{s name=import/message/remove_article}Are you sure you want to remove this product?{/s}',
            recreateRemoteCategoriesTitle: '{s name=import/tree/update_remote_categories}Re-create categories{/s}',
            recreateRemoteCategoriesConfirmMessage: '{s name=import/message/recreate_remote_categories}Are you sure you want to re-create remote categories? This will deactivate all auto created remote categories and unassign their products.{/s}',
            deactivatedCategoriesSuccess: '{s name=deactivated_categories/success/message}[0] categories deactivated{/s}'
        },
        categoryProgress: '[0] of [1] categories assigned',
        articleProgress: '[0] of [1] articles assigned'
    },

    /**
     * Init component. Basically will create the app window and register to events
     */
    init: function () {
        var me = this;

        me.control({
            'connect-remote-categories': {
                reloadRemoteCategories: me.onReloadRemoteCategories,
                beforeload: me.onBeforeReloadRemoteCategories,
                itemmousedown: me.onSelectRemoteCategory
            },
            'connect-remote-categories dataview': {
                drop: me.onDropToRemoteCategory
            },
            'connect-own-categories': {
                reloadOwnCategories: me.onReloadOwnCategories,
                itemmousedown: me.onSelectLocalCategory
            },
            'connect-own-categories button[action=deactivateCategory]': {
                click: me.onDeactivateCategoy
            },
            'connect-own-categories dataview': {
                drop: me.onDropToLocalCategory
            },
            'local-products dataview': {
                beforedrop: me.onBeforeDropLocalProduct,
                drop: me.onDropToLocalProducts
            },
            'connect-products dataview': {
                drop: me.onDropToRemoteProducts,
                beforedrop: me.onBeforeDropRemoteProducts
            },
            'connect-import button[action=importRemoteCategory]': {
                click: me.onImportRemoteCategoryButtonClick
            },
            'connect-import button[action=recreateRemoteCategories]': {
                click: me.onRecreateRemoteCategoryButtonClick
            },
            'connect-import button[action=unassignRemoteCategory]': {
                click: me.onUnassignRemoteCategoryButtonClick
            },
            'connect-import button[action=assignArticlesToCategory]': {
                click: me.onAssignArticlesToCategory
            },
            'connect-import button[action=unAssignArticlesFromCategory]': {
                click: me.onUnAssignArticlesFromCategory
            },
            'connect-import button[action=activateProducts]': {
                click: me.onActivateProducts
            },
            'connect-products': {
                reloadRemoteCategories: me.onReloadRemoteCategories
            },
            'local-products': {
                reloadOwnCategories: me.onReloadOwnCategories,
                deleteProduct: me.onUnAssignArticleFromCategory
            },
            'connect-import checkbox[action=show-only-connect-products]': {
                change: me.showOnlyConnectProducts
            },
            'connect-import checkbox[action=hide-mapped-products]': {
                change: me.hideMappedProducts
            },
            'connect-import textfield[action=search-local-products]': {
                change: me.searchLocalProducts
            },
            'connect-import textfield[action=search-remote-products]': {
                change: me.searchRemoteProducts
            },
            'connect-import checkbox[action=hide-mapped-categories]': {
                change: me.hideMappedCategories
            },
            'connect-import textfield[action=search-remote-categories]': {
                change: me.searchRemoteCategories
            },
            'connect-import textfield[action=search-local-categories]': {
                change: me.searchLocalCategories
            }
        });

        me.callParent(arguments);
    },

    onBeforeReloadRemoteCategories: function( remoteCategoryStore, operation) {
        var root = 'root';

        if (operation.id == root) {
            remoteCategoryStore.getProxy().extraParams.categoryId = root;
        } else {
            remoteCategoryStore.getProxy().extraParams.categoryId = operation.node.get('categoryId');
        }
    },

    /**
     * When remote category is clicked set params to
     * remote products store and load it. Products are visible
     * in remote products grid.
     *
     * @param treePanel
     * @param record
     */
    onSelectRemoteCategory: function(treePanel, record) {
        var me = this,
            localTreeView = me.getLocalCategoryTree().getView(),
            stream = me.getStreamByNode(record),
            mainCategory = me.getMainCategoryByNode(record);

        me.resetTreeViewStyle(localTreeView);

        var style = 'color: #bbbbbb !important';
        me.modifyTree(record, localTreeView, style);

        var remoteProductsStore = me.getRemoteProductsGrid().getStore();
        remoteProductsStore.getProxy().extraParams.shopId = mainCategory.get('categoryId');

        if (stream && stream.get('categoryId') != record.get('categoryId')) {
            remoteProductsStore.getProxy().extraParams.stream = stream.get('text');
        }

        if (mainCategory.get('categoryId') != record.get('categoryId')) {
            remoteProductsStore.getProxy().extraParams.category = record.get('categoryId');
        } else {
            remoteProductsStore.getProxy().extraParams.category = null;
        }

        remoteProductsStore.loadPage(1);
    },

    /**
     * Find main category
     *
     * @param node
     * @returns node
     */
    getMainCategoryByNode: function(node) {
        var me = this;

        if (node.parentNode.get('id') == 'root') {
            return node;
        }

        return me.getMainCategoryByNode(node.parentNode);
    },

    /**
     * Find stream name
     *
     * @param node
     * @returns node
     */
    getStreamByNode: function(node) {
        var me = this;

        if (!node) {
            return null;
        }

        if (node.get('categoryId').indexOf('_stream_') > 0) {
            return node;
        }

        return me.getStreamByNode(node.parentNode);
    },

    onSelectLocalCategory: function(treePanel, record) {
        var me = this;
        var store = me.getLocalProductsGrid().getStore();
        var showOnlyConnectArticles = me.getImportPanel().down('checkbox[action=show-only-connect-products]').getValue();

        if (showOnlyConnectArticles === true) {
            store.getProxy().extraParams.showOnlyConnectArticles = showOnlyConnectArticles;
        }
        store.getProxy().extraParams.categoryId = record.get('id');
        store.loadPage(1);
    },

    onDeactivateCategoy: function () {
        var me = this;
        var selected = me.getLocalCategoryTree().getSelectionModel().getSelection();

        if (selected.length == 0) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_local_category}Please select category from your shop{/s}');
            return;
        }

        Ext.Ajax.request({
            url: '{url controller=Import action=deactivateCategory}',
            method: 'POST',
            params: {
                categoryId: selected[0].get('id')
            },
            success: function (response, opts) {
                var data = Ext.JSON.decode(response.responseText);
                if (data.success == true) {
                    var successMessage = Ext.String.format(me.snippets.messages.deactivatedCategoriesSuccess, data.deactivatedCategoriesCount);
                    me.createGrowlMessage('{s name=connect/success}Success{/s}', successMessage);
                    me.getLocalCategoryTree().getStore().load({ node: selected[0].parentNode });
                } else {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', data.message);
                }
            },
            failure: function (response, opts) {
                me.createGrowlMessage('{s name=connect/error}Error{/s}', 'error');
            }
        });

        me.getLocalCategoryTree().getSelectionModel().deselectAll();
    },

    onBeforeDropLocalProduct: function(node, data, overModel, dropPosition, dropHandlers) {
        var me = this;
        var selected = me.getLocalCategoryTree().getSelectionModel().getSelection();
        if (selected.length > 0) {
            dropHandlers.processDrop();
        } else {
            dropHandlers.cancelDrop();
        }
    },

    /**
     * Check products before drop,
     * they should be only remote products
     */
    onBeforeDropRemoteProducts: function(node, data, overModel, dropPosition, dropHandlers) {
        var me = this;

        for (var index in data.records) {
            if (data.records[index].get('Attribute_connectMappedCategory') == 0) {
                dropHandlers.cancelDrop();
                me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/not_allowed_drag_local_products}Use drag&drop only on remote products{/s}');
                break;
            }
        }
    },

    onDropToLocalProducts: function(node, data, overModel, dropPosition, eOpts) {
        var me = this;
        var articleIds = [];

        for (var i = 0; i < data.records.length; i++) {
            articleIds.push(data.records[i].get('Article_id'));
        }

        me.assignArticlesToCategory(articleIds);
    },

    onAssignArticlesToCategory: function() {
        var me = this;
        var articleIds = [];
        var remoteProductSelection = me.getRemoteProductsGrid().getSelectionModel().getSelection();

        for (var i = 0; i < remoteProductSelection.length; i++) {
            articleIds.push(remoteProductSelection[i].get('Article_id'));
        }

        me.assignArticlesToCategory(articleIds, true);
    },

    assignArticlesToCategory: function(articleIds, reload) {
        var me = this;

        if (articleIds.length == 0) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_articles}Please select at least one article{/s}');
            return;
        }

        var selected = me.getLocalCategoryTree().getSelectionModel().getSelection();

        if (selected.length == 0) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_local_category}Please select category from your shop{/s}');
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
                    me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=changed_products/success/notification/message}Successfully applied changes{/s}');
                    if (reload === true) {
                        me.getRemoteProductsGrid().getStore().reload();
                        me.getLocalProductsGrid().getStore().reload();
                    }

                } else {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', data.message);
                }

                me.getRemoteProductsGrid().getStore().load();
                me.getLocalProductsGrid().getStore().load();
            },
            failure: function(response, opts) {
                me.createGrowlMessage('{s name=connect/error}Error{/s}', 'error');
            }

        });
    },

    /**
     * Handle drop product to remote products grid
     */
    onDropToRemoteProducts: function(node, data, overModel, dropPosition, eOpts) {
        var me = this;
        var ids = [];

        for (var index in data.records) {
            ids.push(data.records[index].get('Article_id'));
        }

        me.unAssignArticlesFromCategory(ids);
    },

    onUnAssignArticlesFromCategory: function() {
        var me = this;
        var articleIds = [];
        var categoryId = me.getLocalCategoryTree().getSelectionModel().getSelection()[0].get('id');
        var localProductSelection = me.getLocalProductsGrid().getSelectionModel().getSelection();

        for (var i = 0; i < localProductSelection.length; i++) {
            articleIds.push(localProductSelection[i].get('Article_id'));
        }

        me.unAssignArticlesFromCategory(articleIds, categoryId, true);
    },

    onUnAssignArticleFromCategory: function(record) {
        var me = this;

        Ext.MessageBox.confirm(me.snippets.messages.removeArticleTitle, me.snippets.messages.removeArticle, function (response) {
            if (response !== 'yes') {
                return false;
            }
            var categoryId = me.getLocalCategoryTree().getSelectionModel().getSelection()[0].get('id');
            me.unAssignArticlesFromCategory([record.get('Article_id')], categoryId, true);
        });
    },

    unAssignArticlesFromCategory: function(articleIds, categoryId, reload) {
        var me = this;
        var panel = me.getImportPanel();

        if (articleIds.length == 0) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_articles}Please select at least one article{/s}');
            return;
        }

        panel.setLoading(false);
        Ext.Ajax.request({
            url: '{url controller=Import action=unassignRemoteArticlesFromLocalCategory}',
            method: 'POST',
            params: {
                'articleIds[]': articleIds,
                'categoryId': categoryId
            },
            success: function(response, opts) {
                panel.setLoading(false);
                var data = Ext.JSON.decode(response.responseText);
                if (data.success == true) {
                    me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=changed_products/success/notification/message}Successfully applied changes{/s}');
                    if (reload === true) {
                        me.getRemoteProductsGrid().getStore().reload();
                        me.getLocalProductsGrid().getStore().reload();
                    }

                } else {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
                }
            },
            failure: function(response, opts) {
                panel.setLoading(false);
                me.createGrowlMessage('{s name=connect/error}Error{/s}', 'error');
            }

        });
    },

    onDropToLocalCategory: function(node, data, overModel, dropPosition, eOpts) {
        var me = this;

        if (!data.records) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_remote_category}Please select Shopware Connect category{/s}');
            return;
        }

        if (!overModel) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_local_category}Please select category from your shop{/s}');
            return;
        }

        me.importRemoteToLocalCategories(
            data.records[0].categoryId,
            data.records[0].text,
            overModel.get('id'),
            data.records[0].id
        );
    },

    /**
     * Get selected local category and unassign all remote articles from it
     */
    onDropToRemoteCategory: function(node, data, overModel, dropPosition, eOpts) {
        var me = this;
        var localCategoryId = data.records[0].get('id');

        me.unassignRemoteToLocalCategories(localCategoryId);
    },

    importRemoteToLocalCategories: function(remoteCategoryKey, remoteCategoryLabel, localCategoryId, node) {
        var me = this;
        var panel = me.getImportPanel();

        panel.setLoading();
        Ext.Ajax.request({
            url: '{url controller=Import action=assignRemoteToLocalCategory}',
            method: 'POST',
            params: {
                remoteCategoryKey: remoteCategoryKey,
                remoteCategoryLabel: remoteCategoryLabel,
                node: node,
                localCategoryId: localCategoryId
            },
            success: function(response, opts) {
                panel.setLoading(false);
                var data = Ext.JSON.decode(response.responseText);
                if (data.success == true) {
                    me.assignArticlesToCategories(data.categories, data.shopId, data.stream);
                } else {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
                }

                // get all currently expanded nodes and reload the tree with them being expanded
                var expandedCategories = [];
                me.getLocalCategoryTree().getStore().getRootNode().cascade(function (n) {
                    if (n.data.expanded) {
                        expandedCategories.push(n.data.id);
                    }
                });
                me.reloadAndExpandLocalCategories(expandedCategories);

            },
            failure: function(response, opts) {
                panel.setLoading(false);
                me.createGrowlMessage('{s name=connect/error}Error{/s}', 'error');
            }
        });
    },

    assignArticlesToCategories: function(categories, shopId, stream) {
        var me = this;
        var shopId = shopId;
        var categoriesCount = categories.length;

        me.progressWindow = Ext.create('Shopware.apps.Connect.view.import.Progress', {
            categoriesCount: categoriesCount
        }).show();

        categories.forEach(function (item, index) {
            Ext.Ajax.request({
                url: '{url controller=Import action=getArticleCountForRemoteCategory}',
                method: 'GET',
                params: {
                    remoteCategoryKey: item.remoteCategory,
                    shopId: shopId,
                    stream: stream
                },
                success: function(response, opts) {
                    var data = Ext.JSON.decode(response.responseText);
                    if (data.success == true) {
                        var window = me.progressWindow;
                        window.progressFieldArticles.updateText(Ext.String.format(window.snippets.processArticles, 0, data.count));
                        me.assignArticlesBatch(window, item, shopId, stream, data.count, 0, 200);
                    } else {
                        me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
                    }
                },
                failure: function(response, opts) {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', 'error');
                }
            });
            var window = me.progressWindow;
            window.progressFieldCategories.updateText(Ext.String.format(window.snippets.processCategories, index + 1, categoriesCount));
            window.progressFieldCategories.updateProgress((index + 1)/categoriesCount);
        });

        me.progressWindow.closeWindow();
    },

    assignArticlesBatch: function(window, category, shopId, stream, articleCount, offset, batchsize) {
        Ext.Ajax.request({
            url: '{url controller=Import action=assignArticlesToRemoteCategory}',
            method: 'POST',
            params: {
                remoteCategoryKey: category.remoteCategory,
                shopId: shopId,
                localCategoryId: category.categoryKey,
                localParentId: category.parentId,
                offset: offset,
                limit: batchsize,
                stream: stream
            },
            success: function(response, opts) {
                var data = Ext.JSON.decode(response.responseText);
                if (data.success == true) {
                    offset = offset + batchsize;
                    window.progressFieldArticles.updateText(Ext.String.format(window.snippets.processArticles, offset, articleCount));
                    window.progressFieldArticles.updateProgress(offset/articleCount);
                    if (offset < articleCount) {
                        me.assignArticlesBatch(window, category, shopId, stream, articleCount, offset, batchsize);
                    }
                } else {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
                }
            },
            failure: function(response, opts) {
                me.createGrowlMessage('{s name=connect/error}Error{/s}', 'error');
            }
        });
    },

    /**
     * Send ajax request to unassign all remote articles from given category id
     */
    unassignRemoteToLocalCategories: function(localCategoryId) {
        var me = this;
        var panel = me.getImportPanel();

        panel.setLoading();
        Ext.Ajax.request({
            url: '{url controller=Import action=unassignRemoteToLocalCategory}',
            method: 'POST',
            params: {
                localCategoryId: localCategoryId
            },
            success: function(response, opts) {
                panel.setLoading(false);
                var data = Ext.JSON.decode(response.responseText);
                if (data.success == true) {
                    me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=changed_products/success/notification/message}Successfully applied changes{/s}');
                } else {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
                }

                me.getRemoteCategoryTree().getStore().getRootNode().removeAll();
                me.getRemoteCategoryTree().getStore().load();
                me.getLocalCategoryTree().getStore().getRootNode().removeAll();
                me.getLocalCategoryTree().getStore().load();

                me.getLocalProductsGrid().getStore().loadPage(1);
            },
            failure: function(response, opts) {
                panel.setLoading(false);
                me.createGrowlMessage('{s name=connect/error}Error{/s}', 'error');
            }
        });
    },

    onImportRemoteCategoryButtonClick: function() {
        var me = this;

        var remoteCategoryTreeSelection = me.getRemoteCategoryTree().getSelectionModel().getSelection();
        if (remoteCategoryTreeSelection.length == 0) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_remote_category}Please select Shopware Connect category{/s}');
            return;
        }

        var localCategoryTreeSelection = me.getLocalCategoryTree().getSelectionModel().getSelection();
        if (localCategoryTreeSelection.length == 0) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_local_category}Please select category from your shop{/s}');
            return;
        }

        if (!me.isNodeValidForAssignment(remoteCategoryTreeSelection[0], localCategoryTreeSelection[0])) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/invalid_assign_category}Invalid category for assignment{/s}');
            return;
        }

        me.importRemoteToLocalCategories(
            remoteCategoryTreeSelection[0].get('categoryId'),
            remoteCategoryTreeSelection[0].get('text'),
            localCategoryTreeSelection[0].get('id'),
            remoteCategoryTreeSelection[0].get('id')
        );
    },

    onRecreateRemoteCategoryButtonClick: function() {
        var me = this;

        Ext.MessageBox.confirm(
            me.snippets.messages.recreateRemoteCategoriesTitle,
            me.snippets.messages.recreateRemoteCategoriesConfirmMessage,
            function (response) {
                if (response !== 'yes') {
                    return false;
                }

                var panel = me.getImportPanel();
                panel.setLoading();
                Ext.Ajax.request({
                    url: '{url controller=Import action=recreateRemoteCategories}',
                    method: 'POST',
                    success: function (response, opts) {
                        panel.setLoading(false);
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success == true) {
                            me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=changed_products/success/notification/message}Successfully applied changes{/s}');
                        } else {
                            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
                        }

                        me.onReloadRemoteCategories();
                        me.onReloadOwnCategories();
                        me.clearLocalProducts();
                        me.clearRemoteProducts();
                    },
                    failure: function (response, opts) {
                        panel.setLoading(false);
                        me.createGrowlMessage('{s name=connect/error}Error{/s}', 'error');
                    }
                });
            });
    },

    onUnassignRemoteCategoryButtonClick: function () {
        var me = this;

        var localCategoryTreeSelection = me.getLocalCategoryTree().getSelectionModel().getSelection();
        if (localCategoryTreeSelection.length == 0) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_local_category}Please select category from your shop{/s}');
            return;
        }

        var localCategoryId = localCategoryTreeSelection[0].get('id');

        me.unassignRemoteToLocalCategories(localCategoryId);
    },

    /**
     * @param selectedNodeRecord
     * @param targetNodeRecord
     * @returns boolean
     */
    isNodeValidForAssignment: function(selectedNodeRecord, targetNodeRecord) {
        var me = this;

        //its minus three, cause we have contact, stream node and language node (deutsch, english)
        var draggedDepth = me.getDepth(selectedNodeRecord) - 3;
        var droppedDepth = me.getDepth(targetNodeRecord);

        //stream or main (Deutsch/English) categories cant be dragged
        if (draggedDepth <= 0) {
            return false;
        }

        //dragged node can be drop everywhere except if the target node is not leaf
        return !me.isLeaf(targetNodeRecord);
    },

    isLeaf: function(record) {
        return record.data.leaf;
    },

    getDepth: function(record) {
        return record.data.depth;
    },

    resetTreeViewStyle: function(treeView) {
        var i, targetNodeRecord,
            nodes = treeView.getNodes();

        for (i = 0; i < nodes.length; i++){
            targetNodeRecord = treeView.getRecord(nodes[i]);
            nodes[i].style = '';
        }
    },

    /**
     * @param selectedNodeRecord
     * @param tree
     * @param stl
     */
    modifyTree: function(selectedNodeRecord, treeView, stl) {
        var me = this,
            i, targetNodeRecord,
            nodes = treeView.getNodes();

        for (i = 0; i < nodes.length; i++){
            targetNodeRecord = treeView.getRecord(nodes[i]);

            if (!me.isNodeValidForAssignment(selectedNodeRecord, targetNodeRecord)) {
                nodes[i].style = stl;
            }
        }
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

    showOnlyConnectProducts: function(checkbox, newValue, oldValue) {
        var me = this;
        var store = me.getLocalProductsGrid().getStore();

        if (me.isLocalCategorySelected() === false) {
            return;
        }

        if (newValue == true) {
            Ext.apply(store.getProxy().extraParams, {
                showOnlyConnectArticles: 1
            });
        } else {
            Ext.apply(store.getProxy().extraParams, {
                showOnlyConnectArticles: null
            });
        }
        store.loadPage(1);
    },

    reloadAndExpandLocalCategories: function (expandedCategories) {
        var me = this;
        var store = me.getLocalCategoryTree().getStore();

        Ext.apply(store.getProxy().extraParams, {
            'expandedCategories[]': expandedCategories
        });

        store.getRootNode().removeAll();
        store.load();
    },

    hideMappedProducts: function(checkbox, newValue, oldValue) {
        var me = this,
            store = me.getRemoteProductsGrid().getStore();

        if (newValue == true) {
            Ext.apply(store.getProxy().extraParams, {
                hideMappedProducts: 1
            });
        } else {
            Ext.apply(store.getProxy().extraParams, {
                hideMappedProducts: null
            });
        }
        store.loadPage(1);
    },

    hideMappedCategories: function(checkbox, newValue, oldValue) {
        var me = this,
            categoryStore = me.getRemoteCategoryTree().getStore();

        if (newValue == true) {
            Ext.apply(categoryStore.getProxy().extraParams, {
                hideMappedProducts: 1
            });
        } else {
            Ext.apply(categoryStore.getProxy().extraParams, {
                hideMappedProducts: null
            });
        }
        me.onReloadRemoteCategories();
    },

    onActivateProducts: function(button, event) {
        var me = this;
        var selection = me.getLocalProductsGrid().getSelectionModel().getSelection();
        var articleIds = [];
        for (var i = 0;i < selection.length; i++) {
            articleIds.push(selection[i].get('Article_id'));
        }

        if (articleIds.length == 0) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=import/select_articles}Please select at least one article{/s}');
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
                    me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=changed_products/success/notification/message}Successfully applied changes{/s}');
                } else {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=changed_products/failure/message}Changes are not applied{/s}');
                }
                me.getLocalProductsGrid().getStore().load();
            },
            failure: function(response, opts) {
                panel.setLoading(false);
                me.createGrowlMessage('{s name=connect/error}Error{/s}', 'error');
            }
        });
    },

    /**
     *  Refreshes the tree.
     *
     *  @event reload
     *  @return void
     */
    onReloadRemoteCategories : function() {
        var tree = this.getRemoteCategoryTree(),
            store = tree.getStore(),
            rootNode = tree.getRootNode();

        rootNode.removeAll(false);
        tree.setLoading(true);
        store.load({
            callback: function() {
                tree.setLoading(false);
            }
        });
    },

    /**
     *  Refreshes the tree.
     *
     *  @event reload
     *  @return void
     */
    onReloadOwnCategories : function() {
        var tree = this.getLocalCategoryTree(),
            store = tree.getStore(),
            rootNode = tree.getRootNode();

        rootNode.removeAll(false);
        tree.setLoading(true);

        //reset the expanded categories on refresh
        Ext.apply(store.getProxy().extraParams, {
            'expandedCategories[]': []
        });

        store.load({
            callback: function() {
                tree.setLoading(false);
            }
        });
    },

    searchLocalProducts: function (textField, newValue) {
        var me = this;
        var store = me.getLocalProductsGrid().getStore();

        Ext.apply(store.getProxy().extraParams, {
            localArticlesQuery: newValue
        });

        store.loadPage(1);
    },

    searchRemoteProducts: function (textField, newValue) {
        var me = this;
        var store = me.getRemoteProductsGrid().getStore();

        Ext.apply(store.getProxy().extraParams, {
            remoteArticlesQuery: newValue
        });

        store.loadPage(1);
    },

    searchRemoteCategories: function (textField, newValue) {

        if (newValue.length != 0 && newValue.length < 3) {
            return;
        }

        var me = this,
            treeView = me.getRemoteCategoryTree().getView(),
            store = me.getRemoteCategoryTree().getStore();

        me.resetTreeViewStyle(treeView);

        Ext.apply(store.getProxy().extraParams, {
            remoteCategoriesQuery: newValue
        });

        me.onReloadRemoteCategories();
    },

    searchLocalCategories: function (textField, newValue) {

        if (newValue.length != 0 && newValue.length < 3) {
            return;
        }

        var me = this,
            treeView = me.getLocalCategoryTree().getView(),
            store = me.getLocalCategoryTree().getStore();

        me.resetTreeViewStyle(treeView);

        Ext.apply(store.getProxy().extraParams, {
            localCategoriesQuery: newValue
        });

        store.getRootNode().removeAll();
        store.load();
    },

    clearRemoteProducts: function () {
        var me = this;
        var store = me.getRemoteProductsGrid().getStore();

        store.getProxy().extraParams = [];
        store.loadPage(1);
    },

    clearLocalProducts: function () {
        var me = this;
        var store = me.getLocalProductsGrid().getStore();

        store.getProxy().extraParams = [];
        store.loadPage(1);
    }
});
//{/block}