//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/filter"}
Ext.define('Shopware.apps.Bepado.view.import.Filter', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-import-filter',

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
        //me.categoryFilter = me.getCategoryFilter();
        me.supplierFilter = me.getSupplierFilter();
        me.searchFilter = me.getSearchFilter();

        Ext.applyIf(me, {
            items: [
                me.statusFilter, me.searchFilter,
                me.supplierFilter
                //me.categoryFilter
            ]
        });

        me.callParent(arguments);
    },

    getCategoryFilter: function() {
        return {
            xtype: 'treepanel',
            title: 'Category filter',
            rootVisible: false,
            root: {
                id: 1,
                expanded: true
            },
            store: 'base.CategoryTree',
            flex: 2
        }
    },

    getStatusFilter: function() {
        return {
            xtype: 'form',
            title: 'Active filter',
            //bodyPadding: 5,
            items: [{
                xtype: 'fieldcontainer',
                defaultType: 'radiofield',
                items: [{
                        boxLabel  : 'Show all',
                        name      : 'active',
                        inputValue: '',
                        checked   : true,
                        id        : 'checkbox1'
                    }, {
                        boxLabel  : 'Show only active',
                        name      : 'active',
                        inputValue: '1',
                        id        : 'checkbox2'
                    }, {
                        boxLabel  : 'Show only inactive',
                        name      : 'active',
                        inputValue: '0',
                        id        : 'checkbox3'
                    }
                ]
            }]
        }
    },

    getSupplierFilter: function() {
        return {
            xtype: 'form',
            title: 'Supplier filter',
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
            title: 'Search',
            height: 65,
            bodyPadding: 5,
            items: [{
                xtype:'textfield',
                name:'searchfield',
                anchor: '100%',
                cls:'searchfield',
                emptyText:'{s name=search/empty_text}Search...{/s}',
                enableKeyEvents:true,
                checkChangeBuffer:500
            }]
        }
    }
});
//{/block}