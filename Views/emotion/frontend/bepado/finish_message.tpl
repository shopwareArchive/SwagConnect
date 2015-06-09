<div class="error">
    <div class="center">
        <strong>
            {foreach from=$bepadoMessages item=bepadoShop}
                {foreach from=$bepadoShop item=bepadomessage}
                    {$message = $bepadomessage->message}
                    {foreach from=$bepadomessage->values key=key item=value}
                        {$message = "%{$key}"|str_replace:$value:$message}
                    {/foreach}
                    {$message}<br>
                {/foreach}
            {/foreach}
        </strong>
        <br>
        <a href="{url}">{s name="frontend_checkout_cart_bepado_refresh"}Klicken Sie hier um die Seite zu aktualisieren{/s}</a>
    </div>
</div>