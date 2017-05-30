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
        copy: true,
        plugins: {
            ptype: 'remote-category-drag-and-drop',
            dragGroup: 'local-category',
            dropGroup: 'remote-category'
        }
    },
    bodyCls: 'shopware-connect-color',

    snippets: {
        reload: '{s name=import/tree/reload}Neuladen{/s}',
        importSelectedCategories: '{s name=import/tree/import_selected_categories}Import categories{/s}',
        recreateRemoteCategories: '{s name=import/tree/update_remote_categories}Re-create categories{/s}',
        hideMappedCategories: '{s name=import/tree/hide_mapped}Hide assigned{/s}'
    },

    initComponent: function() {
        var me = this;

        me.on({
            // Context menu on items
            itemcontextmenu: me.onOpenItemContextMenu,
            // Context menu on container
            containercontextmenu: me.onOpenContainerContextMenu,
            // scope
            scope: me
        });

        Ext.applyIf(me, {
            dockedItems: [
                me.getToolbar()
            ]
        });

        me.callParent(arguments);
    },

    /**
     * Event listener method which fires when the user performs a right click
     * on the Ext.tree.Panel.
     *
     * Opens a context menu which features functions to reload the category list.
     *
     * Fires the following events on the Ext.tree.Panel:
     * - reload
     *
     * @event containercontextmenu
     * @param [object] view - HTML DOM Object of the Ext.tree.Panel
     * @param [object] event - The fired Ext.EventObject
     * @return void
     */
    onOpenContainerContextMenu : function(view, event) {
        event.preventDefault(true);
        var me = this,
            menuElements = [];

        menuElements.push({
            text: me.snippets.reload,
            iconCls:'sprite-arrow-circle-315',
            handler:function () {
                me.fireEvent('reloadRemoteCategories', me, view);
            }
        });

        var menu = Ext.create('Ext.menu.Menu', {
            items:menuElements
        });
        menu.showAt(event.getPageX(), event.getPageY());
    },

    /**
     * Event listener method which fires when the user performs a right click
     * on a node in the Ext.tree.Panel.
     *
     * Opens a context menu which features functions to reload the tree.
     *
     * Fires the following events on the Ext.tree.Panel:
     * - reload
     *
     * @event itemcontextmenu
     * @param [object] view - HTML DOM Object of the Ext.tree.Panel
     * @param [object] record - Associated Ext.data.Model for the clicked node
     * @param [object] item HTML DOM Object of the clicked node
     * @param [integer] index - Index of the clicked node in the associated Ext.data.TreeStore
     * @param [object] event - The fired Ext.EventObject
     * @return void
     */
    onOpenItemContextMenu : function(view, record, item, index, event) {
        event.preventDefault(true);
        var me = this,
            menuElements = [];

        menuElements.push({
            text: me.snippets.reload,
            iconCls:'sprite-arrow-circle-315',
            handler:function () {
                me.fireEvent('reloadRemoteCategories', me, view);
            }
        });

        var menu = Ext.create('Ext.menu.Menu', {
            items: menuElements
        });
        menu.showAt(event.getPageX(), event.getPageY());
    },

    getToolbar: function () {
        var me = this;

        return Ext.create('Ext.toolbar.Toolbar', {
            cls: 'tree-table-toolbar',
            dock: 'top',
            ui: 'shopware-ui',
            items: [{
                xtype: 'button',
                iconCls: 'sprite-plus-circle-frame',
                action: 'importRemoteCategory',
                margin: '0 5px 0 0',
                text: me.snippets.importSelectedCategories
            }, {
                xtype: 'button',
                iconCls: 'sprite-arrow-circle-135',
                action: 'recreateRemoteCategories',
                margin: '0 10px 0 0',
                text: me.snippets.recreateRemoteCategories
            }, {
                xtype : 'checkbox',
                name : 'attribute[hideMapped]',
                action: 'hide-mapped-categories',
                checked: false,
                boxLabel : me.snippets.hideMappedCategories
            }, '->',
                me.getSearchFilter()
            ]
        });
    },

    getSearchFilter: function() {
        return {
            xtype:'textfield',
            anchor: '100%',
            cls:'searchfield',
            emptyText:'{s name=import/filter/search_empty}Search...{/s}',
            enableKeyEvents:true,
            checkChangeBuffer:500,
            action: 'search-remote-categories'
        }
    }
});
//{/block}