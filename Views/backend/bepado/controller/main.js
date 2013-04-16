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
        'export.List', 'export.Filter'
    ],
    stores: [ 'main.Navigation', 'export.List', 'main.Mapping', 'main.Category' ],
    models: [ 'export.List', 'main.Mapping' ],

    refs: [
        { ref: 'window', selector: 'bepado-window' },
        { ref: 'navigation', selector: 'bepado-navigation' },
        { ref: 'panel', selector: 'bepado-panel' },
        { ref: 'configForm', selector: 'bepado-config' },
        { ref: 'exportList', selector: 'bepado-export-list' }
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
            'bepado-export-list button[action=save]': {
               click: function() {
                   var records = me.selModel.getSelection(), ids = [];

                   records.each(function(record) {
                       ids.push(record.get('id'));
                   });

                   Ext.Ajax.request({
                       url: '{url action=selectProducts}',
                       method: 'POST',
                       params: {
                           ids: ids
                       },
                       success: function(response, opts) {
                           var operation = Ext.decode(response.responseText);
                           if (operation.success == true) {

                           }
                       }
                   });

               }
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
            'bepado-export-filter base-element-select[name=supplier]': {
                change: function(field, value) {
                    var table = me.getExportList(),
                        store = table.getStore();

                    if (!value) {
                        store.clearFilter();
                    } else {
                        store.filters.clear();
                        store.filter(
                            'supplierId',
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
            }
        });

        me.callParent(arguments);
    },

    createGrowlMessage: function(record, title, message) {
        var me = this,
            win = me.getWindow(),
            data = Ext.clone(record.data);

        title = new Ext.Template(title).applyTemplate(data);
        message = new Ext.Template(message).applyTemplate(data);
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
