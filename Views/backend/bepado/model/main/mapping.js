//{block name="backend/bepado/model/main/mapping"}
Ext.define('Shopware.apps.Bepado.model.main.Mapping', {
    extend: 'Ext.data.Model',

    fields: [
        //{block name="backend/bepado/model/main/mapping/fields"}{/block}
        { name: 'mapping' }
    ]
    ,
    associations: [{
        type: 'hasMany',
        model: 'Shopware.apps.Bepado.model.main.Category',
        name: 'getCategories',
        associationKey: 'categories'
    }]
});
//{/block}