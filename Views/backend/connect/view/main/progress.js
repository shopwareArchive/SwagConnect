//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/main/progress"}
Ext.define('Shopware.apps.Connect.view.main.Progress', {

    extend:'Enlight.app.Window',
    cls: Ext.baseCSSPrefix + 'connect',

    alias: 'widget.connect-main-migration-progress-window',
    border: false,
    autoShow: true,
    layout: 'anchor',
    width: 420,
    height: 80,
    maximizable: false,
    minimizable: false,
    closable: false,
    footerButton: true,
    stateful: true,
    modal: true,
    inProcess: false,

    /**
     * Contains the batch size for each request of the generation.
     */
    batchSize: 200,

    /**
     * Contains array with source ids which have to exported
     */
    sourceIds: [],

    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        title: 'Migration',
        process: '{s name=connect/import/dataMigrations/progress/process}[0] of [1] imported product(s) migrated...{/s}',
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

        me.progressField = Ext.create('Ext.ProgressBar', {
            name: 'productExportBar',
            text: Ext.String.format(me.snippets.process, 0, me.sourceIds.length),
            margin: '0 0 15',
            border: 1,
            style: 'border-width: 1px !important;',
            cls: 'left-align',
            value: 0
        });

        return [ me.progressField ];
    },

    closeWindow: function() {
        var me = this;

        if (me.progressField.getActiveAnimation()) {
            Ext.defer(me.closeWindow, 200, me);
            return;
        }

        // Wait a little before destroy the window for a better use feeling
        Ext.defer(me.destroy, 500, me);
    }
});
//{/block}
