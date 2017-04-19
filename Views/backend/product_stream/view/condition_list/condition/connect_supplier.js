//{namespace name=backend/connect/view/main}
//{block name="backend/product_stream/view/condition_list/condition/connect_supplier"}
Ext.define('Shopware.apps.ProductStream.view.condition_list.condition.ConnectSupplier', {
    extend: 'Shopware.apps.ProductStream.view.condition_list.condition.AbstractCondition',

    getName: function() {
        return 'ShopwarePlugins\\Connect\\Bundle\\SearchBundle\\Condition\\SupplierCondition';
    },

    getLabel: function() {
        return '{s name=product_stream/stream_condition_supplier_title}Connect supplier condition{/s}';
    },

    isSingleton: function() {
        return true;
    },

    create: function(callback) {
        var field = this.createSelection();
        callback(field);
    },

    load: function(key, value) {
        if (key !== this.getName()) {
            return null;
        }
        var field = this.createSelection();
        field.setValue(value);
        return field;
    },

    createStore: function() {
        return Ext.create('Shopware.apps.ProductStream.store.ConnectSupplierList');
    },

    createSelection: function() {
        return Ext.create('Shopware.apps.ProductStream.view.condition_list.field.Grid', {
            name: 'condition.' + this.getName(),
            searchStore: this.createStore(),
            idsName: 'supplierIds',
            store: this.createStore(),
            getErrorMessage: function() {
                return '{s name=product_stream/stream_condition_supplier_not_selected}No connect supplier selected{/s}';
            }
        });

    }
});
//{/block}