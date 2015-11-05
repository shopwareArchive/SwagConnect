//{block name="backend/connect/model/main/category"}
Ext.define('Shopware.apps.Connect.model.main.Category', {
    extend: 'Shopware.apps.Base.model.Category',

    fields: [
        //{block name="backend/connect/model/main/category/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'name',  type: 'string' }
    ]
});
//{/block}