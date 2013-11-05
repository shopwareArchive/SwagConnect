<div class="table_row bepado-dispatch-row">
	<div class="grid_6">
		<span class="title">
            {if empty($bepadoContent)}
                {s name=bepado/checkout/dispatch_title_single}Versandkosten für die Lieferung{/s}
            {else}
                {s name=bepado/checkout/dispatch_title}Versandkosten für die Lieferung {counter name=bepadoIndex} von {$bepadoShops|count + $addBaseShop}{/s}
            {/if}
		</span>
		&nbsp;
	</div>

	<div class="grid_3">
		&nbsp;
	</div>

	<div class="grid_1">
		&nbsp;
	</div>

	<div class="grid_2 textright">
		{if !$hideSinglePrice}
			{if $bepadoShippingCosts[$shopId]}
				{$bepadoShippingCosts[$shopId]|currency}
			{elseif $bepadoShippingCostsOrg}
				{$bepadoShippingCostsOrg|currency}
			{/if}
		{/if}
	</div>

	<div class="grid_2 textright">
		<strong>{if $bepadoShippingCosts[$shopId]}
			{$bepadoShippingCosts[$shopId]|currency}
		{elseif $bepadoShippingCostsOrg}
			{$bepadoShippingCostsOrg|currency}
		{/if}</strong>
	</div>
	<div class="clear"></div>
</div>