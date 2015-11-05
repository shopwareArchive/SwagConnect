{extends file="parent:frontend/checkout/items/voucher.tpl"}

{block name="frontend_checkout_cart_item_voucher_details_title"}
	<span class="connect--dispatch-title">
		{if empty($connectContent)}
			{s name=connect/checkout/dispatch_title_single}Versandkosten für die Lieferung{/s}
		{else}
			{s name=connect/checkout/dispatch_title}Versandkosten für die Lieferung {counter name=connectIndex} von {$connectShops|count + $addBaseShop}{/s}
		{/if}
	</span>
{/block}

{block name="frontend_checkout_cart_item_voucher_tax_price"}
	{block name="frontend_checkout_connect_dispatch_quantity"}
        {if $hideSinglePrice}
            <div class="panel--td column--tax-price block is--align-right"></div>
        {else}
            <div class="panel--td column--quantity block is--align-right"></div>
        {/if}
	{/block}

	{block name="frontend_checkout_connect_dispatch_unit_price"}
		<div class="panel--td column--unit-price block is--align-right">
				{block name="frontend_checkout_connect_dispatch_unit_price_label"}
				<div class="column--label unit-price--label">
					{s name="CartColumnPrice" namespace="frontend/checkout/cart_header"}{/s}
				</div>
			{/block}

			{block name="frontend_checkout_connect_dispatch_unit_price_value"}
				{if !$hideSinglePrice}
					{if isset($connectShippingCosts[$shopId])}
						{if $connectShippingCosts[$shopId] > 0 }
							{$connectShippingCosts[$shopId]|currency}
						{/if}
					{elseif $connectShippingCostsOrg}
						{$connectShippingCostsOrg|currency}
					{/if}
				{/if}
			{/block}
		</div>
	{/block}
{/block}

{block name="frontend_checkout_cart_item_voucher_total_sum_display"}
	{if isset($connectShippingCosts[$shopId])}
		{if $connectShippingCosts[$shopId] > 0 }
			{$connectShippingCosts[$shopId]|currency}
		{/if}
	{elseif $connectShippingCostsOrg}
		{$connectShippingCostsOrg|currency}
    {/if}
{/block}

{block name="frontend_checkout_cart_item_voucher_details_sku"}{/block}

{block name="frontend_checkout_cart_item_voucher_total_sum"}
	<div class="connect--dispatch-row">
		{$smarty.block.parent}
	</div>
{/block}

{block name="frontend_checkout_cart_item_voucher_delete_article"}
	<div class="connect--dispatch-row">
		{$smarty.block.parent}
	</div>
{/block}

{block name="frontend_checkout_cart_item_voucher_delete_article"}{/block}