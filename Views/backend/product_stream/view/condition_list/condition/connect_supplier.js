//{block name="backend/product_stream/view/condition_list/condition/connect_supplier"}
Ext.define('Shopware.apps.ProductStream.view.condition_list.condition.ConnectSupplier', {
    extend: 'Shopware.apps.ProductStream.view.condition_list.condition.AbstractCondition',

    initComponent: function() {
        console.log('xxxx');
    },

    getName: function() {
        return 'Connect\\SupplierCondition';
    },

    getLabel: function() {
        return 'Bla condition';
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
            idsName: 'connectSupplierIds',
            store: this.createStore(),
            getErrorMessage: function() {
                return 'No connect supplier selected';
            }
        });

    }
});
//{/block}