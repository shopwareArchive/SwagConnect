//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/import/own_categories"}
Ext.define('Shopware.apps.Connect.view.import.OwnCategories', {
    extend: 'Ext.tree.Panel',
    alias: 'widget.connect-own-categories',

    border: true,
    rootVisible: false,
    width: 400,
    height: 300,
    root: {
        id: 1,
        expanded: true
    },
    store: 'base.CategoryTree',
    viewConfig: {
        plugins: {
            ptype: 'treeviewdragdrop',
            appendOnly: true,
            dropGroup: 'local'
        }
    },

    initComponent: function() {
        var me = this;

        //Ext.applyIf(me, {
        //
        //});

        me.callParent(arguments);
    }
});
//{/block}