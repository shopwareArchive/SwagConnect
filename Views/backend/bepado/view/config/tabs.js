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

        me.shopStore = me.subApp.getController('Main').shopStore;

        var tabs = [];
        me.shopStore.each(function() {
            var record = this;

            me.generalForm = Ext.create('Shopware.apps.Bepado.view.config.general.Form', {
                shopId: record.get('id'),
                defaultShop: record.get('default')
            });

            var tab = Ext.create('Ext.form.Panel', {
                autoScroll: true,
                title: record.get('name'),
                border: false,
                layout: 'fit',
                items: [ me.generalForm ]
            });

            tabs.push(tab);
        });
        return tabs;
    }
});
//{/block}