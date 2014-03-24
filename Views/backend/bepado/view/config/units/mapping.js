//{namespace name=backend/bepado/view/main}

//{block name='backend/bepado/view/config/units/mapping'}
Ext.define('Shopware.apps.Bepado.view.config.units.Mapping', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-units-mapping',

    layout: 'fit',

    snippets: {

    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'gridpanel',
                    store: 'config.Units',
                    selModel: 'cellmodel',
                    plugins: [ me.createCellEditor() ],
                    columns: [{
                        header: '{s name=config/units/shopware_unit_header}Shopware unit{/s}',
                        dataIndex: 'shopwareUnitName',
                        flex: 1
                    }, {
                        header: '{s name=config/units/bepado_unit_header}bepado unit{/s}',
                        dataIndex: 'bepadoUnit',
                        flex: 1
                        ,
                        editor: {
                            xtype: 'combo',
                            store: []
                        }
                    }]
                }
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