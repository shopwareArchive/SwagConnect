//{namespace name=backend/bepado/view/main}

//{block name='backend/bepado/view/main/mapping'}
Ext.define('Shopware.apps.Bepado.view.main.Mapping', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-mapping',

    //border: false,
    layout: 'border',
    
    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                me.getNotificationBox(),
            {
                xtype: 'treepanel',
                region: 'center',
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
                    text: '{s name=mapping/columns/category}Category{/s}'
                },{
                    text: '{s name=mapping/columns/mapping}Mapping{/s}',
                    flex: 1,
                    dataIndex: 'mapping',
                    editor: {
                        xtype: 'base-element-selecttree',
                        allowBlank: true,
                        store: 'main.Category'
                    }
                }, me.getActionColumn()],
                dockedItems: [ me.getButtons() ]
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

    getNotificationBox: function() {
        var me = this,
            notice;

        notice = Shopware.Notification.createBlockMessage("{s name=mapping/info}The vendor category tree can only be used for importing products. Mappings for export will not apply.{/s}", 'error');

        notice.margin = 10;
        notice.region = 'north';
        return notice;
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
                iconCls: 'sprite-folder-tree',
                action: 'importCategories',
                tooltip: '{s name=mapping/options/importCategories}Import categories from bepado{/s}',
                handler: function (view, rowIndex, colIndex, item, opts, record) {
                    me.fireEvent('importCategories', record);
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
    }
});
//{/block}