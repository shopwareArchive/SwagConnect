//{block name="backend/connect/model/main/mapping"}
Ext.define('Shopware.apps.Connect.model.main.Mapping', {
    extend: 'Shopware.apps.Base.model.Category',

    fields: [
        //{block name="backend/connect/model/main/mapping/fields"}{/block}
        { name: 'mapping',  type: 'string', useNull: true }
    ]
});
//{/block}