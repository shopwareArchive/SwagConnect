//{block name="backend/bepado/model/config/prices"}
Ext.define('Shopware.apps.Bepado.model.config.Prices', {
    extend: 'Ext.data.Model',

    fields: [
        //{block name="backend/bepado/model/config/prices/fields"}{/block}
        { name: 'bepadoField', type: 'string', useNull: false },
        { name: 'customerGroup', type: 'string', useNull: false },
        { name: 'priceField', type: 'string', useNull: false }
    ],

    proxy: {
        /**
         * Set proxy type to ajax
         * @string
         */
        type: 'ajax',

        /**
         * Configure the url mapping for the different
         * store operations based on
         * @object
         */
        api: {
            create: '{url action="savePriceConfig"}',
            update: '{url action="savePriceConfig"}'
        },

        /**
         * Configure the data reader
         * @object
         */
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
//{/block}