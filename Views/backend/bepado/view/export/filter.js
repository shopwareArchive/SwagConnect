//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/export/filter"}
Ext.define('Shopware.apps.Bepado.view.export.Filter', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-export-filter',

    width: 200,
    layout: {
        type: 'vbox',
        align : 'stretch',
        pack  : 'start'
    },
    //layout: {
    //    type: 'accordion',
    //    animate: Ext.isChrome
    //},
    animCollapse: Ext.isChrome,
    border: false,

    initComponent: function() {
        var me = this;

        me.statusFilter = me.getStatusFilter();
        me.categoryFilter = me.getCategoryFilter();
        me.supplierFilter = me.getSupplierFilter();
        me.searchFilter = me.getSearchFilter();

        Ext.applyIf(me, {
            items: [
                me.statusFilter, me.searchFilter,
                me.supplierFilter, me.categoryFilter
            ]
        });

        me.callParent(arguments);
    },

    getCategoryFilter: function() {
        var me = this;
        return {
            xtype: 'treepanel',
            id: 'export-category-filter',
            title: '{s name=export/filter/category_title}Category filter{/s}',
            rootVisible: false,
            root: {
                id: 1,
                expanded: true
            },
            store: 'base.CategoryTree',
            flex: 2,
            dockedItems: [
                me.createTreeBottomBar()
            ]
        }
    },

    createTreeBottomBar: function () {
        return { xtype: 'toolbar',
            dock: 'bottom',
            items: [{
                    xtype: 'button',
                    text: '{s name=export/filter/clear_category_filter}Clear category filter{/s}',
                    action: 'category-clear-filter'
            }]
        }
    },

    getStatusFilter: function() {
        return {
            xtype: 'form',
            title: '{s name=export/filter/status_title}Status filter{/s}',
            //bodyPadding: 5,
            items: [{
                xtype: 'fieldcontainer',
                defaultType: 'radiofield',
                items: [{
                    boxLabel  : '{s name=export/filter/status_all}Show all{/s}',
                    name      : 'exportStatus',
                    inputValue: ''
                }, {
                    boxLabel  : '{s name=export/filter/status_online}Online{/s}',
                    name      : 'exportStatus',
                    inputValue: 'online'
                }, {
                    boxLabel  : '{s name=export/filter/status_error}Error{/s}',
                    name      : 'exportStatus',
                    inputValue: 'error'
                }, {
                    boxLabel  : '{s name=export/filter/status_insert}Inserting{/s}',
                    name      : 'exportStatus',
                    inputValue: 'insert'
                }, {
                    boxLabel  : '{s name=export/filter/status_update}Updating{/s}',
                    name      : 'exportStatus',
                    inputValue: 'update'
                }, {
                    boxLabel  : '{s name=export/filter/status_delete}Delete{/s}',
                    name      : 'exportStatus',
                    inputValue: 'delete',
                    checked   : true
                }
                ]
            }]
        }
    },

    getSupplierFilter: function() {
        return {
            xtype: 'form',
            title: '{s name=export/filter/supplier_title}Supplier filter{/s}',
            height: 65,
            bodyPadding: 5,
            items: [{
                xtype: 'base-element-select',
                name: 'supplierId',
                anchor: '100%',
                allowBlank: true,
                store: 'base.Supplier'
            }]
        }
    },

    getSearchFilter: function() {
        return {
            xtype: 'form',
            title: '{s name=export/filter/search_title}Search{/s}',
            height: 65,
            bodyPadding: 5,
            items: [{
                xtype:'textfield',
                name:'searchfield',
                anchor: '100%',
                cls:'searchfield',
                emptyText:'{s name=export/filter/search_empty}Search...{/s}',
                enableKeyEvents:true,
                checkChangeBuffer:500
            }]
        }
    }
});
//{/block}