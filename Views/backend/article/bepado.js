/**
 * Extends the article model and adds the bepado field
 */
/*
 {block name="backend/article/model/attribute/fields" append}
 { name: 'bepadoFixedPrice', type: 'boolean' },
 { name: 'bepadoShopId', type: 'int', useNull: true },
 {/block}
 */

//{namespace name=backend/bepado/view/main}

/**
 * Extend the article's base fieldSet and add the fixedPrice field
 */
//{block name="backend/article/view/detail/base" append}
Ext.define('Shopware.apps.Article.view.detail.Base-Bepado', {
    override: 'Shopware.apps.Article.view.detail.Base',

    /**
     * Mark the fixedPrice field as readonly if the product is a remote product
     *
     * @param article
     * @param stores
     * @returns Array
     */
    onStoresLoaded: function(article, stores) {
        var me = this,
            attributes;

        if (article && article.getAttribute()) {
            attributes = article.getAttribute().first();

            me.up('window').bepadoFixedPrice.setReadOnly(attributes.get('bepadoShopId') > 0);
        }

        return me.callOverridden(arguments);
    }
});
//{/block}

/**
 * Extend the article's price fieldset in order to disable it if the price was configured as fixedPrice in the source shop
 */
//{block name="backend/article/view/detail/prices" append}
Ext.define('Shopware.apps.Article.view.detail.Prices-Bepado', {
    override: 'Shopware.apps.Article.view.detail.Prices',

    createElements: function() {
        var me = this,
            attributes,
            style,
            label,
            tabPanel;


        tabPanel =  me.callOverridden(arguments);

        if (me.article && me.article.getAttribute()) {
            attributes = me.article.getAttribute().first();

            style = 'style="width: 25px; height: 25px; display: inline-block; margin-right: 3px;"';

            if(attributes.get('bepadoShopId') > 0 && attributes.get('bepadoFixedPrice')) {
                label = { xtype: 'label', html: '<div title="" class="bepado-icon" ' +  style + '>&nbsp;</div>{s name="bepadoFixedPriceMessage"}The supplier of this product has enabled the fixed price feature. For this reason you will not be able to edit the price.{/s}' };


                tabPanel.setDisabled(true);

                return [
                    label,
                    tabPanel
                ];
            }
        }

        return tabPanel;

    }
});
//{/block}

//{block name="backend/article/view/detail/window" append}
Ext.define('Shopware.apps.Article.view.detail.Window-Bepado', {
    override: 'Shopware.apps.Article.view.detail.Window',

    createBaseTab: function() {
        var me = this,
            result = me.callOverridden(arguments);

        me.detailForm.insert(2, me.getBepadoContainer());

        return result;
    },

    getBepadoContainer: function() {
        var me = this;

        return Ext.create('Ext.form.FieldSet', {
            layout: 'column',
            title: 'bepado',
            defaults: {
                labelWidth: 155,
                anchor: '100%'
            },
            items: me.getBepadoContent()
        });
    },

    getBepadoContent: function() {
        var me = this;

        me.bepadoLeftContainer = Ext.create('Ext.container.Container', {
            columnWidth:0.5,
            defaults: {
                labelWidth: 155,
                anchor: '100%'
            },
            padding: '0 20 0 0',
            layout: 'anchor',
            border:false,
            items:me.getLeftContainer()
        });

        me.bepadoRightContainer = Ext.create('Ext.container.Container', {
            columnWidth:0.5,
            layout: 'anchor',
            defaults: {
                labelWidth: 155,
                anchor: '100%'
            },
            border:false,
            items:me.getRightContainer()
        });

        return [ me.bepadoLeftContainer, me.bepadoRightContainer ];
    },

    getLeftContainer: function() {
        var me = this;

        return [
            me.getFixedPriceCombo(),
            me.getOverwriteCombo('Aktualisierung Preise', 'bepadoUpdatePrice'),
            me.getOverwriteCombo('Aktualisierung Bilder', 'bepadoUpdateImage')
        ];
    },

    getRightContainer: function() {
        var me = this;

        return [
            me.getOverwriteCombo('Aktualisierung Langbeschreibung', 'bepadoUpdateLongDescription'),
            me.getOverwriteCombo('Aktualisierung Kurzbeschreibung', 'bepadoUpdateShortDescription'),
            me.getOverwriteCombo('Aktualisierung Name', 'bepadoUpdateName')
        ];
    },

    getOverwriteCombo: function(text, dataField) {
        var me = this;

        return {
            xtype: 'combobox',
            store: me.getOverwriteStore(),
            displayField: 'description',
            valueField: 'value',
            fieldLabel: text,
            name: dataField,
            editable: false
        };
    },

    getOverwriteStore: function() {
        var me = this;

        return Ext.create('Ext.data.Store', {
            fields: [ { name: 'value', useNull: true }, 'description'],
            data: [
                { value: null, description: 'Erben' },
                { value: 'overwrite', description: 'Händisch pflegen' },
                { value: 'no-overwrite', description: 'Automatisch' }
            ]
        });

    },

    getFixedPriceCombo: function() {
        var me = this;

        return me.bepadoFixedPrice = Ext.create('Ext.form.field.Checkbox', {
            labelWidth: 155,
            name: 'attribute[bepadoFixedPrice]',
            fieldLabel: '{s name=bepadoFixedPrice}Enable price fixing{/s}',
            inputValue: true,
            uncheckedValue:false
        })
    }

});
//{/block}
