//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/import/remote_categories"}
Ext.define('Shopware.apps.Connect.view.import.RemoteCategories', {
    extend: 'Ext.tree.Panel',
    alias: 'widget.connect-remote-categories',

    border: true,
    rootVisible: false,
    width: 400,
    height: 300,
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

        me.callParent(arguments);
    }
});
//{/block}