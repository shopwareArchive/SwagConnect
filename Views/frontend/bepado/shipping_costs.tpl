{namespace name="frontend/bepado/shipping_costs"}

<h1>{s name="bepado_storage_dispatch"}Lagerversand{/s}</h1>

{s name="bepado_dispatch_tax_info"}Der Steuersatz für die Brutto-Angaben kann ggf. geringer ausfallen.{/s}

{foreach from=$bepadoShipping item=item}
    {if $bepadoShopInfo}
        <h2>{s name="bepado_dispatch_shop_name"}Versand von »{$item.shopInfo.name}«{/s}</h2>
    {else}
        <h2>{s name="bepado_dispatch_shop_id"}Versand für Lager {$item.shopInfo.id}{/s}</h2>
    {/if}

    {foreach $item.rules as $rule}
        {if $rule.type == "country"}
            <strong>{s name="bepado_dispatch_country_label"}Versandkosten nach Land: {/s}</strong><br>
            {assign var="rule_header" value="{s name="bepado_dispatch_country_column_header"}Land{/s}"}
        {elseif $rule.type == "weight"}
            <strong>{s name="bepado_dispatch_weight_label"}Versandkosten nach Gewicht: {/s}</strong><br>
            {assign var="rule_header" value="{s name="bepado_dispatch_country_column_header"}bis Gewicht{/s}"}
        {elseif $rule.type == "freeCarriage"}
            {assign var="freeCarriage" value="{$rule}"}
            {continue}
        {/if}
        <br>

        <table>
            <thead>
                <tr>
                    <th>{$rule_header}</th>
                    <th>{s name="bepado_dispatch_net_price"}Netto{/s}</th>
                    <th>{s name="bepado_dispatch_gross_price"}Brutto{/s}</th>
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
    {if $freeCarriage}
        {s name="bepado_dispatch_free_carriage"}Ab einem Warenkorb-Wert von {$rule.values.0.value|currency} ist die Lieferung versandkostenfrei.{/s}
        {assign var="freeCarriage" value=""}
    {/if}
{/foreach}

<br>
<h1>{s name="bepado_dispatch_direct"}Direkt-Versand{/s}</h1>
