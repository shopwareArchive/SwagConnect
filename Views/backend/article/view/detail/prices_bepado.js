/**
 * Extend the article's price fieldset in order to disable it if the price was configured as fixedPrice in the source shop
 */
//{block name="backend/article/view/detail/prices" append}
Ext.define('Shopware.apps.Article.view.detail.PricesBepado', {
    override: 'Shopware.apps.Article.view.detail.Prices',

    createElements: function() {
        var me = this,
            attributes,
            style,
            label,
            tabPanel;


        tabPanel =  me.callOverridden(arguments);

        me.registerCellEditListener();

        style = 'style="width: 25px; height: 25px; display: inline-block; margin-right: 3px;"';

        me.bepadoLabel = Ext.create('Ext.form.Label', {
            hidden: true,
            html: '<div title="" class="bepado-icon" ' + style + '>&nbsp;</div>{s name="bepadoFixedPriceMessage"}The supplier of this product has enabled the fixed price feature. For this reason you will not be able to edit the price.{/s}'
        });

        return [
            me.bepadoLabel,
            tabPanel
        ];

    },

    /**
     * In order to update the record properly, register on cell edit events and set the price record
     */
    registerCellEditListener: function() {
        var me = this;

        Ext.each(me.priceGrids, function(grid) {
            grid.on('edit', function(editor, e) {
                var value = e.value,
                    record = e.record,
                    attributeStore, attribute;

                // Return if another field was edited or we don't have a record
                if (e.field != 'attribute[bepadoPrice]' || !record) {
                    return;
                }

                // Make sure that we have a proper attributesStore
                if (record.hasOwnProperty('getAttributesStore')) {
                    attributeStore = record.getAttributes()
                }

                // Update the record
                if (attributeStore) {
                    attribute = attributeStore.first();
                    attribute.set('bepadoPrice', value);
                    attributeStore.removeAll();
                    attributeStore.add(attribute);
                }
            });
        });
    },

    /**
     * @Override preparePriceStore to make sure, that new products have a price attribute as well
     */
    preparePriceStore: function() {
        var me = this,
            record;

        me.callParent(arguments);
        // Get the first price
        record = me.priceStore.first();

        if (!record) {
            return;
        }

        // Make sure that we have a proper attributesStore
        if (!record.hasOwnProperty('getAttributesStore')) {
            record.getAttributesStore = Ext.create('Ext.data.Store', {
                model: 'Shopware.apps.Article.model.PriceAttribute'
            });
            record.getAttributesStore.add(Ext.create('Shopware.apps.Article.model.PriceAttribute', {

            }));

        }
    },

    /**
     * @Override getColumn in order to add the bepdo price column
     *
     * @returns Array
     */
    getColumns: function() {
        var me = this,
            columns = me.callParent(arguments);

        columns.splice(-1, 0, {
            xtype: 'numbercolumn',
            header: "{s name=detail/price/bepadoPrice}bepado price{/s}",
            renderer: function (value, arg, record) {
                if (value) {
                    return value;
                }

                var attributeStore,
                    attribute;

                if (record.hasOwnProperty('getAttributesStore')) {
                    attributeStore = record.getAttributes()
                }

                if (attributeStore) {
                    attribute = attributeStore.first();
                    return attribute.get('bepadoPrice');
                }
            },
            dataIndex: 'attribute[bepadoPrice]',
            editor: {
                xtype: 'numberfield',
                decimalPrecision: 2,
                minValue: 0
            }
        });
        return columns;

    }

});
//{/block}

