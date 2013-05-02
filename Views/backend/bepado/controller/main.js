//{namespace name=backend/bepado/view/main}

/**
 * Shopware Controller - Cache backend module
 */
//{block name="backend/bepado/controller/main"}
Ext.define('Shopware.apps.Bepado.controller.Main', {

    extend: 'Enlight.app.Controller',

    views: [
        'main.Window', 'main.Navigation',
        'main.Panel', 'main.Config', 'main.Mapping',
        'export.Panel', 'import.Panel',
        'export.List', 'export.Filter',
        'import.List', 'import.Filter'
    ],
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

    },

    /**
     * Class property which holds the main application if it is created
     *
     * @default null
     * @object
     */
    mainWindow: null,

    /**
     *
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
                click: function(button) {
                    var me = this,
                        panel = me.getMapping();
                    panel.setLoading();
                    panel.store.sync({
                        success :function (records, operation) {
                            panel.setLoading(false);
                            //me.createGrowlMessage(title, message, win.title);
                        },
                        failure:function (batch) {
                            panel.setLoading(false);
                            //if(batch.proxy.reader.rawData.message) {
                            //    message += '<br />' + batch.proxy.reader.rawData.message;
                            //}
                            //me.createGrowlMessage(title, message);
                        }
                    });
                }
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

    onExportFilterAction: function(btn) {
        var me = this,
            list = me.getExportList(),
            records = list.selModel.getSelection(),
            ids = [], url;

        if(btn.action == 'add') {
            url = '{url action=insertOrUpdateProduct}';
        } else if(btn.action == 'delete') {
            url = '{url action=deleteProduct}';
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
                var operation = Ext.decode(response.responseText);
                if (operation.success == true) {

                }
                list.setLoading(false);
                list.store.load();
            }
        });
    },

    onImportFilterAction: function(btn) {
        var me = this,
            list = me.getImportList(),
            records = list.selModel.getSelection(),
            ids = [], url;

        if(btn.action == 'activate') {
            url = '{url action=updateProduct}?active=1';
        } else if(btn.action == 'deactivate') {
            url = '{url action=updateProduct}?active=0';
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
                var operation = Ext.decode(response.responseText);
                if (operation.success == true) {

                }
                list.setLoading(false);
                list.store.load();
            }
        });
    },

    createGrowlMessage: function(title, message) {
        var me = this,
            win = me.getWindow();
        Shopware.Notification.createGrowlMessage(title, message, win.title);
    },

    onSelectNavigationEntry: function(tree, node) {
        var me = this,
            panel = me.getPanel(),
            win = me.getWindow(),
            item = node.get('id'),
            layout = panel.getLayout();

        layout.setActiveItem(item);
        win.loadTitle(node);
    },

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
