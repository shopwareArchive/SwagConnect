/**
 * Extends the supplier model and adds the customizingId field
 */
/*
 {block name="backend/supplier/model/supplier/fields" append}
 { name: 'isConnect', type: 'boolean' },
 {/block}
 */

/**
 * Extend the supplier list in order to show a connect icon
 */
//{block name="backend/supplier/view/main/list" append}
Ext.define('Shopware.apps.SupplierList.view.main.Grid-Customizing', {
    override: 'Shopware.apps.Supplier.view.main.List',

    /**
     *
     * @Override: Show a connect icon for connect suppliers
     *
     * @param value
     * @param metaData
     * @param record
     * @returns Object
     */
    nameColumn: function(value, metaData, record) {
        var me = this,
            result = me.callOverridden(arguments);

        if (record.get('isConnect')) {
            result = '<div class="connect-icon" style="padding: 2px 0 6px 20px">' + result + '&nbsp;</div>';
        }

        return result;
    }

});
//{/block}