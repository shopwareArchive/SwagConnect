/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

//{namespace name="backend/bepado/view/main"}
//{block name="backend/article/view/detail/settings" append}
Ext.define('Shopware.apps.Article.view.detail.SettingsBepado', {
    override: 'Shopware.apps.Article.view.detail.Settings',

    createBottomElements: function() {
        var me = this;

        me.snippets.shippingLabel = '{s name=article/shipping}Versand{/s}';

        var items =  me.callOverridden(arguments);
        var shipping = Ext.create('Ext.form.field.TextArea', {
            name: 'attribute[bepadoArticleShipping]',
            anchor: '100%',
            width: '100%',
            labelWidth: 155,
            fieldLabel: me.snippets.shippingLabel
        });
        items.push(shipping);

        return items;
    }
});
//{/block}