//{namespace name=backend/connect/view/main}
//{block name="backend/product_stream/controller/main" append}
Ext.define('Shopware.apps.ProductStream.controller.ConnectMain', {
    override: 'Shopware.apps.ProductStream.controller.Main',

    messages: {
        title: '{s name=product_stream/stream_saved_title}Product stream{/s}',
        streamSaved: '{s name=product_stream/stream_saved_description}Stream saved{/s}',
        exportStreamTitle: '{s name=export/message/export_stream_title}Product streams export{/s}',
        exportStreamMessage: '{s name=export/message/export_stream_message}Product streams were marked for export.{/s}'
    },

    init: function () {
        var me = this;

        me.callOverridden(arguments);

        me.control({
            'product-stream-progress-window': {
                'startStreamExport': me.onStartStreamExport
            }
        });
    },

    saveSelectionStreamRecord: function (record) {
        var me = this;

        record.save({
            callback: function (records, operation, success) {
                var productGrid = me.getProductStreamGrid(),
                    listStore = productGrid.store,
                    detailGrid = me.getProductStreamDetailGrid();

                detailGrid.streamId = record.get('id');
                me.saveAttributes(record);

                listStore.reload({
                    callback: function () {
                        productGrid.reconfigure(listStore);
                    }
                });

                var response = Ext.JSON.decode(operation.response.responseText);

                if (response.data.isExported) {
                    me.exportStream(response.data.id);
                }

                me.createGrowlMessage(
                    me.messages.title,
                    me.messages.streamSaved
                );
            }
        });
    },

    onStartStreamExport: function (streamIds, articleDetailIds, batchSize, window, currentStreamIndex, offset) {
        var me = this,
            limit = batchSize;
        offset = parseInt(offset) || 0;

        Ext.Ajax.request({
            url: '{url controller=connect action=exportStream}',
            method: 'POST',
            params: {
                'streamIds[]': streamIds,
                'currentStreamIndex': currentStreamIndex,
                'articleDetailIds[]': articleDetailIds,
                'offset': offset,
                'limit': limit
            },
            success: function (response, opts) {
                var sticky = false;
                if (!response.responseText) {
                    return
                }

                var operation = Ext.decode(response.responseText);
                if (!operation) {
                    return;
                }

                if (!operation.success && operation.messages) {
                    if (operation.messages.price && operation.messages.price.length > 0) {
                        var priceMsg = Ext.String.format(
                            me.messages.priceErrorMessage, operation.messages.price.length, articleDetailIds.length
                        );
                        me.createGrowlMessage(me.messages.exportStreamTitle, priceMsg, true);
                    }

                    if (operation.messages.default && operation.messages.default.length > 0) {
                        operation.messages.default.forEach(function (message) {
                            me.createGrowlMessage(me.messages.exportStreamTitle, message, true);
                        });
                    }

                    window.inProcess = false;
                    window.cancelButton.setDisabled(false);
                    return;
                }

                window.progressField.updateText(Ext.String.format(window.snippets.process, operation.newOffset, articleDetailIds.length));
                window.progressField.updateProgress(
                    operation.newOffset / articleDetailIds.length,
                    Ext.String.format(window.snippets.process, operation.newOffset, articleDetailIds.length),
                    false
                );

                if (operation.hasMoreIterations) {
                    articleDetailIds = operation.articleDetailIds;
                    currentStreamIndex = operation.nextStreamIndex;
                    offset = operation.newOffset;
                    me.onStartStreamExport(streamIds, articleDetailIds, batchSize, window, currentStreamIndex, offset);
                } else {
                    window.inProcess = false;
                    window.cancelButton.setDisabled(false);

                    me.createGrowlMessage(me.messages.exportStreamTitle, me.messages.exportStreamMessage, sticky);
                }
            }
        });
    },

    /**
     * Helper to show a growl message
     *
     * @param title
     * @param message
     */
    createGrowlMessage: function (title, message, sticky) {
        var me = this;

        if (!sticky) {
            Shopware.Notification.createGrowlMessage(title, message, me.mainWindow.title);
        } else {
            Shopware.Notification.createStickyGrowlMessage({
                title: title,
                text: message,
                width: 400
            });
        }
    },

    exportStream: function (streamId) {
        var me = this;

        Ext.Ajax.request({
            url: '{url controller=connect action=getStreamProductsCount}',
            method: 'POST',
            params: {
                'id': streamId
            },
            success: function (response) {
                if (!response.responseText) {
                    return;
                }

                var operation = Ext.decode(response.responseText);
                if (!operation.success) {
                    me.createGrowlMessage(
                        me.messages.title,
                        operation.message,
                        true
                    );
                    return;
                }

                Ext.create('Shopware.apps.ProductStream.view.Progress', {
                    streamIds: [streamId],
                    articleDetailIds: operation.sourceIds
                }).show();
            }
        });
    }
});
//{/block}