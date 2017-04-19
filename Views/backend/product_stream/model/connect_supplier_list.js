//{block name="backend/base/model/supplier"}`
Ext.define('Shopware.apps.ProductStream.model.ConnectSupplierList', {
    /**
     * Extends the standard Ext Model
     * @string
     */
    extend: 'Shopware.data.Model',

    /**
     * unique id
     * @int
     */
    idProperty : 'id',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        //{block name="backend/product_stream/model/connect_supplier_list/fields"}{/block}
        { name : 'id', type : 'int' },
        { name : 'name', type : 'string'},
        { name : 'logoUrl', type : 'string' }
    ]
});
//{/block}