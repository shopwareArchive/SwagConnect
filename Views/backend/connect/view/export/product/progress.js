//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/product/progress"}
Ext.define('Shopware.apps.Connect.view.export.product.Progress', {

    extend:'Enlight.app.SubWindow',

    alias: 'widget.connect-article-export-progress-window',
    border: false,
    autoShow: true,
    layout: 'anchor',
    width: 420,
    height: 190,
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
    batchSize: 50,

    /**
     * Contains array with source ids which have to exported
     */
    sourceIds: [],

    /**
     * This function gets called when the start button is pressed
     */
    startButtonHandler: 0,

    /**
     * Contains the amount of products export all will export as batches
     */
    count: 0,

    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        title: 'Export',
        process: '{s name=export/progress/process}[0] of [1] product(s) exported...{/s}',
        notice: '{s name=export/progress/notice}This process will take about [0] minutes depending on your system resources. <br>Do you want to continue?{/s}'
    },

    bodyPadding: 10,

    initComponent:function () {
        var me = this;

        if(!Ext.isFunction(me.startButtonHandler)) {
            throw new Error('startButtonHandler has to be a function');
        }

        me.items = me.createItems();
        me.title = me.snippets.title;
        me.callParent(arguments);
    },

    /**
     * Creates the items for the progress window.
     */
    createItems: function() {
        var me = this,
            totalItems = me.sourceIds.length || me.count;

        me.progressField = Ext.create('Ext.ProgressBar', {
            name: 'productExportBar',
            text: Ext.String.format(me.snippets.process, 0, totalItems),
            margin: '0 0 15',
            border: 1,
            style: 'border-width: 1px !important;',
            cls: 'left-align',
            value: 0
        });

        me.cancelButton = Ext.create('Ext.button.Button', {
            text: 'Cancel',
            anchor: '50%',
            cls: 'secondary',
            margin: '0 10 0 0',
            handler: function() {
                me.startButton.setDisabled(false);
                if (!me.inProcess) {
                    me.closeWindow();
                }
            }
        });

        me.startButton = Ext.create('Ext.button.Button', {
            text: 'Start',
            anchor: '50%',
            cls: 'primary',
            handler: function() {
                me.inProcess = true;
                me.startButton.setDisabled(true);
                me.cancelButton.setDisabled(true);

                me.startButtonHandler(me);
            }
        });

        var totalTime = totalItems / me.batchSize * 4 / 60;
        totalTime = Ext.Number.toFixed(totalTime, 0);

        var notice = Ext.create('Ext.container.Container', {
            html: Ext.String.format(me.snippets.notice, totalTime),
            style: 'color: #999; font-style: italic; margin: 0 0 15px 0; text-align: center;',
            anchor: '100%'
        });

        return [ notice, me.progressField, me.cancelButton, me.startButton ];
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
