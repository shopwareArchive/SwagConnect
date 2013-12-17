//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/changed_products/panel"}
Ext.define('Shopware.apps.Bepado.view.changed_products.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-changed-products',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this,
            notice = Shopware.Notification.createBlockMessage("{s name=changed_products/info}These products have been updated by the supplier recently. As you manage some fields of the products manually, some changes where not applied.{/s}", 'error');;


        notice.region = 'north';
        notice.margin = 10;

        Ext.applyIf(me, {
            items: [notice,{
                xtype: 'bepado-changed-products-tabs',
                region: 'south',
                collapsible: true,
                collapsed: true,
                split: true
            },{
                xtype: 'bepado-changed-products-list',
                region: 'center'
            }]
        });

        me.callParent(arguments);
    }
});
//{/block}