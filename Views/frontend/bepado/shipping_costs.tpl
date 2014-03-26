{namespace name="frontend/bepado/shipping_costs"}

<h1>{s name="bepad_storage_dispatch"}Lagerversand{/s}</h1>

{foreach from=$bepadoShipping item=item}
    {if $bepadoShopInfo}
    <h2>{s name="bepado_dispatch_shop_name"}Versand von »{$item.shopInfo.name}«{/s}</h2>
    {else}
    <h2>{s name="bepado_dispatch_shop_id"}Versand für Lager {$item.shopInfo.id}{/s}</h2>
    {/if}
    {foreach from=$item item=rules}
        {foreach from=$rules item=rule key=type}
            {if $type == "country"}
                {foreach from=$rule.values item=ruleValue}
                    {$ruleValue}: {$rule.price}€
                {/foreach}
            {/if}
       {/foreach}
    {/foreach}
{/foreach}

<br>
<h1>Direkt-Versand</h1>
