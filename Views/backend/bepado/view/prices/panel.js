//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/prices/panel"}
Ext.define('Shopware.apps.Bepado.view.prices.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-prices',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this;


        Ext.applyIf(me, {
            items: [{
                xtype: 'label',
                html: '{s name=config/prices/label}<strong>Export to bepado:</strong> In this grid you can configure, where you want to manage your price and your purchase price. When exporting products, the plugin will export the configured fields of the configured customer group as the price/purchase price of your products.{/s}',
                padding: 10,
                region: 'north'
            },{
                xtype: 'bepado-prices-list',
                region: 'center'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}