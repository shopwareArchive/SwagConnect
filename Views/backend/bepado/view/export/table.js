//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/main/value"}
Ext.define('Shopware.apps.Bepado.view.main.Value', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.bepado-export',

    margin: '10 0 0 0',
    border: false,
    viewConfig: {
        emptyText: '{s name=value/empty_text}No values{/s}'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            dockedItems: me.getToolbar(),
            columns: me.getColumns(),
            plugins: me.getPlugins()
        });

        me.addEvents(
            'delete'
        );

        me.callParent(arguments);
    },

    getPlugins: function() {
        var me = this;

        return [{
            ptype: 'cellediting',
            pluginId: 'cellediting',
            clicksToEdit: 1
        }, {
            pluginId: 'translation',
            ptype: 'gridtranslation',
            translationType: 'bepado-value',
            translationKey: me.translationKey
        }];
    },

    getColumns: function() {
        var me = this, columns = [];
        return [{
            header: '{s name=value/columns/value}Value{/s}',
            dataIndex: 'value',
            flex: 2
        }, {
            header: '{s name=value/columns/description}Description{/s}',
            dataIndex: 'description',
            flex: 2
        }, {
            header: '{s name=value/columns/number}Number{/s}',
            dataIndex: 'number',
            flex: 2
        }, {
            header: '{s name=value/columns/position}Position{/s}',
            dataIndex: 'position',
            flex: 1
        }, me.getActionColumn()];
    },

    getActionColumn: function() {
        var me = this;
        return {
            xtype: 'actioncolumn',
            width: 25,
            items: [{
                iconCls: 'sprite-minus-circle-frame',
                action: 'delete',
                tooltip: '{s name=value/delete_tooltip}Delete entry{/s}',
                handler: function (view, rowIndex, colIndex, item, opts, record) {
                    me.fireEvent('delete', view, record, rowIndex);
                }
            }]
        };
    },

    getToolbar: function() {
        var me = this;
        return {
            xtype: 'toolbar',
            dock: 'top',
            border: false,
            cls: 'shopware-toolbar',
            items: me.getTopBar()
        };
    },

    getTopBar: function () {
        var me = this;
        return [{
            iconCls:'sprite-plus-circle-frame',
            cls: 'secondary small',
            text:'{s name=value/add_text}Add entry{/s}',
            action:'add'
        }];
    }
});
//{/block}