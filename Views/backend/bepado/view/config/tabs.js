//{namespace name=backend/connect/view/main}
//{block name="backend/connect/view/config/tabs"}
Ext.define('Shopware.apps.Connect.view.config.Tabs', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.connect-config-tabs',

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
        var me = this,
            tabs = [],
            staticPagesStore = Ext.create('Shopware.apps.Connect.store.config.Pages').load();

        me.shopStore.each(function() {
            var record = this;

            me.generalForm = Ext.create('Shopware.apps.Connect.view.config.general.Form', {
                shopId: record.get('id'),
                defaultShop: record.get('default'),
                staticPagesStore: staticPagesStore
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