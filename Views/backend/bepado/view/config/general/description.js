/**
 * Shopware 4
 * Copyright © shopware AG
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
/**
 * Shopware SwagBepado Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
//{namespace name=backend/bepado/view/main}
//{block name="backend/bepado/view/config/general/description"}
Ext.define('Shopware.apps.Bepado.view.config.general.Description', {
    /**
     * Define that the description field set is an extension of the Ext.form.FieldSet
     * @string
     */
    extend:'Ext.form.FieldSet',

    /**
     * The Ext.container.Container.layout for the fieldset's immediate child items.
     * @object
     */
    layout: 'fit',

    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets.
     * @string
     */
    alias:'widget.bepado-config-description',
    /**
     * Set css class for this component
     * @string
     */
    cls: Ext.baseCSSPrefix + 'bepado-config-description',

    /**
     * Contains all snippets for the component
     * @object
     */
    snippets: {
        title: '{s name=config/description}Beschreibung{/s}'
    },

    /**
     * Initialize the view.config.general.Description
     * and defines the necessary default configuration
     * @return void
     */
    initComponent:function () {
        var me = this;

        me.title = me.snippets.title;
        me.html = me.getHTMLContent();

        me.callParent(arguments);
    },

    /**
     * Returns description fieldset content
     * @return string
     */
    getHTMLContent: function() {
        var me = this;
        me.htmlTpl = [
            '<p style="padding-bottom:8px;font-style:italic;">',
            'Bepado ist die erste soziale B2B-Handelsplattform der Welt. Ziel des Portals ist die Vernetzung von Onlinehändlern untereinander, damit diese ihre Angebote durch die Sortimente anderer Teilnehmer ergänzen können. Das schafft, ganz im Sinne eines sozialen Netzwerks, eine größere Reichweite für den Einzelnen, sorgt für mehr Unabhängigkeit von großen Marktplätzen, erlaubt Dropshipping uvm. und bringt jedem Teilnehmer unterm Strich mehr Umsatz ein. Kurzum: bepado ist ein Netzwerk, das den eCommerce nachhaltig stärken wird!',
            '<br><br>',
            'Nutzen Sie diese Erweiterung, um Ihre Artikel in das Netzwerk einzupflegen und Ihrerseits die für Sie in Frage kommenden Artikel in Ihren Online-Shop zu importieren.',
            '<br><br>',
            'Funktionen:<br>',
            '- Produkt Import: Importieren Sie Ihre abonnierten Artikel in Ihren Shop, um Ihr Produktsortiment mit wenigen Handgriffen zu erweitern.<br>',
            '- Produkt Export: Exportieren Sie beliebige Produkte nach bepado und stellen Sie diese anderen Shopbetreibern im Netzwerk zur Verfügung.<br>',
            '- Kategoriezuweisung: Weisen Sie Ihren Kategorien eine von den vorgebenen Kategorien auf bepado zu.<br>',
            '- Cloud Search: Durchsuchen Sie mit dieser ausgeklügelten Funktion das gesamte Netzwerk übergreifend nach für Sie in Frage kommenden Artikeln.',
            '</p>'
        ].join('');

        return me.htmlTpl;
    }
});
//{/block}