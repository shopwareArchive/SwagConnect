//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/product/progress"}
Ext.define('Shopware.apps.Connect.view.export.product.Progress', {

    extend:'Enlight.app.Window',

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
     * Contains array with article detail ids which have to exported
     */
    articleDetailIds: [],

    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        title: 'Export',
        process: '{s name=export/article/progress/message}[0] of [1] products exported...{/s}',
        notice: '{s name=progress/notice}This process will take about [0] minutes depending on your system resources. <br>Do you want to continue?{/s}'
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
            animate: true,
            name: 'productExportBar',
            text: Ext.String.format(me.snippets.process, 0, me.articleDetailIds.length),
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
                    me.batchSize = 30;
                }
                me.startButton.setDisabled(true);
                me.cancelButton.setDisabled(true);

                me.fireEvent('startExport', me.articleDetailIds, me.batchSize, me);
            }
        });

        var totalTime = me.articleDetailIds.length / me.batchSize * 1.5 / 60;
        totalTime = Ext.Number.toFixed(totalTime, 0);

        var notice = Ext.create('Ext.container.Container', {
            html: Ext.String.format(me.snippets.notice, totalTime),
            style: 'color: #999; font-style: italic; margin: 0 0 15px 0; text-align: center;',
            anchor: '100%'
        });

        return [ notice, me.progressField, me.cancelButton, me.startButton ];
    }
});
//{/block}
