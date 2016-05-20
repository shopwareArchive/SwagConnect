Ext.define('Shopware.apps.Connect.view.import.tree.RemoteCategoryDragAndDrop', {
    extend: 'Ext.tree.plugin.TreeViewDragDrop',
    
    alias: 'plugin.remote-category-drag-and-drop',
    
    onViewRender: function() {
        var me = this;

        me.localTreeView = Ext.ComponentQuery.query('panel[name=localCategoryTree]')[0].getView();

        me.callParent(arguments);

        me.dragZone.onStartDrag = function (x, y) {
            me.modifyTree('color: #bbbbbb !important');
        };

        me.dragZone.afterInvalidDrop = function (e, id) {
            me.modifyTree('');
        };
    },

    modifyTree: function(stl) {
        var me = this,
            i, targetRecord,
            dragData = me.dragZone.dragData.records[0],
            nodes = me.localTreeView.getNodes();

        for (i = 0; i < nodes.length; i++){
            targetRecord = me.localTreeView.getRecord(nodes[i]);

            if (!me.isValidDropPoint(targetRecord, dragData)) {
                nodes[i].style = stl;
            }
        }
    },

    isLeaf: function(record) {
        return record.data.leaf;
    },

    getDepth: function(record) {
        return record.data.depth;
    },

    isValidDropPoint: function (targetRecord, draggedRecord) {
        var me = this;

        //its minus two, cause we have contact and stream node
        var draggedDepth = me.getDepth(draggedRecord) - 2;

        //its plus one, cause we want the parent node depth
        var parentDepth = me.getDepth(targetRecord) + 1;

        return !me.isLeaf(targetRecord) && draggedDepth == parentDepth;
    }
});