//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/config/tabs"}
Ext.define('Shopware.apps.Bepado.view.config.Tabs', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.bepado-config-tabs',

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


        me.tab1 = Ext.create('Ext.form.Panel', {
            autoScroll: true,
            title: 'Tab 1',
            border: false,
            layout: 'fit',
            items: []
        });

        me.tab2 = Ext.create('Ext.form.Panel', {
            autoScroll: true,
            title: 'Tab 2',
            border: false,
            layout: 'fit',
            items: []
        });

        return [me.tab1, me.tab2];
    }
});
//{/block}