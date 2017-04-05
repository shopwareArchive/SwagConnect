//{block name="backend/product_stream/view/selected_list/window" append}
Ext.define('Shopware.apps.ProductStream.view.selected_list.ConnectWindow', {
    override: 'Shopware.apps.ProductStream.view.selected_list.Window',

    createProductGrid: function() {
        var me = this,
            productGrid = this.callParent(arguments);

        if (me.record.get('isConnect')) {
            productGrid.searchField.disable();
            productGrid.grid.columns.pop();
        }

        return productGrid
    },

    createSettingPanel: function() {
        var me = this,
            settingsPanel = this.callParent(arguments);

        if (me.record.get('isConnect')) {
            settingsPanel.nameField.disable();
            settingsPanel.descriptionField.disable();
            settingsPanel.sortingCombo.disable();
        }
        return settingsPanel;
    }
});
//{/block}
