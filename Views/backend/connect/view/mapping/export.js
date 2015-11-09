//{namespace name=backend/connect/view/main}

//{block name='backend/connect/view/main/mapping/export'}
Ext.define('Shopware.apps.Connect.view.mapping.Export', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-mapping-export',

    //border: false,
    layout: 'border',

    snippets: {
        emptyMappingMessage: '{s name=mapping/message/please_assign_category}Bitte Kategorie zuordnen{/s}',
        description: Ext.String.format('{s name=mapping/message/export/description}Legen Sie hier fest in welchen Kategorien Ihre Produkte auf [0] angezeigt werden sollen.{/s}', marketplaceName)
    },
    
    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'treepanel',
                region: 'center',
                rootVisible: false,
                root: {
                    id: 1,
                    expanded: true
                },
                store: 'mapping.Export',
                plugins: [{
                    ptype: 'cellediting',
                    pluginId: 'cellediting',
                    clicksToEdit: 1
                }],
                columns: [{
                    xtype: 'treecolumn',
                    flex: 1,
                    dataIndex: 'text',
                    text: '{s name=mapping/columns/shopware-category}Shopware Category{/s}'
                },{
                    text: Ext.String.format('{s name=mapping/columns/connect-category}[0] Kategorie{/s}', marketplaceName),
                    flex: 1,
                    dataIndex: 'mapping',
                    editor: {
                        xtype: 'base-element-selecttree',
                        allowBlank: true,
                        store: 'mapping.ConnectCategoriesExport'
                    },
                    renderer: function (value) {
                        if (!value) {
                            return me.snippets.emptyMappingMessage;
                        }

                        return value;
                    }
                }, me.getActionColumn()],
                dockedItems: [
                    me.getDescriptionBar(),
                    me.getButtons(),
                    me.getToolbar()
                ]
            }]
        });

        me.addEvents(
            /**
             * Fired if the user clicks the "applyToChildren" action button.
             * Will apply the current mapping to all empty child categories
             */
            'applyToChildren'
        );

        me.callParent(arguments);
    },

    getDescriptionBar: function() {
        var me =this;

        return {
            xtype: 'container',
            padding: '10 0 10 10',
            html: me.snippets.description
        };
    },

    getActionColumn: function() {
        var me = this;
        return {
            xtype: 'actioncolumn',
            width: 110,
            items: [{
                iconCls: 'sprite-minus-circle-frame',
                action: 'clear',
                tooltip: '{s name=mapping/options/clear}Clear mapping{/s}',
                handler: function (view, rowIndex, colIndex, item, opts, record) {
                    record.set('mapping', null);
                },
                getClass: function(value, meta, record) {
                    return record.get('mapping') ? 'x-grid-center-icon': 'x-hide-display';
                }
            }, {
                iconCls: 'sprite-arrow-skip-270',
                action: 'assignToChildren',
                tooltip: '{s name=mapping/options/assignToChildren}Use assignment for children, too{/s}',
                handler: function (view, rowIndex, colIndex, item, opts, record) {
                    me.fireEvent('applyToChildren', record);
                },
                getClass: function(value, meta, record) {
                    // Hide, if category has no children
                    return record.get('childrenCount') > 0 ? 'x-grid-center-icon': 'x-hide-display';
                }
            }]
        };
    },

    getButtons: function() {
        var me = this;

        return {
            dock: 'bottom',
            xtype: 'toolbar',
            items: ['->', {
                text: '{s name=mapping/options/save}Save{/s}',
                cls: 'primary',
                action: 'save'
            }]
        };
    },

    getToolbar:function () {
        var me = this;

        me.searchField = Ext.create('Ext.form.field.Text', {
            name:'searchExportMapping',
            cls:'searchExportMapping',
            width:170,
            emptyText: '{s name=search/empty_text}Search...{/s}',
            enableKeyEvents:true,
            checkChangeBuffer:500
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            dock:'top',
            ui: 'shopware-ui',
            cls: 'shopware-toolbar',
            items:[ me.searchField ]
        });
    }
});
//{/block}