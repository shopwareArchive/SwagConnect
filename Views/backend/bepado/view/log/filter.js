//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/log/filter"}
Ext.define('Shopware.apps.Connect.view.log.Filter', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-log-filter',

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
        me.errorFilter = me.getErrorFilter();

        Ext.applyIf(me, {
            items: [
                me.searchFilter, me.commandFilter, me.errorFilter
            ]
        });

        me.callParent(arguments);
    },

    getErrorFilter: function() {
        return {
            xtype: 'form',
            title: '{s name=log/filter/error}Error filter{/s}',
            //bodyPadding: 5,
            items: [{
                xtype: 'fieldcontainer',
                defaultType: 'radiofield',
                items: [{
                    boxLabel  : '{s name=import/filter/active_all}Show all{/s}',
                    name      : 'error',
                    inputValue: '-1',
                    checked   : true
                }, {
                    boxLabel  : '{s name=log/filter/error_true}Show only errors{/s}',
                    name      : 'error',
                    inputValue: '0'
                }, {
                    boxLabel  : '{s name=log/filter/error_false}Show only success{/s}',
                    name      : 'error',
                    inputValue: '1'
                }
                ]
            }]
        }
    },

    /**
     * Creates the filter for the connect command
     *
     * @returns Ext.form.Panel
     */
    getCommandFilter: function() {
        var me = this;

        me.commandFilter = Ext.create('Ext.form.Panel', {
            xtype: 'form',
            title: '{s name=log/filter/command_title}Command filter{/s}',
            items: [{
                xtype: 'fieldcontainer',
                defaultType: 'checkboxfield',
                items: []
            }]
        });

        return me.commandFilter;
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