//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/own_categories"}
Ext.define('Shopware.apps.Bepado.view.import.OwnCategories', {
    extend: 'Ext.tree.Panel',
    alias: 'widget.connect-own-categories',

    border: false,
    rootVisible: false,
    width: 200,
    height: 200,
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