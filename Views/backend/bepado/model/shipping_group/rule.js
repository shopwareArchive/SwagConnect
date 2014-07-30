//{block name="backend/bepado/model/shipping_group/rule"}
Ext.define('Shopware.apps.Bepado.model.shippingGroup.Rule', {
    extend: 'Ext.data.Model',

    fields: [
        //{block name="backend/bepado/model/shipping_group/rule/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'groupId', type: 'int' },
        { name: 'groupName',  type: 'string' },
        { name: 'country',  type: 'string' },
        { name: 'deliveryDays',  type: 'int' },
        { name: 'price',  type: 'float' },
        { name: 'zipPrefix',  type: 'string' }
    ]
});
//{/block}