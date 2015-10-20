//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/import/remote_categories"}
Ext.define('Shopware.apps.Bepado.view.import.RemoteCategories', {
    extend: 'Ext.tree.Panel',
    alias: 'widget.connect-remote-categories',

    border: false,
    rootVisible: false,
    width: 200,
    height: 200,
    root: {
        id: 1,
        expanded: true
    },
    store: 'import.RemoteCategories',
    viewConfig: {
        plugins: {
            ptype: 'treeviewdragdrop',
            appendOnly: true,
            dragGroup: 'local',
            dropGroup: 'remote'
        }
    },

    initComponent: function() {
        var me = this;

        //Ext.applyIf(me, {
        //});

        me.callParent(arguments);
    }
});
//{/block}