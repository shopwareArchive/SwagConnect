{namespace name="frontend/connect/shipping_costs"}

{* Include the stylesheet *}
{block name="frontend_index_header_css_screen" append}
    <link rel="stylesheet" href="{link file='frontend/_resources/styles/connect.css'}"/>
{/block}


{if $connectShipping|count > 0}
    <h1>{s name="connect_storage_dispatch"}Lagerversand{/s}</h1>

    {s name="connect_dispatch_tax_info"}Der Steuersatz für die Brutto-Angaben kann ggf. geringer ausfallen.{/s}
{/if}

{foreach from=$connectShipping item=item name=shops}
    <fieldset class="connect_collapsible">
        {if $connectShopInfo}
            <h2>
                <b {if !$smarty.foreach.shops.first}class="collapsed"{/if}>&raquo;</b>&nbsp;{s name="connect_dispatch_shop_name"}Versand von »{$item.shopInfo.name}«{/s}
            </h2>
        {else}
            <h2>
                <b {if !$smarty.foreach.shops.first}class="collapsed"{/if}>&raquo;</b>&nbsp;{s name="connect_dispatch_shop_id"}Versand für Lager {$item.shopInfo.id}{/s}
            </h2>
        {/if}

        <div class="{if !$smarty.foreach.shops.first}hidden-content {/if}content">
            {foreach from=$item.rules key=rulesType item=rules}
                {if $rulesType == "country"}
                    <strong>{s name="connect_dispatch_country_label"}Versandkosten nach Land: {/s}</strong>
                    <br>
                {elseif $rulesType == "weight"}
                    <strong>{s name="connect_dispatch_weight_label"}Versandkosten nach Gewicht: {/s}</strong>
                    <br>
                {elseif $rulesType == "minimum"}
                    <strong>{s name="connect_dispatch_minimum_value_label"}Versandkosten nach Einkaufswert: {/s}</strong>
                    <br>
                {elseif $rulesType == "freeCarriage"}
                    {assign var="freeCarriage" value="{$rule}"}
                    {continue}
                {/if}
                <br>
                <table class="dispatch-ruletable">
                    <thead>
                    <tr>
                        <th>{s name="connect_dispatch_country_column_header"}Land{/s}</th>
                        <th>{s name="connect_dispatch_max_weight"}max Gewicht{/s}</th>
                        <th>{s name="connect_dispatch_minimum_basket_value"}Einkaufswert{/s}</th>
                        <th>{s name="connect_dispatch_net_price"}Netto{/s}</th>
                        <th>{s name="connect_dispatch_gross_price"}Brutto{/s}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $rules as $rule}
                        {foreach from=$rule.values item=ruleValue}
                            <tr>
                                <td>{$ruleValue.value}</td>
                                <td>{if $rule.maxWeight}{$rule.maxWeight} kg{/if}</td>
                                <td>{if $rule.minimumBasketValue}{$rule.minimumBasketValue} €{/if}</td>
                                <td>{$ruleValue.netPrice} €</td>
                                <td>{$ruleValue.grossPrice} €</td>
                            </tr>
                        {/foreach}
                    {/foreach}
                    </tbody>
                </table>
			{foreachelse}
				<p>{s name="connect_dispatch_no_rules_available"}Für dieses Lager sind zZt keine Regeln verfügbar.{/s}</p>
            {/foreach}
            <div class="clear"></div>
            {if $freeCarriage}
                <p>
                    <strong>{s name="connect_dispatch_free_carriage"}Ab einem Warenkorb-Wert von {$rule.values.0.value|currency} ist die Lieferung versandkostenfrei.{/s}</strong>
                </p>
                {assign var="freeCarriage" value=""}
            {/if}
        </div>
    </fieldset>
{/foreach}

<br>
<h1>{s name="connect_dispatch_direct"}Direkt-Versand{/s}</h1>
