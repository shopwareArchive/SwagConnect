//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/changed_products/panel"}
Ext.define('Shopware.apps.Connect.view.changed_products.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-changed-products',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this,
            notice = Shopware.Notification.createBlockMessage("{s name=changed_products/info}These products have been updated by the supplier recently. As you manage some fields of the products manually, some changes where not applied.{/s}", 'error');;


        notice.region = 'north';
        notice.margin = 10;

        Ext.applyIf(me, {
            items: [notice,{
                xtype: 'connect-changed-products-tabs',
                region: 'south',
                collapsible: true,
                collapsed: true,
                split: true,
                getTranslatedTitle: me.getTranslatedTitle
            },{
                xtype: 'connect-changed-products-list',
                region: 'center',
                getTranslatedTitle: me.getTranslatedTitle
            }]
        });

        me.callParent(arguments);
    },

    /**
     * Helper to translate titles like name, price…
     * Is used in the list and the panel
     *
     * @param title
     * @returns string
     */
    getTranslatedTitle: function(title) {
        switch (title) {
            case 'name':
                return '{s name=changed_products/title/name}Name{/s}';
            case 'price':
                return '{s name=changed_products/title/price}Price{/s}';
            case 'image':
                return '{s name=changed_products/title/image}Image{/s}';
            case 'longDescription':
                return '{s name=changed_products/title/longDescription}longDescription{/s}';
            case 'shortDescription':
                return '{s name=changed_products/title/shortDescription}shortDescription{/s}';
            case 'imageInitialImport':
                return '{s name=changed_products/title/imageInitialImport}imageInitialImport{/s}';
            default:
                return title;
        }
    }
});
//{/block}