//{namespace name=backend/connect/view/import}

//{block name="backend/connect/view/import/progress"}
Ext.define('Shopware.apps.Connect.view.import.Progress', {

    extend:'Enlight.app.Window',

    alias: 'widget.connect-import-category-assignment-progress-window',
    border: false,
    autoShow: true,
    layout: 'anchor',
    width: 420,
    height: 120,
    maximizable: false,
    minimizable: false,
    closable: false,
    footerButton: true,
    stateful: true,
    modal: true,
    inProcess: false,

    /**
     * Number of categories that should get assigned
     */
    categoriesCount: 0,

    /**
     * Number of articles that should get assigned in current category
     */
    articlesCount: 0,


    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        title: '{s name=import/assignment/title}Category Assignment{/s}',
        processCategories: '{s name=import/assignment/categoryProcess}[0] of [1] categories assigned...{/s}',
        processArticles: '{s name=import/assignment/articleProcess}[0] of [1] articles assigned...{/s}'
    },

    bodyPadding: 10,

    initComponent:function () {
        var me = this;

        me.items = me.createItems();
        me.title = me.snippets.title;
        me.callParent(arguments);
    },

    /**
     * Creates the items for the progress window.
     */
    createItems: function() {
        var me = this;

        me.progressFieldCategories = Ext.create('Ext.ProgressBar', {
            animate: true,
            name: 'categoriesProcessedBar',
            text: Ext.String.format(me.snippets.processCategories, 0, me.categoriesCount),
            margin: '0 0 15',
            border: 1,
            style: 'border-width: 1px !important;',
            cls: 'left-align',
            value: 0
        });

        me.progressFieldArticles = Ext.create('Ext.ProgressBar', {
            animate: true,
            name: 'categoriesProcessedBar',
            text: Ext.String.format(me.snippets.processArticles, 0, me.articlesCount),
            margin: '0 0 15',
            border: 1,
            style: 'border-width: 1px !important;',
            cls: 'left-align',
            value: 0
        });

        return [ me.progressFieldCategories, me.progressFieldArticles ];
    },

    closeWindow: function() {
        var me = this;

        if (me.progressFieldArticles.getActiveAnimation()) {
            Ext.defer(me.closeWindow, 200, me);
            return;
        }

        // Wait a little before destroy the window for a better use feeling
        Ext.defer(me.destroy, 500, me);
    }
});
//{/block}
