//{namespace name=backend/bepado/view/main}

/**
 * Shopware Controller - Cache backend module
 */
//{block name="backend/bepado/controller/main"}
Ext.define('Shopware.apps.Bepado.controller.Main', {

    extend: 'Enlight.app.Controller',

    stores: [
        'main.Navigation', 'main.Mapping', 'main.Category',
        'export.List', 'import.List'
    ],
    models: [
        'main.Mapping', 'main.Product',
        'export.List', 'import.List'
    ],

    refs: [
        { ref: 'window', selector: 'bepado-window' },
        { ref: 'navigation', selector: 'bepado-navigation' },
        { ref: 'panel', selector: 'bepado-panel' },
        { ref: 'configForm', selector: 'bepado-config' },
        { ref: 'exportList', selector: 'bepado-export-list' },
        { ref: 'importList', selector: 'bepado-import-list' },
        { ref: 'mapping', selector: 'bepado-mapping treepanel' }
    ],

    messages: {
        saveMappingTitle: '{s name=mapping/message/title}Save category mapping{/s}',
        saveMappingSuccess: '{s name=mapping/message/success}Category mapping has been saved.{/s}',
        saveMappingError: '{s name=mapping/message/error}Category mapping could not be saved.{/s}',

        insertOrUpdateProductTitle: '{s name=export/message/import_product_title}Products export{/s}',
        insertOrUpdateProductMessage: '{s name=export/message/import_product_messag}Products were marked for inserting / updating.{/s}',
        deleteProductTitle: '{s name=export/message/delete_title}Products export{/s}',
        deleteProductMessage: '{s name=export/message/delete_message}Products were marked for deleting.{/s}',

        activateProductTitle: '{s name=import/message/activate_title}Products import{/s}',
        activateProductMessage: '{s name=import/message/activate_message}Products have been activated.{/s}',
        disableProductTitle: '{s name=import/message/disable_title}Products import{/s}',
        disableProductMessage: '{s name=import/message/disable_message}Products have been disabled.{/s}',

        applyMappingToChildCategoriesTitle: '{s name=mapping/applyConfirmTitle}Apply to child categories?{/s}',
        applyMappingToChildCategoriesMessage: '{s name=mapping/applyConfirmMessage}Do you want to apply this mapping to all empty child categories? This will immediately save the current mapping, all other unsaved changes will be lost{/s}',

        importBepadoCategoriesTitle: '{s name=mapping/importBepadoCategoriesTitle}Import categories?{/s}',
        importBepadoCategoriesMessage: '{s name=mapping/importBepadoCategoriesMessage}Do you want to import all subcategories of »[0]« to you category »[1]«?{/s}'
    },

    /**
     * Class property which holds the main application if it is created
     *
     * @default null
     * @object
     */
    mainWindow: null,

    /**
     * Init component. Basically will create the app window and register to events
     */
    init: function () {
        var me = this;

        me.mainWindow = me.getView('main.Window').create({

        }).show();

        me.control({
            'bepado-navigation': {
                select: me.onSelectNavigationEntry
            },
            'bepado-config button[action=save]': {
                click: me.onSaveConfigForm
            },
            'bepado-mapping button[action=save]': {
                click: me.onSaveMapping
            },
            'bepado-mapping': {
                applyToChildren: me.onApplyMappingToChildCategories,
                importCategories: me.onImportCategoriesFromBepado
            },
            'bepado-export-list button[action=add]': {
               click: me.onExportFilterAction
            },
            'bepado-export-list button[action=delete]': {
                click: me.onExportFilterAction
            },
            'bepado-export-filter textfield[name=searchfield]': {
                change: function(field, value) {
                    var table = me.getExportList(),
                        store = table.getStore();

                    if (value.length === 0 ) {
                        store.clearFilter();
                    } else {
                        store.filters.clear();
                        store.filter(
                            'search',
                            '%' + value + '%'
                        );
                    }
                }
            },
            'bepado-export-filter base-element-select': {
                change: function(field, value) {
                    var table = me.getExportList(),
                        store = table.getStore();

                    if (!value) {
                        store.clearFilter();
                    } else {
                        store.filters.clear();
                        store.filter(
                            field.name,
                            value
                        );
                    }
                }
            },
            'bepado-export-filter treepanel': {
                select: function(tree, node) {
                    var table = me.getExportList(),
                        store = table.getStore();

                    if (!node) {
                        store.clearFilter();
                    } else {
                        store.filters.clear();
                        store.filter(
                            'categoryId',
                            node.get('id')
                        );
                    }
                }
            },
            'bepado-import-list button[action=activate]': {
                click: me.onImportFilterAction
            },
            'bepado-import-list button[action=deactivate]': {
                click: me.onImportFilterAction
            },
            'bepado-import-filter textfield[name=searchfield]': {
                change: function(field, value) {
                    var table = me.getImportList(),
                        store = table.getStore();

                    if (value.length === 0 ) {
                        store.clearFilter();
                    } else {
                        store.filters.clear();
                        store.filter(
                            'search',
                            '%' + value + '%'
                        );
                    }
                }
            },
            'bepado-import-filter base-element-select': {
                change: function(field, value) {
                    var table = me.getImportList(),
                        store = table.getStore();

                    if (!value) {
                        store.clearFilter();
                    } else {
                        store.filters.clear();
                        store.filter(
                            field.name,
                            value
                        );
                    }
                }
            },
            'bepado-import-filter [name=active]': {
                change: function(field, value) {
                    var table = me.getImportList(),
                        store = table.getStore();
                    if(!value) {
                        return;
                    }

                    if(field.inputValue == '') {
                        store.clearFilter();
                    } else {
                        store.filters.clear();
                        store.filter(
                            field.name,
                            field.inputValue
                        );
                    }
                }
            },
            'bepado-import-filter treepanel': {
                select: function(tree, node) {
                    var table = me.getImportList(),
                        store = table.getStore();

                    if (!node) {
                        store.clearFilter();
                    } else {
                        store.filters.clear();
                        store.filter(
                            'categoryId',
                            node.get('id')
                        );
                    }
                }
            }
        });

        me.callParent(arguments);
    },

    /**
     * Callback function that will create the bepado categories in the selected category
     *
     * @param record
     */
    onImportCategoriesFromBepado: function(record) {
        var me = this,
            panel = me.getMapping(),
            store = panel.store;

        Ext.MessageBox.confirm(
            me.messages.importBepadoCategoriesTitle,
            Ext.String.format(me.messages.importBepadoCategoriesMessage, record.get('mapping'), record.get('name')),
            function (response) {
                if ( response !== 'yes' ) {
                    return;
                }

                panel.setLoading();
                Ext.Ajax.request({
                    url: '{url action=importBepadoCategories}',
                    method: 'POST',
                    params: {
                        fromCategory: record.get('mapping'),
                        toCategory: record.get('id')
                    },
                    success: function(response, opts) {
                        me.createGrowlMessage(me.messages.saveMappingTitle, me.messages.saveMappingSuccess);
                        panel.setLoading(false);
                        store.load();
                    },
                    failure: function(response, opts) {
                        me.createGrowlMessage(me.messages.saveMappingError, response.responseText);
                        panel.setLoading(false);
                    }

                });

            }
        );
    },

    /**
     * Callback function that will apply the current mapping to all child mappings
     *
     * @param record
     */
    onApplyMappingToChildCategories: function(record) {
        var me = this,
            panel = me.getMapping(),
            store = panel.store;

        // No message needed, if there aren't any child nodes
        if (record.get('childrenCount') == 0) {
            return;
        }

        Ext.MessageBox.confirm(
            me.messages.applyMappingToChildCategoriesTitle,
            me.messages.applyMappingToChildCategoriesMessage,
            function (response) {
                if ( response !== 'yes' ) {
                    return;
                }

                panel.setLoading();
                Ext.Ajax.request({
                    url: '{url action=applyMappingToChildren}',
                    method: 'POST',
                    params: {
                        category: record.get('id'),
                        mapping: record.get('mapping')
                    },
                    success: function(response, opts) {
                        me.createGrowlMessage(me.messages.saveMappingTitle, me.messages.saveMappingSuccess);
                        panel.setLoading(false);
                        store.load();
                    },
                    failure: function(response, opts) {
                        me.createGrowlMessage(me.messages.saveMappingError, response.responseText);
                        panel.setLoading(false);
                    }

                });

            }
        );
    },

    /**
     * Callback function that will save the current mapping
     *
     * @param button
     */
    onSaveMapping: function(button) {
        var me = this,
            panel = me.getMapping(),
            title = me.messages.saveMappingTitle, message;
        if(panel.store.getUpdatedRecords().length < 1) {
            return;
        }
        panel.setLoading();
        panel.store.sync({
            success :function (records, operation) {
                panel.setLoading(false);
                message = me.messages.saveMappingSuccess;
                me.createGrowlMessage(title, message);
            },
            failure:function (batch) {
                panel.setLoading(false);
                message = me.messages.saveMappingError;
                if(batch.proxy.reader.rawData.message) {
                    message += '<br />' + batch.proxy.reader.rawData.message;
                }
                me.createGrowlMessage(title, message);
            }
        });
    },

    /**
     * Callback function that will insert or delete a product from/for export
     *
     * @param btn
     */
    onExportFilterAction: function(btn) {
        var me = this,
            list = me.getExportList(),
            records = list.selModel.getSelection(),
            ids = [], url, message, title;

        if(btn.action == 'add') {
            url = '{url action=insertOrUpdateProduct}';
            title = me.messages.insertOrUpdateProductTitle;
            message = me.messages.insertOrUpdateProductMessage;
        } else if(btn.action == 'delete') {
            url = '{url action=deleteProduct}';
            title = me.messages.deleteProductTitle;
            message = me.messages.deleteProductMessage;
        } else {
            return;
        }

        Ext.each(records, function(record) {
            ids.push(record.get('id'));
        });

        list.setLoading();
        Ext.Ajax.request({
            url: url,
            method: 'POST',
            params: {
                'ids[]': ids
            },
            success: function(response, opts) {
                //var operation = Ext.decode(response.responseText);
                //if (operation.success == true) {
                //}
                me.createGrowlMessage(title, message);
                list.setLoading(false);
                list.store.load();
            }
        });
    },

    /**
     * Callback function that will activate or disable a product for import
     *
     * @param btn
     */
    onImportFilterAction: function(btn) {
        var me = this,
            list = me.getImportList(),
            records = list.selModel.getSelection(),
            ids = [], url, message, title;

        if(btn.action == 'activate') {
            url = '{url action=updateProduct}?active=1';
            title = me.messages.activateProductTitle;
            message = me.messages.activateProductMessage;
        } else if(btn.action == 'deactivate') {
            url = '{url action=updateProduct}?active=0';
            title = me.messages.disableProductTitle;
            message = me.messages.disableProductMessage;
        } else {
            return;
        }

        Ext.each(records, function(record) {
            ids.push(record.get('id'));
        });

        list.setLoading();
        Ext.Ajax.request({
            url: url,
            method: 'POST',
            params: {
                'ids[]': ids
            },
            success: function(response, opts) {
                //var operation = Ext.decode(response.responseText);
                //if (operation.success == true) {
                //}
                me.createGrowlMessage(title, message);
                list.setLoading(false);
                list.store.load();
            }
        });
    },

    /**
     * Helper to show a growl message
     *
     * @param title
     * @param message
     */
    createGrowlMessage: function(title, message) {
        var me = this,
            win = me.getWindow();
        Shopware.Notification.createGrowlMessage(title, message, win.title);
    },

    /**
     * Callback function to set the window title depending on the current navigation entry
     *
     * @param tree
     * @param node
     */
    onSelectNavigationEntry: function(tree, node) {
        var me = this,
            panel = me.getPanel(),
            win = me.getWindow(),
            item = node.get('id'),
            layout = panel.getLayout();

        layout.setActiveItem(item);
        win.loadTitle(node);
    },

    /**
     * Callback function to save the configuration form
     *
     * @param button
     */
    onSaveConfigForm: function(button) {
        var me = this,
            form = me.getConfigForm();
        form.setLoading();
        form.onSaveForm(form, false, function() {
            form.setLoading(false);
        });
    }
});
//{/block}
