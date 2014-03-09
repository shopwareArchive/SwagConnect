//{namespace name=backend/bepado/view/main}
//{block name="backend/bepado/view/config/tabs"}
Ext.define('Shopware.apps.Bepado.view.config.Tabs', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.bepado-config-tabs',

    layout: 'fit',
    autoScroll: true,
    border: true,
    activeTab: 0,
    items: [],

    initComponent: function() {
        var me = this;

        me.shopStore = Ext.create('Shopware.apps.Base.store.Shop', {
            filters: []
        }).load({
            callback: function () {
                me.add(me.createItems());
                me.setActiveTab(0);
            }
        });


        me.callParent(arguments);
    },

    /**
     * Creates the actual tabs for the known fields
     */
    createItems: function() {
        var me = this;

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