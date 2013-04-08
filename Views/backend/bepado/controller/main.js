//{namespace name=backend/bepado/view/main}

/**
 * Shopware Controller - Cache backend module
 */
//{block name="backend/bepado/controller/main"}
Ext.define('Shopware.apps.Bepado.controller.Main', {

    extend: 'Enlight.app.Controller',

    views: [
        'main.Window', 'main.Navigation', 'main.Panel'
    ],
    stores: [ 'main.Navigation' ],
    models: [ ],

    refs: [
        { ref: 'window', selector: 'bepado-window' },
        { ref: 'list', selector: 'bepado-list' },
        { ref: 'panel', selector: 'bepado-panel' },
        { ref: 'form', selector: 'bepado-form' }
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
            'bepado-list': {
                select: me.onSelectListEntry,
                selectionchange: me.onChangeListEntry
            },
            'bepado-list button[action=removeGroup]': {
                click: me.onRemoveListEntry
            },
            'bepado-list button[action=removeOption]': {
                click: me.onRemoveListEntry
            },
            'bepado-list button[action=addGroup]': {
                click: me.onAddListEntry
            },
            'bepado-list button[action=addOption]': {
                click: me.onAddListEntry
            },
            'bepado-form button[action=save]': {
                click: me.onSaveEntry
            },
            'bepado-value button[action=add]': {
                click: me.onAddValueEntry
            },
            'bepado-value': {
                delete: me.onDeleteValue
            }
        });

        me.callParent(arguments);
    },

    onDeleteValue: function(panel, record) {
        var me = this,
            store = panel.getStore();
        store.remove(record);
    },

    onAddValueEntry: function(button) {
        var me = this,
            table = button.up('grid'),
            fields = table.query('[isFormField]'),
            store = table.getStore(),
            data = { }, fieldData;
        if(!table) {
            return;
        }
        Ext.each(fields, function(field) {
            fieldData = field.getModelData();
            data = Ext.apply(data, fieldData);
        });
        var record = store.add(data)[0],
            plugin = table.getPlugin('cellediting');
        //plugin.startEdit(record, table.columns[0]);
    },

    onAddListEntry: function(button) {
        var me = this, record,
            list = me.getList(),
            selection = list.getSelectionModel().getLastSelected();

        record = me.getModel(button.model).create();
        if(selection && button.model == 'main.Option') {
            record.set('groupId', selection.get('groupId'));
            if(selection.get('position')) {
                record.set('position', selection.get('position') + 1);
            }
        }
        me.loadPanel(button.model, record);
    },

    onRemoveListEntry: function(button) {
        var me = this,
            list = me.getList(),
            selection = list.getSelectionModel().getLastSelected(),
            title = new Ext.Template(me.messages.deleteEntryTitle),
            message = new Ext.Template(me.messages.deleteEntryMessage),
            data = Ext.clone(selection.data),
            panel = me.getPanel();

        title = title.applyTemplate(data);
        message = message.applyTemplate(data);

        Ext.MessageBox.confirm(title, message, function (response) {
            if (response !== 'yes') {
                return;
            }

            panel.removeAll(true);
            selection.destroy({
                callback: function (self, operation) {
                    if (operation.success) {
                        message = me.messages.deleteEntrySuccess;
                    } else {
                        message = me.messages.deleteEntryError;
                        var rawData = operation.records[0].proxy.reader.rawData;
                        if (rawData.message) {
                            message += '<br />' + rawData.message;
                        }
                    }
                    me.createGrowlMessage(selection, title, message);
                }
            });
        });
    },

    onSaveEntry: function(button) {
        var me = this,
            form = button.up('form'),
            basicForm = form.getForm(),
            record = form.getRecord(),
            store = form.store;

        if(!basicForm.isValid()) {
            return;
        }
        if(!record) {
            return;
        }
        basicForm.updateRecord();
        if(!record.store) {
            store.add(record);
        }

        form.setLoading();
        //form.loadRecord();

        var message,
            title = me.messages.saveEntryTitle;

        store.sync({
            success :function (records, operation) {
                message = me.messages.saveEntrySuccess;
                me.createGrowlMessage(record, title, message);
                me.onAfterSaveForm();
            },
            failure:function (operation) {
                message = me.messages.saveEntryError;
                if(operation.proxy.reader.rawData.message) {
                    message += '<br />' + operation.proxy.reader.rawData.message;
                }
                me.createGrowlMessage(record, title, message);
                me.onAfterSaveForm();
            }
        });
    },

    onAfterSaveForm: function() {
        var me = this,
            form = me.getForm(),
            list = me.getList(),
            sm = list.getSelectionModel(),
            selection = sm.getLastSelected(),
            root = list.getRootNode(),
            node = selection || root;
        node = node.isLeaf() ? node.parentNode : node;

        list.getStore().load({
            node: root,
            callback: function(records, operation) {
                node.expand();
                sm.select(node);
            }
        });

        form.setLoading(false);
        form.destroy();
    },

    createGrowlMessage: function(record, title, message) {
        var me = this,
            win = me.getWindow(),
            data = Ext.clone(record.data);

        title = new Ext.Template(title).applyTemplate(data);
        message = new Ext.Template(message).applyTemplate(data);
        Shopware.Notification.createGrowlMessage(title, message, win.title);
    },

    onSelectListEntry: function(tree, node) {
        var me = this,
            panel = me.getPanel(),
            model = node.isLeaf() ? 'main.Option' : 'main.Group',
            value = node.get(node.isLeaf() ? 'optionId' : 'groupId');

        panel.setLoading(true);

        me.getStore(model).load({
            filters : [{
                property: 'id',
                value: value
            }],
            callback: function(records, operation, success) {
                var record = records[0];
                if(record) {
                    me.loadPanel(model, record);
                }
            }
        });
    },

    onChangeListEntry: function(table, records) {
        var me = this,
            record = records.length ? records[0] : null,
            list = me.getList(),
            buttons = list.query('button');
        Ext.each(buttons, function(button) {
            button.hide().enable();
        });
        if(!record || !record.isLeaf()) {
            list.down('button[action=removeGroup]').show();
        } else {
            list.down('button[action=addOption]').show();
            list.down('button[action=removeOption]').show();
        }
        list.down('button[action=addGroup]').show();
        if(!record) {
            list.down('button[action=removeGroup]').disable();
        } else {
            list.down('button[action=addOption]').show();
        }
    },

    loadPanel: function(model, record) {
        var me = this,
            form, win = me.getWindow(),
            store = me.getStore(model),
            panel = me.getPanel();

        form = me.getView(model).create({
            store: store,
            record: record
        });

        panel.removeAll(true);
        panel.add(form);

        win.loadTitle(model, record);

        form.loadRecord(record);
        record.setDirty();
        record.associations.each(function(association) {
            var store = record[association.name](),
                associationKey = association.associationKey,
                grid = form.down('grid[name=' + associationKey + ']');
            if(grid && store) {
                grid.reconfigure(store);
            }
        });

        panel.setLoading(false);
    }
});
//{/block}
