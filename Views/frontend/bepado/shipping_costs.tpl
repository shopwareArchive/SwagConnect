{namespace name="frontend/bepado/shipping_costs"}

<h1>{s name="bepad_storage_dispatch"}Lagerversand{/s}</h1>

Der Steuersatz für die Brutto-Angaben kann ggf. geringer ausfallen.

{foreach from=$bepadoShipping item=item}
    {if $bepadoShopInfo}
        <h2>{s name="bepado_dispatch_shop_name"}Versand von »{$item.shopInfo.name}«{/s}</h2>
    {else}
        <h2>{s name="bepado_dispatch_shop_id"}Versand für Lager {$item.shopInfo.id}{/s}</h2>
    {/if}
    {foreach $item.rules as $rule}
        {if $rule.type == "country"}
            <strong>Versandkosten nach Land: </strong><br>
            {assign var="rule_header" value="Land"}
        {elseif $rule.type == "weight"}
            <strong>Versandkosten nach Gewicht: </strong><br>
            {assign var="rule_header" value="bis Gewicht"}
        {/if}
        <br>

        <table>
            <thead>
                <tr>
                    <th>{$rule_header}</th>
                    <th>Price (net)</th>
                    <th>Price (gross)</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$rule.values item=ruleValue}
                    <tr>
                        <td>{$ruleValue.value}</td>
                        <td>{$ruleValue.netPrice} €</td>
                        <td>{$ruleValue.grossPrice} €</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {/foreach}
{/foreach}

<br>
<h1>Direkt-Versand</h1>
