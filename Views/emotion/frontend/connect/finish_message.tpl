<div class="error">
    <div class="center">
        <strong>
            {foreach from=$connectMessages item=connectShop}
                {foreach from=$connectShop item=connectmessage}
                    {$message = $connectmessage->message}
                    {foreach from=$connectmessage->values key=key item=value}
                        {$message = "%{$key}"|str_replace:$value:$message}
                    {/foreach}
                    {$message}<br>
                {/foreach}
            {/foreach}
        </strong>
        <br>
        <a href="{url}">{s name="frontend_checkout_cart_connect_refresh"}Klicken Sie hier um die Seite zu aktualisieren{/s}</a>
    </div>
</div>