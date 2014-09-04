//{namespace name="backend/bepado/view/main"}
//{block name="backend/article/controller/bepado"}
Ext.define('Shopware.apps.Article.controller.Bepado', {
    /**
     * The parent class that this class extends.
     * @string
     */
    extend:'Ext.app.Controller',

    refs: [
          { ref: 'bepadoForm', selector: 'article-bepado-form' },
          { ref: 'articleDetailWindow', selector: 'article-detail-window' },
      ],


    /**
     * A template method that is called when your application boots.
     * It is called before the Application's launch function is executed
     * so gives a hook point to run any code before your Viewport is created.
     *
     * @return Ext.window.Window
     */
    init: function () {
        var me = this,
            mainWindow = me.subApplication.articleWindow;

        me.control({
            'article-detail-window': {
                bepadoTabActivated: me.onBepadoStoreReloadNeeded,
                bepadoStoreReloadNeeded: me.onBepadoStoreReloadNeeded,
                saveArticle: me.onSaveArticle
            }
        });

        mainWindow.on('storesLoaded', me.onStoresLoaded, me);

        me.callParent(arguments);
    },

    enableTab: function() {
        var me = this,
            detailWindow = me.getArticleDetailWindow();

        detailWindow.bepadoTab.setDisabled(false);
    },

    onStoresLoaded: function() {
        var me = this;
        

    },

    onSaveArticle: function(win, article, options) {
        var me = this,
            bepadoForm = me.getBepadoForm();

        if (!me.subApplication.article || !me.subApplication.article.get('id')) {
            return;
        }

        bepadoForm.getForm().updateRecord(me.record);


        me.record.save({
                failure: function(record, operation) {
                    var rawData = record.getProxy().getReader().rawData,
                        message = rawData.message;

                    Shopware.Notification.createGrowlMessage('{s name=error}error{/s}', message, 'bepado');
                }
        });
    },

    /**
     * Callback function for the "bepadoStoreReloadNeeded" event
     */
    onBepadoStoreReloadNeeded: function() {
        var me = this;

        me.doReloadBepadoStore();
    },

    /**
     * Actually do reload the bepado store
     */
    doReloadBepadoStore: function() {
        var me = this,
            bepadoForm = me.getBepadoForm();

        if (!me.subApplication.article || !me.subApplication.article.get('id')) {
            return;
        }

        me.bepadoStore.load({
            callback: function(records, operation) {
                if (!operation.wasSuccessful()) {
                    return;
                }
                if (records.length > 0) {
                    me.record = records[0];
                    bepadoForm.loadRecord(me.record);

                    me.manageFields();
                }
            }
        });


    },

    /**
     * Manage some fields which needs to be disabled for remote products
     */
    manageFields: function() {
        var me = this,
            bepadoForm = me.getBepadoForm(),
            settingsFieldSet = me.subApplication.articleWindow.down('article-settings-field-set'),
            pricesFieldSet = me.subApplication.articleWindow.down('article-prices-field-set'),
            basePriceColumn = me.subApplication.articleWindow.down('article-prices-field-set numbercolumn[dataIndex=basePrice]'),
            shippingField, inStockField,
            isPriceLocked;


        // Unit fields
        var contentLabel = me.getArticleDetailWindow().snippets.basePrice.content;
        var contentField = me.getArticleDetailWindow().down('numberfield[fieldLabel=' + contentLabel + ']');
        var basicUnitLabel = me.getArticleDetailWindow().snippets.basePrice.basicUnit;
        var basicField = me.getArticleDetailWindow().down('numberfield[fieldLabel=' + basicUnitLabel + ']');
        me.getArticleDetailWindow().unitComboBox.setReadOnly(me.record.get('shopId') > 0);
        contentField.setReadOnly(me.record.get('shopId') > 0);
        basicField.setReadOnly(me.record.get('shopId') > 0);

        bepadoForm.bepadoFixedPrice.setReadOnly(me.record.get('shopId') > 0);
        bepadoForm.bepadoShippingGroup.setReadOnly(me.record.get('shopId') > 0);

        if (settingsFieldSet) {
            shippingField = settingsFieldSet.down('checkboxfield[fieldLabel=' + settingsFieldSet.snippets.shippingFree.field + ']');
            inStockField = settingsFieldSet.down('numberfield[fieldLabel=' + settingsFieldSet.snippets.stock + ']');

            if (shippingField) {
                shippingField.setReadOnly(me.record.get('shopId') > 0);
            }
            if (inStockField) {
                inStockField.setReadOnly(me.record.get('shopId') > 0);
            }
        }

        if (pricesFieldSet) {
            isPriceLocked = me.record.get('fixedPrice') && me.record.get('shopId') > 0;
            pricesFieldSet.tabPanel.setDisabled(isPriceLocked);
            pricesFieldSet.bepadoLabel.setVisible(isPriceLocked);
        }


        if (basePriceColumn) {
            if (me.record.get('shopId') > 0) {
                basePriceColumn.setEditor(false);
            }
        }
    }


});
//{/block}
