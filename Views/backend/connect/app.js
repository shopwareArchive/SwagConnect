//{block name="backend/connect/application"}
Ext.define('Shopware.apps.Connect', {
    extend: 'Enlight.app.SubApplication',

    bulkLoad: true,
    loadPath: '{url action=load}',
    views: [
        'main.Window',
        'main.Panel',

        'export.Window',
        'export.product.Panel',
        'export.product.List',
        'export.product.Filter',
        'export.product.Progress',
        'export.stream.Panel',
        'export.stream.List',
        'export.stream.Progress',
        'export.price.Window',
        'export.price.Form',
        'export.price.Checkboxcolumn',

        'import.Panel',
        'import.RemoteCategories',
        'import.OwnCategories',
        'import.RemoteProducts',
        'import.LocalProducts',
        'import.TabPanel',
        'import.unit.Panel',
        'import.tree.RemoteCategoryDragAndDrop',

        'log.Panel',
        'log.List',
        'log.Filter',
        'log.Tabs',

        'changed_products.Panel',
        'changed_products.List',
        'changed_products.Tabs',
        'changed_products.Images',

        'config.Window',
        'config.TabPanel',
        'config.general.Panel',
        'config.general.Form',
        'config.export.Panel',
        'config.export.Form',
        'config.import.Panel',
        'config.import.Form',
        'config.import.UnitsMapping',
        'config.marketplaceAttributes.Panel',
        'config.marketplaceAttributes.Mapping'
    ],
    controllers: [ 'Main', 'Import' ],

    //views: [],

    /**
     * This method will be called when all dependencies are solved and
     * all member controllers, models, views and stores are initialized.
     */
    launch: function(param) {
        var me = this;
        me.controller = me.getController('Main');
        return me.controller.mainWindow;
    }
});
//{/block}