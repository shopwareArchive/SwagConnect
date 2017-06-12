//{namespace name=backend/connect/view/main}

/**
 * Shopware Controller - Cache backend module
 */
//{block name="backend/connect/controller/main"}
Ext.define('Shopware.apps.Connect.controller.Main', {

    extend: 'Enlight.app.Controller',

    stores: [
        'main.Navigation',
        'export.StreamList', 'export.List', 'import.RemoteCategories', 'import.RemoteProducts', 'import.LocalProducts',
        'changed_products.List',
        'log.List',
		'config.General', 'config.Import', 'config.Export', 'config.CustomerGroup', 'config.PriceGroup',
        'config.Units', 'config.ConnectUnits', 'config.MarketplaceAttributes', 'config.LocalProductAttributes'
    ],
    models: [
        'main.Mapping', 'main.Product',
        'export.StreamList', 'export.List', 'import.List',
        'changed_products.List', 'changed_products.Product', 'log.List',
        'config.General', 'config.Import', 'config.Units', 'config.MarketplaceAttributes',
        'config.ConnectUnit', 'config.Pages', 'config.LocalProductAttributes', 'config.PriceGroup'
    ],

    refs: [
        { ref: 'window', selector: 'connect-window' },
        { ref: 'navigation', selector: 'connect-navigation' },
        { ref: 'panel', selector: 'connect-panel' },
        { ref: 'exportWindow', selector: 'connect-export-window' },
        { ref: 'exportList', selector: 'connect-export-list' },
        { ref: 'exportStreamList', selector: 'connect-export-stream-list' },
        { ref: 'exportFilter', selector: 'connect-export-filter' },
        { ref: 'importList', selector: 'connect-import-list' },
        { ref: 'changeView', selector: 'connect-changed-products-tabs' },
        { ref: 'changedList', selector: 'connect-changed-products-list' },
        { ref: 'logList', selector: 'connect-log-list' },
        { ref: 'logFilter', selector: 'connect-log-filter' },
        { ref: 'logTabs', selector: 'connect-log-tabs' },
        { ref: 'marketeplaceMappingPanel', selector: 'connect-config-marketplace-attributes' },
        { ref: 'marketeplaceMapping', selector: 'connect-marketplace-attributes-mapping' },
        { ref: 'exportConfigForm', selector: 'connect-config-export-form' },
        { ref: 'unitsMapping', selector: 'connect-units-mapping-list' }
    ],

    messages: {
        login: {
            successTitle: '{s name=login/successTitle}Shopware ID{/s}',
            successMessage: '{s name=login/successMessage}Login successful{/s}',
            waitTitle: '{s name=login/waitTitle}Logging in...{/s}',
            waitMessage: '{s name=login/waitMessage}This process might take a few seconds{/s}'
        },
        growlMessage:'{s name=growlMessage}Shopware Connect{/s}',

        saveMappingTitle: '{s name=mapping/message/title}Save category mapping{/s}',
        saveMappingSuccess: '{s name=mapping/message/success}Category mapping has been saved.{/s}',
        saveMappingError: '{s name=mapping/message/error}Category mapping could not be saved.{/s}',


        insertOrUpdateProductTitle: '{s name=export/message/import_product_title}Products export{/s}',
        insertOrUpdateProductMessage: '{s name=export/message/import_product_messag}Products were marked for inserting / updating.{/s}',
        deleteProductTitle: '{s name=export/message/delete_title}Products export{/s}',
        deleteProductMessage: '{s name=export/message/delete_message}Products were marked for deleting.{/s}',

        exportStreamTitle: '{s name=export/message/export_stream_title}Product streams export{/s}',
        exportStreamMessage: '{s name=export/message/export_stream_message}Product streams were marked for export.{/s}',
        removeStreamTitle: '{s name=export/message/remove_stream_title}Product streams export{/s}',
        removeStreamMessage: '{s name=export/message/remove_stream_message}Products streams were marked for remove.{/s}',
        exportDynamicStreamMessage: '{s name=export/message/dynamic_stream_selected}The selected dynamic streams will be exported via cron job{/s}',

        activateProductTitle: '{s name=import/message/activate_title}Products import{/s}',
        activateProductMessage: '{s name=import/message/activate_message}Products have been activated.{/s}',
        disableProductTitle: '{s name=import/message/disable_title}Products import{/s}',
        disableProductMessage: '{s name=import/message/disable_message}Products have been disabled.{/s}',
        unsubscribeProductTitle: '{s name=import/message/unsubscribe_title}Products unsubscribed{/s}',
        unsubscribeProductMessage: '{s name=import/message/unsubscribe_message}Products have been unsubscribed.{/s}',

        priceErrorMessage: '{s name=export/progress/error_price_message}[0] of [1] products weren\'t exported, because there were with empty price fields{/s}',

        applyMappingToChildCategoriesTitle: '{s name=mapping/applyConfirmTitle}Apply to child categories?{/s}',
        applyMappingToChildCategoriesMessage: '{s name=mapping/applyConfirmMessage}Do you want to apply this mapping to all empty child categories? This will immediately save the current mapping, all other unsaved changes will be lost{/s}',

        updatePartOneMessage: Ext.String.format('{s name=config/message/update_part_one}Update to [0] will take{/s}', marketplaceName),
        updatePartTwoMessage: '{s name=config/message/update_part_two}to finish{/s}',
        doneMessage: '{s name=config/message/done}Done{/s}',

        hours: '{s name=connect/hours}Hour(s){/s}',
        minutes: '{s name=connect/minutes}Minute(s){/s}',
        seconds: '{s name=connect/seconds}Second(s){/s}',

        exportTitle: '{s name=connect/tab_panel/export}Export{/s}',
        exportStatusCount:  '{s name=export/message/status_count}Sync-Status: [0] from [1] products{/s}',
        priceModeNotSelected: '{s name=config/config/price/price_mode_not_selected}Please select price mode{/s}',
        productDescriptionNotSelected: '{s name=config/export/product_description_export_not_selected}Please select product description{/s}',

        adoptUnitsTitle: '{s name=config/import/adopt_units_confirm_title}Maßeinheiten übernehmen{/s}',
        adoptUnitsMessage: '{s name=config/import/adopt_units_confirm_message}Möchten Sie die importieren Maßeinheiten in Ihren Shop übernehmen?{/s}',

        priceFieldIsNotSupported: '{s name=config/export/priceFieldIsNotSupported}Price field is not maintained. Some of the products have price = 0{/s}',
        priceResetSuccess: '{s name=config/price_reset_success}The exported prices were successfully reset. It will take up to 10min for the changes to take effect. When this operation is done, you will get the option to set the price type again when you reopen "Export" in the Connect menu.{/s}',
        priceResetError: '{s name=config/price_reset_error}A problem occur in price type reset. Please try again later or contact our support team.{/s}',
        priceResetLabel: '{s name=config/price_reset_label}Reset exported prices{/s}',

        exchangeSettingsResetSuccess: '{s name=config/exchange_settings_success}Successfully reset. It will take up to 10min for the changes to take effect. When this operation is done, you need to login again.{/s}',
        exchangeSettingsResetError: '{s name=config/exchange_settings_error}A problem occur in exchange settings reset. Please try again later or contact our support team.{/s}',
        exchangeSettingsResetLabel: '{s name=config/exchange_settings_label}Reset exchange settings{/s}',

        importConnectCategoriesTitle: '{s name=mapping/importConnectCategoriesTitle}Import categories?{/s}',
        importConnectCategoriesMessage: '{s name=mapping/importConnectCategoriesMessage}Do you want to import all subcategories of »[0]« to you category »[1]«?{/s}',
        importAssignCategoryConfirm: '{s name=import/message/confirm_assign_category}Assign the selected »[0]« products to the category selected below.{/s}',
        allProductsMarkedForExportWithCron: '{s name=export/all/marked_for_export_with_cron}All products have been marked for export with CronJob.{/s}',
        error: '{s name=connect/error}error{/s}'
    },


    /**
     * Class property which holds the main application if it is created
     *
     * @default null
     * @object
     */
    mainWindow: null,

    exportStatusEl: null,

    /**
     * Init component. Basically will create the app window and register to events
     */
    init: function () {
        var me = this;

        if (!window.marketplaceName) {
            me.sendAjaxRequest(
                '{url controller=Connect action=initParams}',
                {},
                function(response) {

                    window.marketplaceName = response.data.marketplaceName;
                    window.marketplaceNetworkUrl = response.data.marketplaceNetworkUrl;
                    window.marketplaceLogo = response.data.marketplaceLogo;
                    window.defaultMarketplace = response.data.defaultMarketplace;
                    window.isFixedPriceAllowed = response.data.isFixedPriceAllowed;
                    window.purchasePriceInDetail = response.data.purchasePriceInDetail;

                    me.launchAction();
                    me.setEventListeners();
                }
            );
        } else {
            me.launchAction();
            me.setEventListeners();
        }

        me.callParent(arguments);
    },

    launchAction: function () {
        var me = this;
        switch (me.subApplication.action){
            case 'Export':
                me.mainWindow = me.getView('export.Window').create({
                    'action': me.subApplication.action
                }).show();
                break;
            case 'Settings':
                me.customerGroupStore = Ext.create('Shopware.apps.Connect.store.config.CustomerGroup').load({
                    callback: function(){
                        me.mainWindow = me.getView('config.Window').create({
                            customerGroupStore: me.customerGroupStore
                        }).show();
                        me.populateLogCommandFilter();
                    }
                });
                break;
            default:
                me.mainWindow = me.getView('main.Window').create({
                    'action': me.subApplication.action
                }).show();
                break;
        }
    },

    setEventListeners: function () {
        var me = this;
        me.control({
            'connect-navigation': {
                select: me.onSelectNavigationEntry
            },
            'connect-config button[action=save-general-config]': {
                click: me.onSaveConfigForm
            },
            'connect-config-form': {
                calculateFinishTime: me.onCalculateFinishTime,
                resetPriceType: me.onResetPriceType,
                resetExchangeSettings: me.onResetExchangeSettings
            },
            'connect-config-import-form button[action=save-import-config]': {
                click: me.onSaveImportConfigForm
            },
            'connect-import-unit button[action=save-unit]': {
                click: me.onSaveUnitsMapping
            },
            'connect-import-unit checkbox[name=hideAssignedUnits]': {
                change: me.onHideAssignedUnits
            },
            'connect-import-unit button[action=adoptUnits]': {
                click: me.onAdoptUnits
            },
            'connect-export-price-form': {
                saveExportSettings: me.onSaveExportSettingsForm,
                collectPriceParams: me.collectPriceParams,
                rejectPriceConfigChanges: me.rejectPriceConfigChanges
            },
            'connect-config-export-form combobox[name=priceGroupForPriceExport]': {
                change: me.onChangePriceGroupForPrice
            },
            'connect-config-export-form combobox[name=priceGroupForPurchasePriceExport]': {
                change: me.onChangePriceGroupForPurchasePrice
            },
            'connect-config-export-form combobox[name=priceFieldForPriceExport]': {
                change: me.onChangePriceFieldForPrice
            },
            'connect-config-export-form combobox[name=priceFieldForPurchasePriceExport]': {
                change: me.onChangePriceFieldForPurchasePrice
            },
            'connect-mapping button[action=save]': {
                click: me.onSaveMapping
            },
            'connect-export button[action=add]': {
                click: me.onExportAction
            },
            'connect-export button[action=delete]': {
                click: me.onRemoveArticleAction
            },
            'connect-export button[action=exportAll]': {
                click: me.onExportAllAction
            },
            'connect-article-export-progress-window': {
                startExport: me.startArticleExport
            },
            'connect-many-products-dialog': {
                cronExportAll: me.cronExportAll
            },
            'connect-stream-export-progress-window': {
                startStreamExport: me.startStreamExport
            },
            'connect-export-stream button[action=add]': {
                click: me.onExportStream
            },

            'connect-export-stream button[action=remove]': {
                click: me.onRemoveStreams
            },
            'connect-config-export-form': {
                saveExportSettings: me.onSaveExportSettingsForm,
                collectPriceParams: me.collectPriceParams,
                rejectPriceConfigChanges: me.rejectPriceConfigChanges

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
                    store.load({
                        page: 1,
                        start: 0
                    });
                    store.currentPage = 1;
                }
            },

            'connect-export-filter [name=supplierId]': {
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
                    store.load({
                        page: 1,
                        start: 0
                    });
                    store.currentPage = 1;
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
                    store.load({
                        page: 1,
                        start: 0
                    });
                    store.currentPage = 1;
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
                    store.load({
                        page: 1,
                        start: 0
                    });
                    store.currentPage = 1;
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
                changeLogging: me.onChangeLogging,
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
            'connect-export-window': {
                showPriceWindow: me.onShowPriceWindow
            },

            'connect-export-list': {
                getExportStatus: me.onGetExportStatus,
                reloadLocalProducts: me.onGetExportStatus
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
            'connect-marketplace-attributes-mapping button[action=save]': {
                click: function () {
                    me.saveMarketplaceAttributesMapping();
                }
            }
        });

        Shopware.app.Application.on(me.getEventListeners());
    },

    getEventListeners: function() {
        var me = this;

        return {
            'connect-login': me.login,
            'connect-register': me.register,
            scope: me
        };
    },

    sendAjaxRequest: function(url, params, callback, errorCallback) {
        var me = this;

        Ext.Ajax.request({
            url: url,
            method: 'POST',
            params: params,
            success: function(operation, opts) {
                var response = Ext.decode(operation.responseText);

                if (response.success === false) {
                    if (Ext.isFunction(errorCallback)) {
                        errorCallback(response);
                    } else {
                        me.displayErrorMessage(response);
                        me.hideLoadingMask();
                    }
                    return;
                }

                callback(response);
            }
        });
    },

    login: function(params, callback) {
        var me = this;

        me.splashScreen = Ext.Msg.wait(
            me.messages.login.waitMessage,
            me.messages.login.waitTitle
        );

        me.sendAjaxRequest(
            '{url controller=Connect action=login}',
            params,
            function(response) {

                response.shopwareId = params.shopwareID;
                me.splashScreen.close();

                if (response.success == true) {
                    Ext.create('Shopware.notification.SubscriptionWarning').checkSecret();

                    Shopware.Notification.createGrowlMessage(
                        me.messages.login.successTitle,
                        me.messages.login.successMessage,
                        me.messages.growlMessage
                    );

                    if (callback && typeof callback === 'function') {
                        callback(response);
                    }

                    document.cookie = 'connectLogin=true';
                    location.reload();
                }
            },
            function(response) {
                me.splashScreen.close();
                me.displayErrorMessage(response, callback);
            }
        );
    },

    register: function(params, callback) {
        var me = this;

        me.splashScreen = Ext.Msg.wait(
            me.messages.login.waitMessage,
            me.messages.login.waitTitle
        );

        me.sendAjaxRequest(
            '{url controller=Connect action=register}',
            params,
            function(response) {

                response.shopwareId = params.shopwareID;
                me.splashScreen.close();

                if (response.success == true) {
                    Ext.create('Shopware.notification.SubscriptionWarning').checkSecret();

                    Shopware.Notification.createGrowlMessage(
                        me.messages.login.successTitle,
                        me.messages.login.successMessage,
                        me.messages.growlMessage
                    );

                    if (callback && typeof callback === 'function') {
                        callback(response);
                    }

                    document.cookie = 'connectLogin=true';
                    location.reload();
                }
            },
            function(response) {
                me.splashScreen.close();
                me.displayErrorMessage(response, callback);
            }
        );
    },

    displayErrorMessage: function(response, callback) {
        var me = this,
            message = response.message;

        Shopware.Notification.createStickyGrowlMessage({
            title: me.messages.error,
            text: message,
            width: 350
        });

        callback = typeof callback === 'function' && callback || function() {};

        if (response.hasOwnProperty('authentication') && response.authentication) {
            Shopware.app.Application.fireEvent('open-login', callback);
        }

        this.hideLoadingMask();
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
                Shopware.Notification.createGrowlMessage('{s name=connect/error}Error{/s}', response.responseText);
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

                additionalDescriptionLocal: record.get('additionalDescription'),
                additionalDescriptionRemote: remoteChangeSet['additionalDescription'],

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
                64: 'imageInitialImport',
                128: 'additionalDescription'
            };
            // Check all flags and show the corresponding tab if it is active
            // if not, remove the tab without destroying the component
            changeView.removeAll();

            Ext.each(Object.keys(flags), function(key) {
                var fieldName = flags[key];
                if (changeFlag & key) {
                    var form = changeView.createContainer(fieldName);
                    form.loadRecord(changeRecord);
                    changeView.add(form);
                    form.applyButton.handler = function () {
                        me.applyChanges(fieldName, changeRecord.get(fieldName + 'Remote'), record.get('id'), changeView);
                    }
                }
            });

            changeView.setTitle(record.get('name'));
            changeView.setActiveTab(0);
        }
    },

    /**
     * Callback to apply a given change for a given product
     *
     * @param type
     * @param value
     * @param detailId
     * @param changeView
     */
    applyChanges: function(type, value, detailId, changeView) {
        var me = this,
            changedProductsList = me.getChangedList(),
            store = changedProductsList.store;

        Ext.Ajax.request({
            url: '{url controller=LastChanges action=applyChanges}',
            method: 'POST',
            params: {
                type: type,
                value: value,
                detailId: detailId
            },
            success: function(response, opts) {
                var responseObject = Ext.decode(response.responseText);
                if (responseObject.success) {
                    me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=changed_products/success/notification/message}Successfully applied changes{/s}');
                } else {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', responseObject.message);
                }

                store.reload();
                changeView.removeAll();
            },
            failure: function(response, opts) {
                me.createGrowlMessage('{s name=connect/error}Error{/s}', response.responseText);
            }

        });

    },

    /**
     * Callback function that will insert from/for export
     *
     * @param btn
     */
    onExportAction: function(btn) {
        var me = this,
            list = me.getExportList(),
            records = list.selModel.getSelection(),
            ids = [],
            title = me.messages.insertOrUpdateProductTitle;

        if (records.length == 0) {
            return;
        }

        Ext.each(records, function(record) {
            ids.push(record.get('id'));
        });

        Ext.Ajax.request({
            url: '{url action=getArticleSourceIds}',
            method: 'POST',
            params: {
                'ids[]': ids
            },
            success: function(response, opts) {
                if (response.responseText) {
                    var operation = Ext.decode(response.responseText);
                    if (operation) {
                        if (!operation.success) {
                            me.createGrowlMessage(title, operation.message, true);
                        } else {
                            Ext.create('Shopware.apps.Connect.view.export.product.Progress', {
                                sourceIds: operation.sourceIds
                            }).show();
                        }
                    }
                }
            }
        });
    },

    /**
     * Callback function that will insert from/for export
     *
     * @param sourceIds
     * @param batchSize
     * @param window
     * @param offset
     */
    startArticleExport: function(sourceIds, batchSize, window, offset) {
        offset = parseInt(offset) || 0;
        var limit = offset + batchSize;

        if (limit > sourceIds.length) {
            limit = sourceIds.length;
        }

        var me = this,
        message = me.messages.insertOrUpdateProductMessage,
        title = me.messages.insertOrUpdateProductTitle,
        list = me.getExportList();

        Ext.Ajax.request({
            url: '{url action=insertOrUpdateProduct}',
            method: 'POST',
            params: {
                'sourceIds[]': sourceIds.slice(offset, limit)
            },
            success: function(response, opts) {
                var doneDetails = limit;
                var operation = Ext.decode(response.responseText);

                if (!operation.success && operation.messages) {

                    if(operation.messages.price && operation.messages.price.length > 0){
                        var priceMsg = Ext.String.format(
                            me.messages.priceErrorMessage, operation.messages.price.length, sourceIds.length
                        );
                        me.createGrowlMessage(title, priceMsg, true);
                    }

                    if(operation.messages.default && operation.messages.default.length > 0){
                        operation.messages.default.forEach( function(message){
                            me.createGrowlMessage(title, message, true);
                        });
                    }
                }

                window.progressField.updateText(Ext.String.format(window.snippets.process, doneDetails, sourceIds.length));
                window.progressField.updateProgress(
                    limit / sourceIds.length,
                    Ext.String.format(window.snippets.process, doneDetails, sourceIds.length),
                    true
                );

                if (limit >= sourceIds.length) {
                    window.closeWindow();
                    me.createGrowlMessage(title, message, false);
                    list.store.load();
                    me.onGetExportStatus();
                } else {
                    //otherwise we have to call this function recursive with the next offset
                    me.startArticleExport(sourceIds, batchSize, window, limit);
                }
            },
            failure: function(operation) {
                me.createGrowlMessage(title, operation.responseText, true);
                window.inProcess = false;
                window.cancelButton.setDisabled(false);
            }
        });
    },

    cronExportAll: function () {
        var me = this;

        Ext.Ajax.request({
            url: '{url action=exportAllWithCron}',
            method: 'POST',

            success: function (response, opts) {
                var operation = Ext.decode(response.responseText);
                if (operation.success) {
                    me.createGrowlMessage("{s name=connect/success}Success{/s}", me.messages.allProductsMarkedForExportWithCron, true);
                    me.getExportList().getStore().reload();
                } else {
                    me.createGrowlMessage("{s name=connect/error}Error{/s}", operation.message, true);
                }
            }
        });
    },

    startStreamExport: function(streamIds, articleDetailIds, batchSize, window, currentStreamIndex, offset) {
        offset = parseInt(offset) || 0;
        var limit = batchSize;

        var me = this,
            list = me.getExportStreamList(),
            messages = [];

        Ext.Ajax.request({
            url: '{url action=exportStream}',
            method: 'POST',
            params: {
                'streamIds[]': streamIds,
                'currentStreamIndex': currentStreamIndex,
                'articleDetailIds[]': articleDetailIds,
                'offset': offset,
                'limit': limit
            },
            success: function(response, opts) {
                var sticky = false;
                if (response.responseText) {
                    var operation = Ext.decode(response.responseText);
                    if (operation) {
                        if (!operation.success && operation.messages) {
                            if(operation.messages.price && operation.messages.price.length > 0){
                                var priceMsg = Ext.String.format(
                                    me.messages.priceErrorMessage, operation.messages.price.length, articleDetailIds.length
                                );
                                me.createGrowlMessage(me.messages.exportStreamTitle, priceMsg, true);
                            }

                            if(operation.messages.default && operation.messages.default.length > 0){
                                operation.messages.default.forEach( function(message){
                                    me.createGrowlMessage(me.messages.exportStreamTitle, message, true);
                                });
                            }

                            window.inProcess = false;
                            window.cancelButton.setDisabled(false);
                            return;
                        }

                        window.progressFieldStream.updateText(Ext.String.format(window.snippets.processStream, operation.processedStreams, streamIds.length));
                        window.progressFieldStream.updateProgress(
                            operation.processedStreams / streamIds.length,
                            Ext.String.format(window.snippets.processStream, operation.processedStreams, streamIds.length),
                            false
                        );

                        window.progressField.updateText(Ext.String.format(window.snippets.process, operation.newOffset, articleDetailIds.length));
                        window.progressField.updateProgress(
                            operation.newOffset / articleDetailIds.length,
                            Ext.String.format(window.snippets.process, operation.newOffset, articleDetailIds.length),
                            false
                        );

                        if (operation.hasMoreIterations) {
                            articleDetailIds = operation.articleDetailIds;
                            currentStreamIndex = operation.nextStreamIndex;
                            offset = operation.newOffset;
                            me.startStreamExport(streamIds, articleDetailIds, batchSize, window, currentStreamIndex, offset);
                        } else {
                            window.inProcess = false;
                            window.cancelButton.setDisabled(false);

                            me.createGrowlMessage(me.messages.exportStreamTitle, me.messages.exportStreamMessage, sticky);

                            list.setLoading(false);
                            list.store.load();
                        }
                    }
                }
            }
        });
    },

    /**
     * Callback function that will start the export all dialog
     *
     * @param btn
     */
    onExportAllAction: function (btn) {
        var me = this,
            list = me.getExportList(),
            title = me.messages.insertOrUpdateProductTitle;

        list.setLoading(true);

        Ext.Ajax.request({
            url: '{url action=getArticleSourceIds}',
            method: 'POST',
            params: {
                'exportAll': true
            },
            success: function (response, opts) {
                list.setLoading(false);

                if (!response.responseText) {
                    return;
                }

                var operation = Ext.decode(response.responseText);
                if (!operation) {
                    return;
                }

                if (!operation.success) {
                    me.createGrowlMessage(title, operation.message, true);
                } else {
                    if (operation.sourceIds.length > 1000) {

                        Ext.create('Shopware.apps.Connect.view.export.product.manyProductsDialog', {
                            sourceIds: operation.sourceIds
                        }).show();
                        return;
                    }

                    Ext.create('Shopware.apps.Connect.view.export.product.Progress', {
                        sourceIds: operation.sourceIds
                    }).show();
                }
            }
        });
    },

    /**
     * Callback function that will delete a product from/for export
     *
     * @param btn
     */
    onRemoveArticleAction: function(btn) {
        var me = this,
            list = me.getExportList(),
            records = list.selModel.getSelection(),
            ids = [],
            messages = [];

        Ext.each(records, function(record) {
            ids.push(record.get('id'));
        });

        list.setLoading();

        Ext.Ajax.request({
            url: '{url action=deleteProduct}',
            method: 'POST',
            params: {
                'ids[]': ids
            },
            success: function(response, opts) {
                var sticky = false;
                if (response.responseText) {
                    var operation = Ext.decode(response.responseText);
                    if (operation) {
                        if (!operation.success && operation.messages) {
                            messages = operation.messages;
                            sticky = true;
                        }
                    }
                }
                if (messages.length > 0) {
                    messages.forEach( function(message){
                        me.createGrowlMessage(me.messages.deleteProductTitle, message, sticky);
                    });
                } else {
                    me.createGrowlMessage(me.messages.deleteProductTitle, me.messages.deleteProductMessage, sticky);
                }

                list.setLoading(false);
                list.store.load();
            }
        });
    },

    /**
     * Callback function that will export or delete a product stream from/for export
     *
     * @param btn
     */
    onExportStream: function(btn){
        var me = this,
            list = me.getExportStreamList(),
            records = list.selModel.getSelection(),
            staticStreamIds = [],
            dynamicStreamIds = [];

        if (records.length == 0) {
            return;
        }

        Ext.each(records, function(record) {
            if (record.get('type') == 1) {
                dynamicStreamIds.push(record.get('id'));
            } else {
                staticStreamIds.push(record.get('id'));
            }
        });
        if (dynamicStreamIds.length > 0) {
            me.prepareDynamicStreamExport(dynamicStreamIds);
        }

        if (staticStreamIds.length > 0) {
            me.prepareStaticStreamExport(staticStreamIds);
        }

    },

    prepareDynamicStreamExport: function(ids){
        var me = this,
            list = me.getExportStreamList(),
            store = list.store,
            title = me.messages.exportStreamTitle;

        Ext.Ajax.request({
            url: '{url action=prepareDynamicStreams}',
            method: 'POST',
            params: {
                'streamIds[]': ids
            },
            success: function(response) {
                if (response.responseText) {
                    var operation = Ext.decode(response.responseText);

                    if (!operation) {
                        return;
                    }

                    if (!operation.success) {
                        me.createGrowlMessage(title, operation.messages, false);
                        return;
                    }

                    me.createGrowlMessage(title, me.messages.exportDynamicStreamMessage, false);
                    store.reload();
                }
            }
        });
    },

    /**
     * @param ids
     */
    prepareStaticStreamExport: function(ids){
        var me = this;

        Ext.Ajax.request({
            url: '{url action=getStreamProductsCount}',
            method: 'POST',
            params: {
                'id': ids[0]
            },
            success: function(response) {
                if (response.responseText) {
                    var operation = Ext.decode(response.responseText);

                    if (!operation) {
                        return;
                    }

                    if (!operation.success) {
                        var title = me.messages.exportStreamTitle,
                            messages = operation.messages;

                        //todo: handle price and default errors
                        if (messages.default.length > 0) {
                            messages.default.forEach(function (message) {
                                me.createGrowlMessage(title, message, false);
                            });
                        }
                    } else {
                        Ext.create('Shopware.apps.Connect.view.export.stream.Progress', {
                            streamIds: ids,
                            articleDetailIds: operation.sourceIds
                        }).show();
                    }
                }
            }
        });
    },

    onRemoveStreams: function(){
        var me = this,
            list = me.getExportStreamList(),
            records = list.selModel.getSelection(),
            ids = [],
            url,
            message,
            messages = [],
            title;

        if (records.length == 0) {
            return;
        }

        Ext.each(records, function(record) {
            ids.push(record.get('id'));
        });

        url = '{url action=removeStreams}';
        title = me.messages.removeStreamTitle;
        message = me.messages.removeStreamMessage;

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
                        if (!operation.success && operation.messages) {
                            messages = operation.messages;
                            sticky = true;
                        }
                    }
                }

                if (messages.length > 0) {
                    messages.forEach( function(message){
                        me.createGrowlMessage(title, message, sticky);
                    });
                } else {
                    me.createGrowlMessage(title, message, sticky);
                }

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
        var me = this;

        if (!sticky) {
            Shopware.Notification.createGrowlMessage(title, message, me.mainWindow.title);
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

        if (form.getRecord()) {
            var model = form.getRecord();

            form.getForm().updateRecord(model);
            model.save({
                success: function(record) {
                    form.setLoading(false);
                    Shopware.Notification.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=config/success/message}Successfully applied changes{/s}');
                },
                failure: function(record) {
                    form.setLoading(false);
                    var rawData = record.getProxy().getReader().rawData,
                        message = rawData.message;
                    Shopware.Notification.createGrowlMessage('{s name=connect/error}Error{/s}', message);
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
        var me = this,
            form = btn.up('form');

        form.setLoading();
        if (form.getRecord()) {
            var model = form.getRecord();

            form.getForm().updateRecord(model);
            model.save({
                success: function(record) {
                    form.setLoading(false);
                    me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=config/success/message}Successfully applied changes{/s}');
                },
                failure: function(record) {
                    form.setLoading(false);
                    var rawData = record.getProxy().getReader().rawData,
                        message = rawData.message;
                    Shopware.Notification.createGrowlMessage('{s name=connect/error}Error{/s}', response.responseText);
                }
            });
        }
    },

    onSaveUnitsMapping: function(btn){
        var me = this,
            unitsStore = me.getUnitsMapping().getStore(),
            form = btn.up('form');

        if (unitsStore.getModifiedRecords().length > 0){
            form.setLoading();
        }

        unitsStore.sync({
            success :function (records, operation) {
                form.setLoading(false);
                me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=config/success/message}Successfully applied changes{/s}');
                me.getUnitsMapping().unitsStore.load({
                    scope: this,
                    callback: function(records, operation, success) {
                        me.getUnitsMapping().getStore().reload();
                    }
                });
            },
            failure:function (batch) {
                me.createGrowlMessage('{s name=connect/error}Error{/s}','{s name=config/units/error_save_message}Mapping der Einheiten konnte nicht gespeichert werden.{/s}');
            }
        });
    },

    /**
     * Reload Shopware Connect units store
     * and show/hide already assigned units
     */
    onHideAssignedUnits: function(checkbox, value)
    {
        var me = this;
        var store = me.getUnitsMapping().getStore();
        var hideAssigned = 0;

        if (value === true) {
            hideAssigned = 1;
        }

        store.load({
            params: {
                'hideAssignedUnits': hideAssigned
            }
        });
    },

    onAdoptUnits: function(btn) {
        var me = this;
        var form = btn.up('form');

        Ext.Msg.show({
            title: me.messages.adoptUnitsTitle,
            msg: me.messages.adoptUnitsMessage,
            buttons: Ext.Msg.YESNO,
            fn: function(response) {
                if(response !== 'yes') {
                    return;
                }

                form.setLoading();
                Ext.Ajax.request({
                    url: '{url controller=ConnectConfig action=adoptUnits}',
                    method: 'POST',
                    success: function(response, opts) {
                        var responseObject = Ext.decode(response.responseText);
                        form.setLoading(false);
                        if (responseObject.success) {
                        } else {
                        }
                    }
                });
            }
        });
    },

    /**
     * Callback function to save the export configuration form
     *
     * @param data
     * @param btn
     */
    onSaveExportSettingsForm: function(data, btn) {
        var me = this,
            form = btn.up('form');

        var model = Ext.create('Shopware.apps.Connect.model.config.Export', data);

        if (data.exportPriceMode.length == 0) {
            return me.createGrowlMessage(me.messages.exportTitle, me.messages.priceModeNotSelected);
        }

        if (!data.hasOwnProperty('descriptionField') || data.descriptionField.length == 0) {
            return me.createGrowlMessage(me.messages.exportTitle, me.messages.productDescriptionNotSelected);
        }

        form.setLoading();
        model.save({
            success: function(record) {
                form.setLoading(false);
                me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=config/success/message}Successfully applied changes{/s}');

                if (me.hasOwnProperty('exportWindow') && me.exportWindow.isWindow) {
                    var domEl = Ext.dom.Query.select('.export-window-wrapper');
                    domEl[0].remove();
                    me.exportWindow.close();
                }
            },
            failure: function(record) {
                form.setLoading(false);
                var rawData = record.getProxy().getReader().rawData,
                    message = rawData.message;
                me.createGrowlMessage('{s name=connect/error}Error{/s}', message);
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
                    me.createGrowlMessage('{s name=connect/success}Success{/s}', '{s name=config/success/message}Änderungen erfolgreich übernommen{/s}');
                },
                failure:function (batch) {
                    panel.setLoading(false);
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', batch.proxy.getReader().jsonData.message);
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

    onResetPriceType: function () {
        var me = this;

        Ext.Ajax.request({
            scope: this,
            url: '{url module=backend controller=ConnectConfig action=resetPriceType}',
            success: function (result) {
                var response = Ext.JSON.decode(result.responseText);
                if (response.success) {
                    me.createGrowlMessage(
                        me.messages.priceResetLabel,
                        me.messages.priceResetSuccess
                    );
                } else {
                    me.createGrowlMessage(
                        me.messages.priceResetLabel,
                        me.messages.priceResetError
                    );
                }
            }
        });
    },

    onResetExchangeSettings: function () {
        var me = this;

        Ext.Ajax.request({
            scope: this,
            url: '{url module=backend controller=ConnectConfig action=resetExchangeSettings}',
            success: function (result) {
                var response = Ext.JSON.decode(result.responseText);
                if (response.success) {
                    me.createGrowlMessage(
                        me.messages.exchangeSettingsResetLabel,
                        me.messages.exchangeSettingsResetSuccess
                    );

                    //reload after 3 sec
                    setTimeout(function(){
                        location.reload();
                    }, 3000);
                } else {
                    me.createGrowlMessage(
                        me.messages.exchangeSettingsResetLabel,
                        me.messages.exchangeSettingsResetError
                    );
                }
            }
        });
    },

    onChangeLogging: function(checkbox, newValue, oldValue) {
        var me = this;
        var loggingEnabled = 0;
        if (newValue === true) {
            loggingEnabled = 1;
        }

        Ext.Ajax.request({
            url: '{url controller=ConnectConfig action=changeLogging}',
            method: 'POST',
            params: {
                enableLogging: loggingEnabled
            },
            success: function(response, opts) {
                var data = Ext.JSON.decode(response.responseText);
                if (data.success == false) {
                    me.createGrowlMessage('{s name=connect/error}Error{/s}', '{s name=config/log_label}Logging aktivieren{/s}');
                }
            }
        });
    },

    onGetExportStatus: function() {
        var me = this;

        Ext.Ajax.request({
            scope: me,
            url: '{url controller=Connect action=getExportStatus}',
            success: function (result) {
                var response = Ext.JSON.decode(result.responseText);
                if (response.success === true) {

                    //removes the old export status
                    if (me.exportStatusEl) {
                        Ext.destroy(me.exportStatusEl);
                    }

                    //sets the new export status
                    me.exportStatusEl = me.getExportWindow().getEl().insertHtml(
                        "afterBegin",
                        me.getHtmlStatus(response.data, response.total),
                        true
                    );
                }
            }
        });
    },

    getHtmlStatus: function ($start, $end) {
        var me = this;

        return '<div class="sc-export-status">' +
            Ext.String.format(me.messages.exportStatusCount, $start, $end) +
            '</div>';
    },


    /**
     * On change customer group for price configuration
     * reload the store with available price fields
     *
     * @param combo
     * @param newValue
     * @param oldValue
     */
    onChangePriceGroupForPrice: function(combo, newValue, oldValue) {
        var me = this;
        var exportConfigForm = me.getExportConfigForm();
        exportConfigForm.setLoading(true);
        exportConfigForm.priceFieldForPrice.store.load({
            params: {
                'customerGroup': newValue
            },
            callback: function() {
                exportConfigForm.setLoading(false);
            }
        });
    },

    /**
     * On change customer group for purchase price configuration
     * reload the store with available price fields
     *
     * @param combo
     * @param newValue
     * @param oldValue
     */
    onChangePriceGroupForPurchasePrice: function(combo, newValue, oldValue) {
        var me = this;
        var exportConfigForm = me.getExportConfigForm();
        exportConfigForm.setLoading(true);
        exportConfigForm.priceFieldForPurchasePrice.store.load({
            params: {
                'customerGroup': newValue
            },
            callback: function() {
                exportConfigForm.setLoading(false);
            }
        });
    },

    /**
     * Checks price field selection, if it's not supported
     * shows error message
     *
     * @param combo
     * @param newValue
     * @param oldValue
     */
    onChangePriceFieldForPurchasePrice: function(combo, newValue, oldValue) {
        var me = this;

        var newRecord = combo.findRecordByValue(newValue);
        if (newRecord && newRecord.get('available') === false) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', me.messages.priceFieldIsNotSupported);
        }
    },

    /**
     * Checks price field selection, if it's not supported
     * shows error message
     *
     * @param combo
     * @param newValue
     * @param oldValue
     */
    onChangePriceFieldForPrice: function(combo, newValue, oldValue) {
        var me = this;

        var newRecord = combo.findRecordByValue(newValue);
        if (newRecord && newRecord.get('available') === false) {
            me.createGrowlMessage('{s name=connect/error}Error{/s}', me.messages.priceFieldIsNotSupported);
        }
    },

    onShowPriceWindow: function() {
        var me = this;

        me.customerGroupStore = Ext.create('Shopware.apps.Connect.store.config.CustomerGroup').load({
            callback: function(){
                me.exportWindow = me.getView('export.price.Window').create({
                    customerGroupStore: me.customerGroupStore
                }).show();
            }
        });
    },

    /**
     * Resets store
     * @param column
     */
    rejectPriceConfigChanges: function (column) {
        var tabs = column.up('panel').up('panel').items;

        tabs.each(function(tab){
            tab.getStore().rejectChanges();
        });
    },

    /**
     * Collects prices params from given tab panel
     *
     * @param tabPanel
     * @param exportMode
     * @param collection
     */
    collectPriceParams: function(tabPanel, exportMode, collection) {
        var me = this,
            priceTypes = ['price', 'pseudoPrice', 'basePrice'],
            exportPriceType;

        switch (exportMode) {
            case 'purchasePrice':
                exportPriceType = 'ForPurchasePriceExport';
                break;
            case 'price':
                exportPriceType = 'ForPriceExport';
                break;
        }

        tabPanel.items.each(function(tab) {
            if (tab.getStore().getUpdatedRecords().length > 0) {
                collection['priceGroup' + exportPriceType] = tab.customerGroup.get('key');
                collection.exportPriceMode.push(exportMode);

                for (var i = 0; i < priceTypes.length; i++){
                    if (tab.getStore().getAt(0).get(priceTypes[i]) == true) {
                        collection['priceField' + exportPriceType] = priceTypes[i];
                    }
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
