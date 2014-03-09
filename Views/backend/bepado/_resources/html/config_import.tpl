{s name=text/config_import namespace=backend/bepado/view/main}
    <div class=bepado-info>
        <div class=content>
            Hier können Sie den Import von bepado-Produkten in ihr System beeinflussen.
            <br>
            <br>
            <ul>
                <li>
                    <strong>Felder beim Import überschreiben</strong>
                    <p>Die hier ausgewählten Felder werden automatisch überschrieben, wenn der Quellshop diese ändert.
                    Sie können auf Artikel-Ebene Ausnahmen definieren.</p>
                </li>
                <li>
                    <strong>Bilder beim Produkt-Erstimport importieren</strong>
                    <p>Der Import von Bildern kann den Import verlangsamen. Wenn Sie viele Produkte importieren möchten,
                    sollten Sie diese Option <strong>nicht</strong> aktivieren und die Bilder über den CronJob
                    oder »Geänderte Produkte« importieren.</p>
                </li>
            </ul>
        </div>
    </div>
{/s}