//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/log/tabs"}
Ext.define('Shopware.apps.Bepado.view.log.Tabs', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.bepado-log-tabs',

    height: 300,

    border: false,

    initComponent: function() {
        var me = this;

        me.items = me.createItems();

        me.callParent(arguments);
    },

    /**
     * Creates the actual tabs for the known fields
     */
    createItems: function() {
        var me = this;


        me.requestPanel = Ext.create('Ext.form.Panel', {
            autoScroll: true,
            title: 'Request',
            border: false,
            layout: 'fit',
            items: [{
                xtype: 'textarea',
                name: 'request'
            }]
        });

        me.responsePanel = Ext.create('Ext.form.Panel', {
            autoScroll: true,
            title: 'Response',
            border: false,
            layout: 'fit',
            items: [{
                xtype: 'textarea',
                name: 'response'
            }]
        });

        return [me.requestPanel, me.responsePanel];
    }
});
//{/block}