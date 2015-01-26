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
            title: '{s name=import/filter/category_title}Category filter{/s}',
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
            title: '{s name=import/filter/active_title}Active filter{/s}',
            //bodyPadding: 5,
            items: [{
                xtype: 'fieldcontainer',
                defaultType: 'radiofield',
                items: [{
                        boxLabel  : '{s name=import/filter/active_all}Show all{/s}',
                        name      : 'active',
                        inputValue: '',
                        checked   : true,
                        id        : 'checkbox1'
                    }, {
                        boxLabel  : '{s name=import/filter/active_true}Show only active{/s}',
                        name      : 'active',
                        inputValue: '1',
                        id        : 'checkbox2'
                    }, {
                        boxLabel  : '{s name=import/filter/active_false}Show only inactive{/s}',
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
            title: '{s name=import/filter/supplier_title}Supplier filter{/s}',
            height: 65,
            bodyPadding: 5,
            items: [{
                xtype: 'base-element-select',
                pageSize: 25,
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
            title: '{s name=import/filter/search_title}Search{/s}',
            height: 65,
            bodyPadding: 5,
            items: [{
                xtype:'textfield',
                name:'searchfield',
                anchor: '100%',
                cls:'searchfield',
                emptyText:'{s name=import/filter/search_empty}Search...{/s}',
                enableKeyEvents:true,
                checkChangeBuffer:500
            }]
        }
    }
});
//{/block}