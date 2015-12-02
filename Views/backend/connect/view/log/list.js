//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/log/list"}
Ext.define('Shopware.apps.Connect.view.log.List', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.connect-log-list',

    border: false,

    store: 'log.List',

    snippets: {
        logLabel: '{s name=config/log_label}Logging aktivieren{/s}'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            dockedItems: [
                me.getToolbar(),
                me.getPagingToolbar()
            ],
            columns: me.getColumns()
        });

        me.callParent(arguments);

        me.store.load();
    },

    getColumns: function() {
        var me = this;

        return [{
            header: '{s name=log/columns/service}Service{/s}',
            dataIndex: 'service',
            flex: 2
        }, {
            header: '{s name=log/columns/command}Command{/s}',
            dataIndex: 'command',
            flex: 2
        }, {
            header: '{s name=log/columns/error}Error{/s}',
            xtype: 'booleancolumn',
            dataIndex: 'isError',
            renderer: function(value, metaData, record) {
                return value ? 'error' : '';
            },
            flex: 1
        }, {
            header: '{s name=log/columns/time}Time{/s}',
            dataIndex: 'time',
            flex: 4
        }];
    },


    /**
     * Creates a paging toolbar with additional page size selector
     *
     * @returns Array
     */
    getPagingToolbar: function() {
        var me = this;
        var pageSize = Ext.create('Ext.form.field.ComboBox', {
            labelWidth: 120,
            cls: Ext.baseCSSPrefix + 'page-size',
            queryMode: 'local',
            width: 180,
            listeners: {
                scope: me,
                select: function(combo, records) {
                    var record = records[0],
                        me = this;

                    me.store.pageSize = record.get('value');
                    me.store.loadPage(1);
                }
            },
            store: Ext.create('Ext.data.Store', {
                fields: [ 'value' ],
                data: [
                    { value: '20' },
                    { value: '40' },
                    { value: '60' },
                    { value: '80' },
                    { value: '100' },
                    { value: '250' },
                    { value: '500' },
                ]
            }),
            displayField: 'value',
            valueField: 'value',
            editable: false,
            emptyText: '20'
        });
        pageSize.setValue(me.store.pageSize);

        var pagingBar = Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock:'bottom',
            displayInfo:true
        });

        pagingBar.insert(pagingBar.items.length - 2, [ { xtype: 'tbspacer', width: 6 }, pageSize ]);
        return pagingBar;
    },

    getToolbar: function() {
        var me = this;
        return {
            xtype: 'toolbar',
            ui: 'shopware-ui',
            dock: 'top',
            border: false,
            items: me.getTopBar()
        };
    },

    getTopBar:function () {
        var me = this;
        var items = [];
        var loggingCheckbox = Ext.create('Ext.form.field.Checkbox', {
            boxLabel: me.snippets.logLabel,
            name: 'logRequest',
            inputValue: 1,
            uncheckedValue: 0
        });

        items.push('->');
        items.push(loggingCheckbox);
        items.push({
            iconCls:'sprite-minus-circle-frame',
            text:'{s name=log/clear}Clear log{/s}',
            action:'clear'
        });

        Ext.Ajax.request({
            scope: me,
            url: '{url controller=ConnectConfig action=getLoggingEnabled}',
            success: function(result, request) {
                var response = Ext.JSON.decode(result.responseText);
                if (response.success === false) {
                    loggingCheckbox.setRawValue(0);
                }

                var loggingEnable = response.enableLogging ? 1 : 0;
                loggingCheckbox.setRawValue(loggingEnable);
            }
        });

        return items;
    }
});
//{/block}