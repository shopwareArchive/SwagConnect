//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/config/window"}
Ext.define('Shopware.apps.Connect.view.config.Window', {
    extend: 'Enlight.app.Window',
    alias: 'widget.connect-config-window',
    cls: Ext.baseCSSPrefix + 'connect',

    layout: 'border',
    width: 1000,
    height: '95%',
    title: Ext.String.format('{s name=window/title}[0]{/s}', marketplaceName),
    titleTemplate: Ext.String.format('{s name=window/title_template}[0] - [text]{/s}', marketplaceName),
    snippets: {
        priceFieldsNotConfigured: "{s name=export/price_fields/not_configured}To export product, you need to configure price fields under Settings tab export{/s}"
    },

    /**
     *
     */
    initComponent: function() {
        var me = this;

        me.items = me.getItems();

        me.callParent(arguments);
    },

    /**
     * Creates the fields sets and the sidebar for the detail page.
     * @return Array
     */
    getItems: function() {
        var me = this;

        return [
            Ext.create('Shopware.apps.Connect.view.config.TabPanel', {
                region: 'center',
                customerGroupStore: me.customerGroupStore,
                action : me.action
            })
        ];
    }
});
//{/block}