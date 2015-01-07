{s name=text/import namespace=backend/bepado/view/main}
    <div class=bepado-home-page>
        <h3 class=headline>Import</h3>
        <div class=content>
            <p>Verwalten und importieren Sie die von Ihnen auf bepado abonnierten Produkte</p>
            <ul>
                <li>
                    <strong>Konfiguration</strong>
                    <p>Hier können Sie den Import von bepado Produkten in Ihr System konfigurieren.</p>
                    <p>
                        <span style=text-decoration:underline;>Felder beim Import überschreiben</span>
                        Die hier ausgewählten Felder werden automatisch überschrieben, wenn der Quellshop diese ändert.
                        Sie können auf Artikel Ebene Ausnahmen definieren.
                    </p>
                    <p>
                        <span style=text-decoration:underline;>Bilder beim Produkt-Erstimport importieren</span>
                        Der Import von Bildern kann den Import verlangsamen. Wenn Sie viele Produkte
                        Importieren möchten, sollten Sie diese Option nicht aktivieren und die Bilder per CronJob oder
                        über „geänderte Produkte“ importieren.
                    </p>
                </li>

                <li>
                    <strong>Kategorie Mapping</strong>
                    <p>Legen Sie hier fest in welchen Kategorien Ihres Shops die importierten Produkte angezeigt werden sollen</p>
                </li>

                <li>
                    <strong>Produkte</strong>
                    <p>
                        Der Produktimport erfolgt automatisch, sobald Sie Produkte anderer Händler auf bepado abonnieren
                        und eine Verbindung zu bepado hergestellt worden ist. Sie können die Produkte in diesem Menü
                        nach dem Import aktivieren, deaktivieren oder weiter bearbeiten.
                    </p>
                </li>

                <li>
                    <strong>Letzte Änderungen</strong>
                    <p>
                        Hier haben Sie eine Übersicht von Produkten, die durch den Hersteller geändert wurden,
                        bei denen die Änderungen aber noch nicht durchgeführt wurden – etwa weil Sie konfiguriert haben,
                        dass Sie die Preise selber verwalten möchten.
                        <br />
                        Haben Sie konfiguriert, dass beim Produkt-Erstimport keine Bilder importiert werden sollen,
                        haben Sie hier die Möglichkeit, den Bilder Import manuell anzustoßen.
                    </p>
                </li>
            </ul>
        </div>
    </div>
{/s}