//{namespace name=backend/connect/view/main}

/**
 * Shopware Controller - Cache backend module
 */
//{block name="backend/connect/controller/main"}
Ext.define('Shopware.apps.Connect.controller.Main', {

    extend: 'Enlight.app.Controller',

    stores: [
        'main.Navigation',
        'export.List', 'import.List', 'import.RemoteCategories', 'import.RemoteProducts', 'import.LocalProducts',
        'changed_products.List',
        'log.List',
        'mapping.Import', 'mapping.Export',
        'mapping.ConnectCategoriesExport', 'mapping.ConnectCategoriesImport',
        'mapping.GoogleCategories',
		'config.General', 'config.Import', 'config.Export', 'config.CustomerGroup', 'config.PriceGroup',
        'config.Units', 'config.ConnectUnits', 'config.MarketplaceAttributes', 'config.LocalProductAttributes'
    ],
    models: [
        'main.Mapping', 'main.Product',
        'export.List', 'import.List',
        'changed_products.List', 'changed_products.Product', 'log.List',
        'config.General', 'config.Import', 'config.Units', 'config.MarketplaceAttributes',
        'config.ConnectUnit', 'config.Pages', 'config.LocalProductAttributes', 'config.PriceGroup'
    ],

    refs: [
        { ref: 'window', selector: 'connect-window' },
        { ref: 'navigation', selector: 'connect-navigation' },
        { ref: 'panel', selector: 'connect-panel' },
        { ref: 'exportList', selector: 'connect-export-list' },
        { ref: 'exportFilter', selector: 'connect-export-filter' },
        { ref: 'importList', selector: 'connect-import-list' },
        { ref: 'importMapping', selector: 'connect-mapping-import treepanel' },
        { ref: 'exportMapping', selector: 'connect-mapping-export treepanel' },
        { ref: 'changeView', selector: 'connect-changed-products-tabs' },
        { ref: 'changedList', selector: 'connect-changed-products-list' },
        { ref: 'logList', selector: 'connect-log-list' },
        { ref: 'logFilter', selector: 'connect-log-filter' },
        { ref: 'logTabs', selector: 'connect-log-tabs' },
        { ref: 'marketeplaceMappingPanel', selector: 'connect-config-marketplace-attributes' },
        { ref: 'marketeplaceMapping', selector: 'connect-marketplace-attributes-mapping' },
        { ref: 'unitsMapping', selector: 'connect-units-mapping' }
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
        unsubscribeProductTitle: '{s name=import/message/unsubscribe_title}Products unsubscribed{/s}',
        unsubscribeProductMessage: '{s name=import/message/unsubscribe_message}Products have been unsubscribed.{/s}',


        applyMappingToChildCategoriesTitle: '{s name=mapping/applyConfirmTitle}Apply to child categories?{/s}',
        applyMappingToChildCategoriesMessage: '{s name=mapping/applyConfirmMessage}Do you want to apply this mapping to all empty child categories? This will immediately save the current mapping, all other unsaved changes will be lost{/s}',

        updatePartOneMessage: Ext.String.format('{s name=config/message/update_part_one}Update to [0] will take{/s}', marketplaceName),
        updatePartTwoMessage: '{s name=config/message/update_part_two}to finish{/s}',
        doneMessage: '{s name=config/message/done}Done{/s}',

        hours: '{s name=hours}Hour(s){/s}',
        minutes: '{s name=minutes}Minute(s){/s}',
        seconds: '{s name=seconds}Second(s){/s}',

        importConnectCategoriesTitle: '{s name=mapping/importConnectCategoriesTitle}Import categories?{/s}',
        importConnectCategoriesMessage: '{s name=mapping/importConnectCategoriesMessage}Do you want to import all subcategories of »[0]« to you category »[1]«?{/s}',
        importAssignCategoryConfirm: '{s name=import/message/confirm_assign_category}Assign the selected »[0]« products to the category selected below.{/s}'
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
            'connect-navigation': {
                select: me.onSelectNavigationEntry
            },
            'connect-config button[action=save-general-config]': {
                click: me.onSaveConfigForm
            },
            'connect-config-form': {
                calculateFinishTime: me.onCalculateFinishTime
            },
			'connect-config-import-form button[action=save-import-config]': {
                click: me.onSaveImportConfigForm
            },
			'connect-config-export-form button[action=save-export-config]': {
                click: me.onSaveExportConfigForm
            },
			'connect-mapping button[action=save]': {
                click: me.onSaveMapping
            },
            'connect-mapping-import button[action=save]': {
                click: me.onSaveImportMapping
            },
            'connect-mapping-export button[action=save]': {
                click: me.onSaveExportMapping
            },
            'connect-mapping-export': {
                applyToChildren: me.onApplyMappingToChildCategories
            },
            'connect-mapping-import': {
                importCategories: me.onImportCategoriesFromConnect
            },
            'connect-export button[action=add]': {
               click: me.onExportFilterAction
            },
            'connect-export button[action=delete]': {
                click: me.onExportFilterAction
            },
            'connect-export-filter button[action=category-clear-filter]': {
                click: me.onExportCategoryFilterClearAction
            },
            'connect-export-filter textfield[name=searchfield]': {
                change: function(field, value) {
                    var table = me.getExportList(),
                        store = table.getStore();
                        store.filters.removeAtKey('search');
                    if (value.length > 0 ) {
                        store.filters.add('search', new Ext.util.Filter({
                            property: 'search',
                            value: '%' + value + '%'
                        }));
                    }
                    store.load();
                }
            },

            'connect-export-filter base-element-select[name=supplierId]': {
                change: function(field, value) {
                    var table = me.getExportList(),
                        store = table.getStore();

                        store.filters.removeAtKey('supplierId');
                    if (value) {
                        store.filters.add('supplierId', new Ext.util.Filter({
                            property: field.name,
                            value: value
                        }));
                    }
                    store.load();
                }
            },
            'connect-export-filter [name=exportStatus]': {
                change: function(field, value) {
                    var table = me.getExportList(),
                        store = table.getStore();
                    if(!value) {
                        return;
                    }
                    store.filters.removeAtKey('exportStatus');
                    if(field.inputValue != '') {
                        store.filters.add('exportStatus', new Ext.util.Filter({
                            property: field.name,
                            value: field.inputValue
                        }));
                    }
                    store.load();
                }
            },

            'connect-export-filter treepanel': {
                select: function(tree, node) {
                    var table = me.getExportList(),
                        store = table.getStore();

                    if (!node) {
                        store.filters.removeAtKey('exportCategoryFilter');
                    } else {
                        store.filters.removeAtKey('exportCategoryFilter');
                        store.filters.add('exportCategoryFilter', new Ext.util.Filter({
                            property: 'categoryId',
                            value:  node.get('id')
                        }));
                    }
                    store.load();
                }
            },
            'connect-import-list button[action=activate]': {
                click: me.onImportFilterAction
            },
            'connect-import-list button[action=deactivate]': {
                click: me.onImportFilterAction
            },
            'connect-import-list': {
                'unsubscribeAndDelete': me.onImportFilterAction
            },
            'connect-import-list button[action=assignCategory]': {
                click: me.onAssignCategoryAction
            },
            'connect-assign-category-window button[action=save]': {
                click: me.onSaveAssignCategoryAction
            },
            'connect-import-list button[action=unsubscribe]': {
                click: me.onImportFilterAction
            },
            'connect-import-filter textfield[name=searchfield]': {
                change: function(field, value) {
                    var table = me.getImportList(),
                        store = table.getStore();
                    store.filters.removeAtKey('search');
                    if (value.length > 0 ) {
                        store.filters.add('search', new Ext.util.Filter({
                            property: 'search',
                            value: '%' + value + '%'
                        }));
                    }
                    store.load();
                }
            },
            'connect-import-filter base-element-select': {
                change: function(field, value) {
                    var table = me.getImportList(),
                        store = table.getStore();
                        store.filters.removeAtKey('supplierId');
                    if (value) {
                        store.filters.add('supplierId', new Ext.util.Filter({
                            property: field.name,
                            value: value
                        }));
                    }
                    store.load();
                }
            },
            'connect-import-filter [name=active]': {
                change: function(field, value) {
                    var table = me.getImportList(),
                        store = table.getStore();
                    if(!value) {
                        return;
                    }
                        store.filters.removeAtKey('isActive');
                    if(field.inputValue != '') {
                        store.filters.add('isActive', new Ext.util.Filter({
                            property: field.name,
                            value: field.inputValue
                        }));
                    }
                    store.load();
                }
            },
            'connect-import-filter treepanel': {
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
            },
            'connect-changed-products-list': {
                'selectionchange': me.onChangedProductsSelectionChanged
            },
            'connect-log-filter [filter=commandFilter]': {
                change: function(field, value) {
                    var table = me.getLogList(),
                        store = table.getStore();

                    store.getProxy().extraParams['commandFilter_' + field.name] = value;
                    store.reload();
                }
            },
            'connect-log-filter [name=error]': {
                change: function(field, value) {
                    var table = me.getLogList(),
                        store = table.getStore();

                    if (!value) {
                        return;
                    }

                    store.getProxy().extraParams.errorFilter = field.inputValue;
                    store.reload();
                }
            },
            'connect-log-filter textfield[name=searchfield]': {
                change: function(field, value) {
                    var table = me.getLogList(),
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
            'connect-log-list': {
                'selectionchange': function(grid, selected, eOpts) {
                    var me = this,
                        record,
                        tabs = me.getLogTabs(),
                        request = tabs.down('textarea[name=request]'),
                        response = tabs.down('textarea[name=response]');

                    // make sure that we have a selection
                    if (selected && selected.length > 0) {
                        record = selected[0];

                        request.setValue(record.get('request'));
                        response.setValue(record.get('response'));
                    }

                }
            },
            'connect-log-list button[action=clear]': {
                click: function() {
                    var table = me.getLogList(),
                        store = table.getStore();

                    Ext.MessageBox.confirm(
                        '{s name=log/clear/confirm}Delete log?{/s}',
                        '{s name=log/clear/message}You are about to delete all log entries. Continue?{/s}',
                        function (response) {
                            if ( response !== 'yes' ) {
                                return;
                            }
                            Ext.Ajax.request({
                                url: '{url action=clearLog}',
                                method: 'POST',
                                success: function(response, opts) {
                                    store.reload();
                                }
                            });

                        }
                    );
                }
            },
            'connect-mapping-import textfield[name=searchImportMapping]': {
                change: function(field, value) {
                    var tree = me.getImportMapping(),
                        store = tree.getStore();
                    store.filters.removeAtKey('searchImportShopwareCategory');
                    store.filters.removeAtKey('searchConnectCategory');
                    if (value.length > 0 ) {
                        store.filters.clear();
                        store.filters.add('searchImportShopwareCategory', new Ext.util.Filter({
                            property: 'name',
                            value: '%' + value + '%'
                        }));
                        store.filters.add('searchConnectCategory', new Ext.util.Filter({
                            property: 'mapping',
                            value: '%' + value + '%'
                        }));
                    }
                    store.load();
                }
            },
            'connect-mapping-export textfield[name=searchExportMapping]': {
                change: function(field, value) {
                    var tree = me.getExportMapping(),
                        store = tree.getStore();
                    store.filters.removeAtKey('searchExportShopwareCategory');
                    store.filters.removeAtKey('searchConnectCategory');
                    if (value.length > 0 ) {
                        store.filters.clear();
                        store.filters.add('searchExportShopwareCategory', new Ext.util.Filter({
                            property: 'name',
                            value: '%' + value + '%'
                        }));
                        store.filters.add('searchConnectCategory', new Ext.util.Filter({
                            property: 'mapping',
                            value: '%' + value + '%'
                        }));
                    }
                    store.load();
                }
            },
            'connect-marketplace-attributes-mapping button[action=save]': {
                click: function () {
                    me.saveMarketplaceAttributesMapping();
                }
            }
        });

        me.populateLogCommandFilter();

        me.callParent(arguments);
    },

    /**
     * Dynamically create filter fields for all known command types
     */
    populateLogCommandFilter: function() {
        var me = this,
            logList = me.getLogList(),
            store = logList.store,
            container = me.getLogFilter().down('fieldcontainer');

        Ext.Ajax.request({
            url: '{url action=getLogCommands}',
            method: 'POST',
            success: function(response, opts) {
                var data;

                if (!response || !response.responseText) {
                    return;
                }

                data = Ext.JSON.decode(response.responseText);

                Ext.each(data.data, function(command) {
                    store.getProxy().extraParams['commandFilter_' + command] = true;
                    container.add({
                        boxLabel  : command,
                        name      : command,
                        inputValue:  true,
                        checked   :  true,
                        filter    : 'commandFilter'
                    });
                });

                store.reload();
            },
            failure: function(response, opts) {
                Shopware.Notification.createGrowlMessage('{s name=error}Error{/s}', response.responseText);
            }

        });

    },

    /**
     * Method called when clear category button is clicked.
     */
    onExportCategoryFilterClearAction: function() {
        var me = this;

        var table = me.getExportList(),
            filter = me.getExportFilter(),
            store = table.getStore();
        store.filters.removeAtKey('exportCategoryFilter');
        store.load();

        //deselect all nodes
        Ext.getCmp('export-category-filter').getSelectionModel().deselectAll();
    },

    /**
     * Callback method called when the user selects a product in the "changed products" view.
     * Will populate the bottom "change view" grid with the correct tabs
     *
     * @param grid
     * @param selected
     * @param eOpts
     */
    onChangedProductsSelectionChanged: function(grid, selected, eOpts) {
        var me = this,
            record,
            remoteChangeSet,
            changeRecord,
            changeFlag = 0, flags,
            changeView = me.getChangeView();

        // make sure that we have a selection
        if (selected && selected.length > 0) {
            record = selected[0];

            // Decode the lastUpdate info
            remoteChangeSet = Ext.JSON.decode(record.get('lastUpdate'));

            // Build a record for the changeset
            changeRecord = Ext.create('Shopware.apps.Connect.model.changed_products.Product', {
                shortDescriptionLocal: record.get('description'),
                shortDescriptionRemote: remoteChangeSet['shortDescription'],

                longDescriptionLocal: record.get('descriptionLong'),
                longDescriptionRemote: remoteChangeSet['longDescription'],

                nameLocal: record.get('name'),
                nameRemote: remoteChangeSet['name'],

                priceLocal: record.get('price'),
                priceRemote: remoteChangeSet['price'],

                imageLocal: record.get('images'),
                imageRemote: remoteChangeSet['image'].join('|')
            });

            // Read updateFlag and build update flag object
            changeFlag = record.get('lastUpdateFlag');
            flags = {
                2: 'shortDescription',
                4: 'longDescription',
                8: 'name',
                16: 'image',
                32: 'price',
                64: 'imageInitialImport'
            };

            // Check all flags and show the corresponding tab if it is active
            // if not, remove the tab without destroying the component
            Ext.each(Object.keys(flags), function(key) {
                var fieldName = flags[key],
                    container = changeView.fields[fieldName];

                if (container) {
                    changeView.remove(container, false);
                }

                if (changeFlag & key && container) {
                    changeView.add(container);

                    container.applyButton.handler = function() {
                        me.applyChanges(fieldName, changeRecord.get(fieldName + 'Remote'), record.get('id'));
                    }

                    container.loadRecord(changeRecord);
                }
            });

            changeView.setTitle(record.get('name'));

            // hotfix: make sure that the tab is displayed correctly
            changeView.setActiveTab(0);
            changeView.setActiveTab(1);
            changeView.setActiveTab(0);

        }
    },

    /**
     * Callback to apply a given change for a given product
     *
     * @param type
     * @param value
     * @param articleId
     */
    applyChanges: function(type, value, articleId) {
        var me = this,
            changedProductsList = me.getChangedList(),
            store = changedProductsList.store;

        Ext.Ajax.request({
            url: '{url action=applyChanges}',
            method: 'POST',
            params: {
                type: type,
                value: value,
                articleId: articleId
            },
            success: function(response, opts) {
                me.createGrowlMessage('{s name=success}Success{/s}', '{s name=changed_products/success/message}Successfully applied changes{/s}');
                store.reload();
            },
            failure: function(response, opts) {
                me.createGrowlMessage('{s name=error}Error{/s}', response.responseText);
            }

        });

    },

    /**
     * Callback function that will create the connect categories in the selected category
     *
     * @param record
     */
    onImportCategoriesFromConnect: function(record) {
        var me = this,
            panel = me.getImportMapping(),
            store = panel.store;

        Ext.MessageBox.confirm(
            me.messages.importConnectCategoriesTitle,
            Ext.String.format(me.messages.importConnectCategoriesMessage, record.get('mapping'), record.get('name')),
            function (response) {
                if ( response !== 'yes' ) {
                    return;
                }

                panel.setLoading();
                Ext.Ajax.request({
                    url: '{url action=importConnectCategories}',
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
            panel = me.getExportMapping(),
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
     * Callback function that will save the current import mapping
     *
     * @param button
     */
    onSaveImportMapping: function(button) {
        var me = this,
            panel = me.getImportMapping(),
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
     * Callback function that will save the current mapping
     *
     * @param button
     */
    onSaveExportMapping: function(button) {
        var me = this,
            panel = me.getExportMapping(),
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
                var sticky = false;
                if (response.responseText) {
                    var operation = Ext.decode(response.responseText);
                    if (operation) {
                        if (!operation.success && operation.message) {
                            message = operation.message;
                            sticky = true;
                        }
                    }
                }
                me.createGrowlMessage(title, message, sticky);
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
        } else if(btn.action == 'unsubscribe') {
            url = '{url action=updateProduct}?unsubscribe=1';
            title = me.messages.unsubscribeProductTitle;
            message = me.messages.unsubscribeProductMessage;
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
    onSaveConfigForm: function(btn) {
        var me = this;
            form = btn.up('form');

        form.setLoading();
        me.saveUnitsMapping();
        if (form.getRecord()) {
            var model = form.getRecord();

            form.getForm().updateRecord(model);
            model.save({
                success: function(record) {
                    form.setLoading(false);
                    Shopware.Notification.createGrowlMessage('{s name=success}Success{/s}', '{s name=config/success/message}Successfully applied changes{/s}');
                },
                failure: function(record) {
                    form.setLoading(false);
                    var rawData = record.getProxy().getReader().rawData,
                        message = rawData.message;
                    Shopware.Notification.createGrowlMessage('{s name=error}Error{/s}', message);
                }
            });
        }
    },

    /**
     * Callback function to save the import configuration form
     *
     * @param button
     */
    onSaveImportConfigForm: function(btn) {
        var me = this;
        form = btn.up('form');

        form.setLoading();
        if (form.getRecord()) {
            var model = form.getRecord();

            form.getForm().updateRecord(model);
            model.save({
                success: function(record) {
                    form.setLoading(false);
                    Shopware.Notification.createGrowlMessage('{s name=success}Success{/s}', '{s name=config/success/message}Successfully applied changes{/s}');
                },
                failure: function(record) {
                    form.setLoading(false);
                    var rawData = record.getProxy().getReader().rawData,
                        message = rawData.message;
                    Shopware.Notification.createGrowlMessage('{s name=error}Error{/s}', response.responseText);
                }
            });
        }
    },

    /**
     * Callback function to save the export configuration form
     *
     * @param button
     */
    onSaveExportConfigForm: function(btn) {
        var me = this;
        form = btn.up('form');

        form.setLoading();
        if (form.getRecord()) {
            var model = form.getRecord();

            form.getForm().updateRecord(model);
            model.save({
                success: function(record) {
                    form.setLoading(false);
                    Shopware.Notification.createGrowlMessage('{s name=success}Success{/s}', '{s name=config/success/message}Successfully applied changes{/s}');
                },
                failure: function(record) {
                    form.setLoading(false);
                    var rawData = record.getProxy().getReader().rawData,
                        message = rawData.message;
                    Shopware.Notification.createGrowlMessage('{s name=error}Error{/s}', message);
                }
            });
        }
    },

    /**
     * Show categories tree window
     * @param btn
     */
    onAssignCategoryAction: function(btn) {
        var me = this,
            list = me.getImportList(),
            records = list.selModel.getSelection();

        if (records.length > 0) {
            Ext.create('Shopware.apps.Connect.view.import.AssignCategory').show();
        }
    },

    /**
     * Callback function that will assign products to category
     * @param btn
     */
    onSaveAssignCategoryAction: function(btn) {
        var me = this,
            ids = [],
            records = me.getImportList().getSelectionModel().getSelection(),
            treeSelection = btn.up('treepanel').getSelectionModel().getSelection(),
            categoriesWindow = btn.up('window');

        if (treeSelection.length == 0) {
            return;
        }

        Ext.each(records, function(record) {
            ids.push(record.get('id'));
        });

        Ext.MessageBox.confirm(
            '{s name=confirm}Confirm{/s}',
            Ext.String.format(me.messages.importAssignCategoryConfirm, records.length),
            function(button) {
                if (button == 'yes') {
                    categoriesWindow.setLoading(true);
                    var categoryId = treeSelection[0].get('id');

                    var url = '{url action=assignProductsToCategory}';
                    Ext.Ajax.request({
                        url: url,
                        method: 'POST',
                        params: {
                            'ids[]': ids,
                            'category': categoryId
                        },
                        success: function(response, opts) {
                            var sticky = false;
                            categoriesWindow.setLoading(false);

                            if (response.responseText) {
                                var operation = Ext.decode(response.responseText);
                                if (operation) {
                                    if (!operation.success) {
                                        me.createGrowlMessage('{s name=error}Error{/s}',
                                            '{s name=import/message/error_assign_category}Category has not been added successfully.{/s}',
                                            false
                                        );
                                    } else {
                                        me.createGrowlMessage('{s name=success}Success{/s}',
                                            '{s name=import/message/success_assign_category}Category has been added successfully.{/s}',
                                            sticky
                                        );
                                        btn.up('window').close();
                                    }
                                }
                            }
                        },
                        failure: function(response, opts) {
                            Shopware.Notification.createGrowlMessage('{s name=error}Error{/s}', response.responseText);
                        }
                    });
                }
            }
        );
    },

    saveUnitsMapping: function() {
        var me = this,
            unitsStore = me.getUnitsMapping().unitsStore;

        if (unitsStore.getUpdatedRecords().length < 1) {
            return;
        }

        unitsStore.sync({
            success :function (records, operation) {
            },
            failure:function (batch) {
                me.createGrowlMessage('{s name=error}Error{/s}','{s name=config/units/error_save_message}Mapping der Einheiten konnte nicht gespeichert werden.{/s}');
            }
        });
    },

    /**
     * Sends marketplace attributes mapping to the PHP
     */
    saveMarketplaceAttributesMapping: function() {
        var me = this,
            panel = me.getMarketeplaceMappingPanel(),
            store = me.getMarketeplaceMapping().localProductAttributesStore;

        if (store.getNewRecords().length > 0 || store.getUpdatedRecords().length > 0 || store.getRemovedRecords().length > 0) {
            panel.setLoading();
            store.sync({
                success :function (records, operation) {
                    panel.setLoading(false);
                    me.createGrowlMessage('{s name=success}Success{/s}', '{s name=config/success/message}Änderungen erfolgreich übernommen{/s}');
                },
                failure:function (batch) {
                    console.log(batch);
                    panel.setLoading(false);
                    me.createGrowlMessage('{s name=error}Error{/s}', batch.proxy.getReader().jsonData.message);
                }
            });
        }
    },

    /**
     * Calculate needed time to finish synchronization
     *
     * @param progressBar
     */
    onCalculateFinishTime: function(progressBar) {
        var me = this;
        me.time = 0;
        me.resetSpentTime();

        Ext.TaskManager.start({
            interval: 60000,
            run: function() {
                Ext.Ajax.request({
                    url: '{url controller=ConnectConfig action=calculateFinishTime}',
                    method: 'POST',
                    success: function(response, opts) {
                        var responseObject = Ext.decode(response.responseText);

                        if (responseObject.time > 0) {
                            if ((responseObject.time - (me.time + me.spentTime)) == 0) {
                                return;
                            }
                            me.time = responseObject.time;
                            me.resetSpentTime();

                            var time = me.secondsToTime(me.time);
                            progressBar.wait({
                                interval: 500,
                                increment: 15,
                                scope: this
                            });
                            progressBar.updateText(me.messages.updatePartOneMessage + ' ' + time.h + ' ' + me.messages.hours + ' ' + time.m + ' ' + me.messages.minutes + ' ' + time.s + ' ' + me.messages.seconds + ' ' + me.messages.updatePartTwoMessage);
                        } else {
                            me.resetSpentTime();
                            me.resetTime();
                            progressBar.reset();
                            progressBar.updateText(me.messages.doneMessage);
                        }
                    }
                });
            }
        });

        Ext.TaskManager.start({
            interval: 5000,
            run: function () {
                if (me.time >= 5) {
                    me.time = me.time - 5;
                    me.spentTime = me.spentTime + 5;
                    var time = me.secondsToTime(me.time);
                    progressBar.updateText(me.messages.updatePartOneMessage + ' ' + time.h + ' ' + me.messages.hours + ' ' + time.m + ' ' + me.messages.minutes + ' ' + time.s + ' ' + me.messages.seconds + ' ' + me.messages.updatePartTwoMessage);
                } else {
                    progressBar.reset();
                    progressBar.updateText(me.messages.doneMessage);
                }
            }
        });

    },

    /**
     * Convert number of seconds into time object
     *
     * @param integer secs Number of seconds to convert
     * @return object
     */
    secondsToTime: function (secs) {
        var hours = parseInt( secs / 3600 ) % 24;
        var minutes = parseInt( secs / 60 ) % 60;
        var seconds = secs % 60;

        var obj = {
            "h": hours,
            "m": minutes,
            "s": seconds
        };
        return obj;
    },

    /**
     * Reset spent time used for synchronization bar
     */
    resetSpentTime: function() {
        var me = this;
        me.spentTime = 0;
    },

    /**
     * Reset time used for synchronization bar
     */
    resetTime: function() {
        var me = this;
        me.time = 0;
    }
});
//{/block}
