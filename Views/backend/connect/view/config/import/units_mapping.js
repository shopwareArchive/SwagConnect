//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/config/import/panel"}
Ext.define('Shopware.apps.Connect.view.config.import.UnitsMapping', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.connect-units-mapping-list',
    store: 'config.ConnectUnits',

    border: false,

    selModel: "cellmodel",

    snippets: {
        connectUnitsHeader: '{s name=config/units/connect_units_header}importierte Maßeinheiten{/s}',
        localUnitsHeader: '{s name=config/units/shopware_units_header}Meine Maßeinheiten{/s}'
    },

    initComponent: function() {
        var me = this;

        me.unitsStore = Ext.create('Shopware.apps.Connect.store.config.Units').load();
        me.connectUnitsStore = Ext.create('Shopware.apps.Connect.store.config.ConnectUnits').load();
        me.plugins = [ me.createCellEditor() ];
        me.store = me.connectUnitsStore;

        Ext.applyIf(me, {
            height: 300,
            width: 400,
            columns: me.getColumns()
        });

        me.callParent(arguments);
    },

    getColumns: function() {
        var me = this;

        return [
            {
                header: me.snippets.connectUnitsHeader,
                dataIndex: 'name',
                flex: 1
            },
            {
                header: me.snippets.localUnitsHeader,
                dataIndex: 'shopwareUnitKey',
                flex: 1,
                editor: {
                    xtype: 'combo',
                    store: me.unitsStore,
                    displayField: 'shopwareUnitName',
                    valueField: 'shopwareUnitKey'
                },
                renderer: function (value) {
                    var index = me.unitsStore.findExact('shopwareUnitKey', value);
                    if (index > -1) {
                        return me.unitsStore.getAt(index).get('shopwareUnitName');
                    }

                    return value;
                }
            }
        ];
    },

    createCellEditor: function() {
        var me = this;

        me.cellEditor = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToMoveEditor: 1,
            autoCancel: true
        });

        return me.cellEditor;
    }
});
//{/block}
