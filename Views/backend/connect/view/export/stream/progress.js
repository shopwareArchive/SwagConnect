//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/stream/progress"}
Ext.define('Shopware.apps.Connect.view.export.stream.Progress', {

    extend:'Enlight.app.Window',

    alias: 'widget.connect-stream-export-progress-window',
    border: false,
    autoShow: true,
    layout: 'anchor',
    width: 420,
    height: 220,
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
     * Number of sourceIds which will be exported
     */
    sourceIdsCount: 0,

    /**
     * Contains array with article detail ids which have to exported
     */
    streamIds: [],

    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        title: 'Export',
        process: '{s name=export/progress/process}[0] of [1] product(s) exported...{/s}',
        processStream: '{s name=export/progress/process_streams}[0] of [1] product stream(s) exported...{/s}',
        notice: '{s name=export/progress/notice_streams}The export process can take several minutes. Do you want to continue?{/s}'
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

        me.progressFieldStream = Ext.create('Ext.ProgressBar', {
            animate: true,
            name: 'streamExportBar',
            text: Ext.String.format(me.snippets.processStream, 0, me.streamIds.length),
            margin: '0 0 15',
            border: 1,
            style: 'border-width: 1px !important;',
            cls: 'left-align',
            value: 0
        });

        me.progressField = Ext.create('Ext.ProgressBar', {
            animate: true,
            name: 'productExportBar',
            text: Ext.String.format(me.snippets.process, 0, me.sourceIdsCount),
            margin: '0 0 15',
            border: 1,
            style: 'border-width: 1px !important;',
            cls: 'left-align',
            value: 0
        });

        me.cancelButton = Ext.create('Ext.button.Button', {
            text: 'Close',
            anchor: '50%',
            cls: 'secondary',
            margin: '0 10 0 0',
            handler: function() {
                me.startButton.setDisabled(false);
                if (!me.inProcess) {
                    me.destroy();
                }
            }
        });

        me.startButton = Ext.create('Ext.button.Button', {
            text: 'Start',
            anchor: '50%',
            cls: 'primary',
            handler: function() {
                me.inProcess = true;
                if (!Ext.isNumeric(me.batchSize)) {
                    me.batchSize = 5;
                }
                me.startButton.setDisabled(true);
                me.cancelButton.setDisabled(true);

                me.fireEvent('startStreamExport', me.streamIds, me.sourceIdsCount, me.batchSize, me);
            }
        });

        var totalTime = me.sourceIdsCount / me.batchSize * 1.5 / 60;
        totalTime = Ext.Number.toFixed(totalTime, 0);

        var notice = Ext.create('Ext.container.Container', {
            html: Ext.String.format(me.snippets.notice, totalTime),
            style: 'color: #999; font-style: italic; margin: 0 0 15px 0; text-align: center;',
            anchor: '100%'
        });

        return [ notice, me.progressFieldStream, me.progressField, me.cancelButton, me.startButton ];
    }
});
//{/block}
