//{block name="backend/bepado/model/config/prices"}
Ext.define('Shopware.apps.Bepado.model.config.Prices', {
    extend:'Ext.data.Model',

    fields: [
        //{block name="backend/bepado/model/config/prices/fields"}{/block}
        { name: 'bepadoField', type: 'string', useNull: false },
        { name: 'customerGroup', type: 'string', useNull: false },
        { name: 'priceField', type: 'string', useNull: false }

    ]
});
//{/block}