{namespace name="frontend/bepado/shipping_costs"}

<h1>{s name="bepad_storage_dispatch"}Lagerversand{/s}</h1>

{foreach from=$bepadoShipping item=item}
    {if $bepadoShopInfo}
        <h2>{s name="bepado_dispatch_shop_name"}Versand von »{$item.shopInfo.name}«{/s}</h2>
    {else}
        <h2>{s name="bepado_dispatch_shop_id"}Versand für Lager {$item.shopInfo.id}{/s}</h2>
    {/if}
    {foreach $item.rules as $rule}
        <strong>Versand nach
        {if $rule.type == "country"}
            Land
        {/if}
        :</strong>
        <table>
            <thead>
                <tr>
                    <th>Rule</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$rule.values item=ruleValue}
                    <tr>
                        <td>{$ruleValue}</td>
                        <td>{$rule.price} €</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {/foreach}
{/foreach}

<br>
<h1>Direkt-Versand</h1>
