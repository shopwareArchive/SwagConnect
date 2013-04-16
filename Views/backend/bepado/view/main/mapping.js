//{namespace name=backend/bepado/view/main}

//{block name='backend/bepado/view/main/mapping'}
Ext.define('Shopware.apps.Bepado.view.main.Mapping', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-mapping',

    //border: false,
    layout: 'fit',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'treepanel',
                //title: 'Category filter',
                rootVisible: false,
                root: {
                    id: 1,
                    expanded: true
                },
                store: 'main.Mapping',
                plugins: [{
                    ptype: 'cellediting',
                    pluginId: 'cellediting',
                    clicksToEdit: 1
                }],
                columns: [{
                    xtype: 'treecolumn',
                    flex: 1,
                    dataIndex: 'text',
                    text: 'Category'
                },{
                    text: 'Mapping',
                    flex: 1,
                    dataIndex: 'mapping',
                    editor: {
                        xtype: 'base-element-selecttree',
                        allowBlank: true,
                        store: 'main.Category'
                    }
                }],
                dockedItems: [ me.getButtons() ]
            }]
        });

        me.callParent(arguments);
    },

    getButtons: function() {
        var me = this;

        return {
            dock: 'bottom',
            xtype: 'toolbar',
            items: ['->', {
                text: '{s name=form/save_text}Save{/s}',
                cls: 'primary',
                action: 'save'
            }]
        };
    }
});
//{/block}