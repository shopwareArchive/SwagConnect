//{namespace name=backend/connect/view/main}

//{block name='backend/connect/view/config/units/mapping'}
Ext.define('Shopware.apps.Connect.view.config.units.Mapping', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-units-mapping',

    layout: 'fit',

    snippets: {
        unitHeader: Ext.String.format('{s name=config/units/connect_unit_header}[0] Einheit{/s}', marketplaceName)
    },

    initComponent: function() {
        var me = this;
        me.unitsStore = Ext.create('Shopware.apps.Connect.store.config.Units').load();
        me.connectUnitsStore = Ext.create('Shopware.apps.Connect.store.config.ConnectUnits').load();



        Ext.applyIf(me, {
            items: [
                Ext.create('Ext.grid.Panel', {
                    alias: 'widget.connect-units-mapping-list',
                    store: me.unitsStore,
                    selModel: 'cellmodel',
                    plugins: [ me.createCellEditor() ],
                    columns: [{
                        header: '{s name=config/units/shopware_unit_header}Shopware unit{/s}',
                        dataIndex: 'shopwareUnitName',
                        flex: 1
                    }, {
                        header: me.snippets.unitHeader,
                        dataIndex: 'connectUnit',
                        flex: 1,
                        editor: {
                            xtype: 'combo',
                            store: me.connectUnitsStore,
                            displayField: 'name',
                            valueField: 'key'
                        },
                        renderer: function (value) {
                            var index = me.connectUnitsStore.findExact('key', value);
                            if (index > -1) {
                                return me.connectUnitsStore.getAt(index).get('name');
                            }

                            return value;
                        }
                    }]
                })
            ]
        });

        me.callParent(arguments);
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