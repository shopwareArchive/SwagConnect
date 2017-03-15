
//{block name="backend/connect/model/changed_products/product"}
Ext.define('Shopware.apps.Connect.model.changed_products.Product', {
    extend: 'Ext.data.Model',
    fields: [
        { name: 'shortDescriptionLocal', type: 'string' },
        { name: 'shortDescriptionRemote', type: 'string' },

        { name: 'longDescriptionLocal', type: 'string' },
        { name: 'longDescriptionRemote', type: 'string' },

        { name: 'additionalDescriptionLocal', type: 'string' },
        { name: 'additionalDescriptionRemote', type: 'string' },

        { name: 'nameLocal', type: 'string' },
        { name: 'nameRemote', type: 'string' },

        { name: 'imageLocal', type: 'string' },
        { name: 'imageRemote', type: 'string' },


        { name: 'priceLocal', type: 'float' },
        { name: 'priceRemote', type: 'float' }
    ]
});
//{/block}