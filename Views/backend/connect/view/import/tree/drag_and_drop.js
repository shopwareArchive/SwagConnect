Ext.define('Shopware.apps.Connect.view.import.tree.DragAndDrop', {
    extend: 'Ext.tree.plugin.TreeViewDragDrop',
    
    alias: 'plugin.customtreeviewdragdrop',
    
    onViewRender: function() {
        var me = this;

        me.callParent(arguments);

        me.dropZone.isValidDropPoint = function(node, position, dragZone, e, data) {

            var view = me.dropZone.view,
                targetRecord = view.getRecord(node),
                draggedRecord = data.records[0];

            //its minus two, cause we have contact and stream node
            var draggedDepth = me.getDepth(draggedRecord) - 2;

            //its plus one, cause we want the parent node depth
            var parentDepth = me.getDepth(targetRecord) + 1;

            return !me.isLeaf(targetRecord) && draggedDepth == parentDepth;
        };
    },

    isLeaf: function(record) {
        return record.data.leaf;
    },

    getDepth: function(record) {
        return record.data.depth;
    }
});