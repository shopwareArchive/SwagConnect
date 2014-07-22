{namespace name="frontend/bepado/shipping_costs"}

{* Include the stylesheet *}
{block name="frontend_index_header_css_screen" append}
    <link rel="stylesheet" href="{link file='frontend/_resources/styles/bepado.css'}"/>
{/block}


{if $bepadoShipping|count > 0}
    <h1>{s name="bepado_storage_dispatch"}Lagerversand{/s}</h1>

    {s name="bepado_dispatch_tax_info"}Der Steuersatz für die Brutto-Angaben kann ggf. geringer ausfallen.{/s}
{/if}

{foreach from=$bepadoShipping item=item name=shops}
    <fieldset class="bepado_collapsible">
        {if $bepadoShopInfo}
            <h2>
                <b {if !$smarty.foreach.shops.first}class="collapsed"{/if}>&raquo;</b>&nbsp;{s name="bepado_dispatch_shop_name"}Versand von »{$item.shopInfo.name}«{/s}
            </h2>
        {else}
            <h2>
                <b {if !$smarty.foreach.shops.first}class="collapsed"{/if}>&raquo;</b>&nbsp;{s name="bepado_dispatch_shop_id"}Versand für Lager {$item.shopInfo.id}{/s}
            </h2>
        {/if}

        <div class="{if !$smarty.foreach.shops.first}hidden-content {/if}content">
            {foreach $item.rules as $rule}
                {if $rule.type == "country"}
                    <strong>{s name="bepado_dispatch_country_label"}Versandkosten nach Land: {/s}</strong>
                    <br>
                    {assign var="rule_header" value="{s name="bepado_dispatch_country_column_header"}Land{/s}"}
                {elseif $rule.type == "weight"}
                    <strong>{s name="bepado_dispatch_weight_label"}Versandkosten nach Gewicht: {/s}</strong>
                    <br>
                    {assign var="rule_header" value="{s name="bepado_dispatch_country_column_header"}bis Gewicht{/s}"}
                {elseif $rule.type == "freeCarriage"}
                    {assign var="freeCarriage" value="{$rule}"}
                    {continue}
                {/if}
                <br>
                <table class="dispatch-ruletable">
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
                <div class="clear"></div>
                {foreachelse}
                <p>Für dieses Lager sind zZt keine Regeln verfügbar.</p>
            {/foreach}

            {if $freeCarriage}
                <p>
                    <strong>{s name="bepado_dispatch_free_carriage"}Ab einem Warenkorb-Wert von {$rule.values.0.value|currency} ist die Lieferung versandkostenfrei.{/s}</strong>
                </p>
                {assign var="freeCarriage" value=""}
            {/if}
        </div>
    </fieldset>
{/foreach}

<br>
<h1>{s name="bepado_dispatch_direct"}Direkt-Versand{/s}</h1>
