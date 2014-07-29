//{block name="backend/bepado/model/shipping_group/group"}
Ext.define('Shopware.apps.Bepado.model.shippingGroup.Group', {
    extend: 'Ext.data.Model',

    fields: [
        //{block name="backend/bepado/model/shipping_group/group/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'groupName',  type: 'string' },
        { name: 'country',  type: 'string' },
        { name: 'deliveryDays',  type: 'int' },
        { name: 'price',  type: 'float' },
        { name: 'zipPrefix',  type: 'string' }
    ]
});
//{/block}