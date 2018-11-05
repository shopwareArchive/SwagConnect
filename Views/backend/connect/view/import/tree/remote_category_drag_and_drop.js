//{namespace name="backend/connect/view/main"}
//{block name="backend/connect/view/import/tree/remote_category_drag_and_drop"}
Ext.define('Shopware.apps.Connect.view.import.tree.RemoteCategoryDragAndDrop', {
    extend: 'Ext.tree.plugin.TreeViewDragDrop',
    
    alias: 'plugin.remote-category-drag-and-drop',
    
    onViewRender: function() {
        var me = this;

        me.localTreeView = Ext.ComponentQuery.query('panel[name=localCategoryTree]')[0].getView();

        me.callParent(arguments);

        me.dragZone.onStartDrag = function (x, y) {
            //remove this code after CON-3515 is done
            //me.modifyTree('color: #bbbbbb !important');
        };

        me.dragZone.afterInvalidDrop = function (e, id) {
            //remove this code after CON-3515 is done
            //me.modifyTree('');
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

        //its minus three, cause we have contact, stream node and language node (deutsch, english)
        var draggedDepth = me.getDepth(draggedRecord) - 3;
        var droppedDepth = me.getDepth(targetRecord);

        //dragged leaf can be drop everywhere except at the main language categories
        if(me.isLeaf(draggedRecord) && !me.isLeaf(targetRecord) && droppedDepth > 1){
            return true;
        }

        return !me.isLeaf(targetRecord) && draggedDepth == droppedDepth;
    }
});
//{/block}