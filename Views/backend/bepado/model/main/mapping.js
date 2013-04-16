//{block name="backend/bepado/model/main/mapping"}
Ext.define('Shopware.apps.Bepado.model.main.Mapping', {
    extend: 'Shopware.apps.Base.model.Category',

    fields: [
        //{block name="backend/bepado/model/main/mapping/fields"}{/block}
        { name: 'mapping',  type: 'string', useNull: true }
    ]
});
//{/block}