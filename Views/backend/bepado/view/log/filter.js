//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/log/filter"}
Ext.define('Shopware.apps.Bepado.view.log.Filter', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-log-filter',

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

        me.commandFilter = me.getCommandFilter();
        me.searchFilter = me.getSearchFilter();

        Ext.applyIf(me, {
            items: [
                me.commandFilter, me.searchFilter
            ]
        });

        me.callParent(arguments);
    },

    getCommandFilter: function() {
        return {
            xtype: 'form',
            title: '{s name=log/filter/command_title}Command filter{/s}',
            items: [{
                xtype: 'fieldcontainer',
                defaultType: 'checkboxfield',
                items: [{
                        boxLabel  : '{s name=log/filter/fromShop}From shop{/s}',
                        name      : 'fromShop',
                        inputValue:  true,
                        checked   :  true,
                        filter    : 'commandFilter'
                    }, {
                        boxLabel  : '{s name=log/filter/toShop}To shop{/s}',
                        name      : 'toShop',
                        inputValue:  true,
                        checked   :  true,
                        filter    : 'commandFilter'
                    }, {
                        boxLabel  : '{s name=log/filter/update}Update{/s}',
                        name      : 'update',
                        inputValue:  true,
                        checked   :  true,
                        filter    : 'commandFilter'
                    }, {
                        boxLabel  : '{s name=log/filter/getLastRevision}Last revision{/s}',
                        name      : 'getLastRevision',
                        inputValue:  true,
                        checked   :  true,
                        filter    : 'commandFilter'
                    }
                ]
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