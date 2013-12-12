/**
 * Extends the article model and adds the bepado field
 */
/*
 {block name="backend/article/model/attribute/fields" append}
 { name: 'bepadoFixedPrice', type: 'boolean' },
 { name: 'bepadoShopId', type: 'int', useNull: true },
 { name: 'bepadoUpdatePrice', type: 'string', useNull: true  },
 { name: 'bepadoUpdateImage', type: 'string', useNull: true  },
 { name: 'bepadoUpdateLongDescription', type: 'string', useNull: true  },
 { name: 'bepadoUpdateShortDescription', type: 'string', useNull: true  },
 { name: 'bepadoUpdateName', type: 'string', useNull: true  },
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
 * Disable the shippingFree field for bepado products
 */
//{block name="backend/article/view/detail/settings" append}
Ext.define('Shopware.apps.Article.view.detail.Settings-Bepado', {
    override: 'Shopware.apps.Article.view.detail.Settings',

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

            field = me.up('window').down('article-settings-field-set').down('checkboxfield[fieldLabel=' + me.snippets.shippingFree.field + ']');

            if (field) {
                field.setReadOnly(attributes.get('bepadoShopId') > 0);
            }
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
            me.getOverwriteCombo('{s name=updatePrice}Update prices{/s}', 'attribute[bepadoUpdatePrice]'),
            me.getOverwriteCombo('{s name=updateImage}Update images{/s}', 'attribute[bepadoUpdateImage]')
        ];
    },

    getRightContainer: function() {
        var me = this;

        return [
            me.getOverwriteCombo('{s name=updateLongDescription}Update long description{/s}', 'attribute[bepadoUpdateLongDescription]'),
            me.getOverwriteCombo('{s name=updateShortDescription}Update short description{/s}', 'attribute[bepadoUpdateShortDescription]'),
            me.getOverwriteCombo('{s name=updateName}Update name{/s}', 'attribute[bepadoUpdateName]')
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
            editable: false,
            emptyText: '{s name=inherit}Inherit{/s}'
        };
    },

    getOverwriteStore: function() {
        var me = this;

        return Ext.create('Ext.data.Store', {
            fields: [ { name: 'value', useNull: true }, { name: 'description' } ],
            data: [
                { value: 'inherit', description: '{s name=inherit}Inherit{/s}' },
                { value: 'overwrite', description: '{s name=automatically}Automatic{/s}' },
                { value: 'no-overwrite', description: '{s name=manually}Manual{/s}' }
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
