//{namespace name=backend/bepado/view/main}

//{block name='backend/bepado/view/main/home_page'}
Ext.define('Shopware.apps.Bepado.view.main.HomePage', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.bepado-home-page',

    //border: false,
    layout: 'fit',
    bodyPadding: 25,
    autoScroll: true,
    unstyled: true,

    initComponent: function() {
        var me = this;

        me.html = me.getHTMLContent();
        me.callParent(arguments);
    },

    getHTMLContent: function() {
        var me = this;
        me.htmlTpl = [
            '<div class="bepado-home-page">',
                '<h3 class="headline">Bepado – be part of it!</h3>',
                '<div class="content">',
                    '<p>',
                    'Bepado ist die erste soziale B2B-Handelsplattform der Welt. Ziel des Portals ist die Vernetzung von ',
                    'Onlinehändlern untereinander, damit diese ihre Angebote durch die Sortimente anderer Teilnehmer ergänzen',
                    'können. Das schafft, ganz im Sinne eines sozialen Netzwerks, eine größere Reichweite für den Einzelnen,',
                    'sorgt für mehr Unabhängigkeit von großen Marktplätzen, erlaubt Dropshipping uvm. und bringt jedem ',
                    'Teilnehmer unterm Strich mehr Umsatz ein. Kurzum: bepado ist ein Netzwerk, das den eCommerce nachhaltig',
                    'stärken wird! Mit der Installation dieses Plugins sind Sie nur noch einen Schritt von bepado entfernt.<br/><br/>',
                    'Nutzen Sie diese Erweiterung, um Ihre Artikel in das Netzwerk einzupflegen und Ihrerseits die für Sie ',
                    'in Frage kommenden Artikel in Ihren Online-Shop zu importieren.<br/><br/>Im Detail unterstützt das Plugin folgende Funktionen:',
                    '</p>',
                    '<ul>',
                        '<li>',
                            '<strong>Produkt Import</strong>',
                            '<p>Importieren Sie Ihre abonnierten Artikel in Ihren Shop, um Ihr Produktsortiment mit wenigen Handgriffen zu erweitern.</p>',
                        '</li>',

                        '<li>',
                            '<strong>Produkt Export</strong>',
                            '<p>Exportieren Sie beliebige Produkte nach bepado und stellen Sie diese anderen Shopbetreibern im Netzwerk zur Verfügung.</p>',
                        '</li>',

                        '<li>',
                            '<strong>Kategoriezuweisung</strong>',
                            '<p>Weisen Sie Ihren Kategorien eine von den vorgebenen Kategorien auf bepado zu.</p>',
                        '</li>',

                        '<li>',
                            '<strong>Cloud Search</strong>',
                            '<p>Durchsuchen Sie mit dieser ausgeklügelten Funktion das gesamte Netzwerk übergreifend nach für Sie in Frage kommenden Artikeln.</p>',
                        '</li>',
                    '</ul>',
                '</div>',
            '</div>'
        ].join('');

        return me.htmlTpl;
    }
});
//{/block}