{s name=text/config_import_description namespace=backend/bepado/view/main}
    <div class=bepado-info>
        <div class=content>
            Hier können Sie den Import von bepado-Produkten in ihr System beeinflussen.
            <br>
            <br>
            <ul>
                <li class="question">
                    <p>
                        Die hier ausgewählten Felder werden automatisch überschrieben, wenn der Quellshop diese ändert.
                        Sie können auf Artikel-Ebene Ausnahmen definieren.
                    </p>
                </li>
                <li class="question">
                    <p>
                        Der Import von Bildern kann den Import verlangsamen. Wenn Sie viele Produkte importieren möchten,
                        sollten Sie diese Option  <strong>nicht</strong> aktivieren und die Bilder über den CronJob oder
                        »Geänderte Produkte« importieren.
                    </p>
                </li>
                <li class="question">
                    <p>Hier geben Sie an, in welche Shop Kategorie Ihre Produkte importiert werden, wenn kein „Kategorie-Mapping“ vorgenommen wurde.</p>
                </li>
            </ul>
        </div>
    </div>
{/s}