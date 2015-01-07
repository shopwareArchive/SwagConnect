{s name=text/export namespace=backend/bepado/view/main}
    <div class=bepado-home-page>
        <h3 class=headline>Export</h3>
        <div class=content>
            <p>Hier können Sie Produkte, die Sie aus Ihrem System zu bepado exportieren möchten, verwalten.</p>
            <ul>
                <li>
                    <strong>Konfiguration</strong>
                    <p>Hier können Sie den Export Ihrer Produkte nach bepado beeinflussen.</p>
                    <p>
                        <span style=text-decoration:underline;>Produkt-Beschreibungsfeld</span>
                        Konfigurieren Sie, welches Feld als Produkt-Langbeschreibung nach bepado exportiert werden soll.
                        Haben Sie in ihrer Langbeschreibung aufwändige SEO-optimierte Texte gepflegt, möchten Sie
                        vielleicht eher »attribute.bepadoProductDescription« verwenden, um duplicate content zu vermeiden.
                        Dieses spezielle bepado-Beschreibungsfeld finden Sie in der Artikel-Eingabemaske unter »Zusatzfeld«
                    </p>
                    <p>
                        <span style=text-decoration:underline;>Geänderte Produkte automatisch synchronisieren</span>
                        Wenn Sie ein nach bepado exportiertes Produkt ändern (etwa im Preis), wird die Änderung
                        automatisch nach bepado synchronisiert. So müssen Sie die Aktualisierungen nicht händisch durchführen.
                    </p>
                    <p>
                        <span style=text-decoration:underline;>Preis Konfiguration</span>
                        bepado-Produkte haben einen  Endkunden-Preis und einen Händler-Preis. Konfigurieren Sie hier,
                        aus welcher lokalen Kundengruppe und welchem Preisfeld die entsprechenden Preise ausgelesen werden sollen.
                    </p>
                </li>

                <li>
                    <strong>Kategorie Mapping</strong>
                    <p>
                        Hier legen Sie fest in welcher bepado Kategorie Ihre exportieren Produkte angezeigt werden sollen.
                        <br />
                        In der linken Spalte wählen Sie eine Shopware-Kategorie aus, für die Sie ein Mapping
                        erzeugen möchten. In der rechten Spalte wählen Sie dann die bepado Kategorie aus, der Ihre Produkte
                        zugeordnet werden sollen. Dies erhöht die Auffindbarkeit Ihrer Produkte für andere Händler.
                        <br />
                        Beim Export werden Produkte dieser Shopware Kategorie nun automatisch der gewählten bepado
                        Kategorie zugewiesen.
                    </p>
                </li>

                <li>
                    <strong>Produkte</strong>
                    <p>
                        Hier exportieren Sie Ihre Produkte schließlich zu bepado. Dazu markieren Sie die Produkte und
                        klicken im Anschluss auf den grünen Button „Produkte zum Export einfügen/aktualisieren“.
                        Ihre Produkte werden automatisch zu bepado exportiert und müssen nur noch freigegeben werden.
                    </p>
                </li>

                <li>
                    <strong>Liefergruppen</strong>
                    <p>
                        Hier haben Sie die Möglichkeit, Liefergruppen zu erstellen. In diesen Liefergruppen können Sie
                        bestimmte Informationen wie beispielsweise Länderinformationen, Lieferzeit in Tagen, Preise und
                        Postleitzahlen- Präfix hinterlegen. Durch die Zuweisung der Liefergruppen zu bestimmten Artikeln,
                        übernehmen diese die hinterlegten Eigenschaften auf bepado. Je nachdem entstehen individuelle Versandkosten.
                    </p>
                </li>
            </ul>
        </div>
    </div>
{/s}