//{block name="backend/bepado/model/main/category"}
Ext.define('Shopware.apps.Bepado.model.main.Category', {
    extend: 'Shopware.apps.Base.model.Category',

    fields: [
        //{block name="backend/bepado/model/main/category/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'name',  type: 'string' }
    ]
});
//{/block}