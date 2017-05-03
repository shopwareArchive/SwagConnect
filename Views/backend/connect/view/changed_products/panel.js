//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/changed_products/panel"}
Ext.define('Shopware.apps.Connect.view.changed_products.Panel', {
    extend: 'Ext.container.Container',
    alias: 'widget.connect-changed-products',

    border: false,
    layout: 'border',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [{
                xtype: 'connect-changed-products-tabs',
                region: 'south',
                collapsible: true,
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
            case 'additionalDescription':
                return '{s name=changed_products/title/additionalDescription}Connect Description{/s}';
            case 'imageInitialImport':
                return '{s name=changed_products/title/imageInitialImport}imageInitialImport{/s}';
            default:
                return title;
        }
    }
});
//{/block}