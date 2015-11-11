{namespace name="frontend/connect/shipping_costs"}

{if $connectShipping|count > 0}
	{block name="frontend_custom_connect_dispatch"}
		<div class="connect--store-dispatch">
			{block name="frontend_custom_connect_dispatch_headline"}
				<h1 class="store-dispatch--headline">
					{s name="connect_storage_dispatch"}Lagerversand{/s}
				</h1>
			{/block}

			{block name="frontend_custom_connect_dispatch_text"}
				<span class="store-dispatch--text">
					{s name="connect_dispatch_tax_info"}Der Steuersatz für die Brutto-Angaben kann ggf. geringer ausfallen.{/s}
				</span>
			{/block}
		</div>
	{/block}
{/if}

{block name="frontend_custom_connect_dispatches"}
	{foreach from=$connectShipping item=item name=shops}
		{block name="frontend_custom_connect_dispatch"}
			<div class="connect--collapsible-dispatch">
				{block name="frontend_custom_connect_dispatch_header"}
					<div class="collapsible-dispatch--header">
						{block name="frontend_custom_connect_dispatch_headline"}
							<h4 class="collapsible-dispatch--headline">
								{if $connectShopInfo}
									<span class="connect--raquo{if !$smarty.foreach.shops.first} collapsible-dispatch--collapsed{/if}">&raquo;</span>&nbsp;{s name="connect_dispatch_shop_name"}Versand von »{$item.shopInfo.name}«{/s}
								{else}
									<span class="connect--raquo{if !$smarty.foreach.shops.first} collapsible-dispatch--collapsed{/if}">&raquo;</span>&nbsp;{s name="connect_dispatch_shop_id"}Versand für Lager {$item.shopInfo.id}{/s}
								{/if}
							</h4>
						{/block}
					</div>
				{/block}

				{block name="frontend_custom_connect_dispatch_body"}
					<div class="collapsible-dispatch--body">
						{foreach from=$item.rules key=rulesType item=rules}
							{if $rulesType == "country"}
								{block name="frontend_custom_connect_dispatch_country"}
									<span class="collapsible-dispatch--rule-label is--strong">{s name="connect_dispatch_country_label"}Versandkosten nach Land: {/s}</span>
									<br />
								{/block}
							{elseif $rulesType == "weight"}
								{block name="frontend_custom_connect_dispatch_weight"}
									<span class="collapsible-dispatch--rule-label is--strong">{s name="connect_dispatch_weight_label"}Versandkosten nach Gewicht: {/s}</span>
									<br />
								{/block}
							{elseif $rulesType == "minimum"}
								{block name="frontend_custom_connect_dispatch_min_value"}
									<span class="collapsible-dispatch--rule-label is--strong">{s name="connect_dispatch_minimum_value_label"}Versandkosten nach Einkaufswert: {/s}</span>
									<br />
								{/block}
							{elseif $rulesType == "freeCarriage"}
								{block name="frontend_custom_connect_dispatch_free_carriage"}
									{assign var="freeCarriage" value="{$rule}"}
									{continue}
								{/block}
							{/if}

							{block name="frontend_custom_connect_dispatch_panel"}
								<div class="panel connect--table panel--body panel--table has--border">
									{block name="frontend_custom_connect_dispatch_panel_header"}
										<div class="connect--table-header panel--tr">
											{block name="frontend_custom_connect_dispatch_column_country"}
												<div class="panel--th connect--column-country">
													{s name="connect_dispatch_country_column_header"}Land{/s}
												</div>
											{/block}

											{block name="frontend_custom_connect_dispatch_column_weight"}
												<div class="panel--th connect--column-weight">
													{s name="connect_dispatch_max_weight"}max Gewicht{/s}
												</div>
											{/block}

											{block name="frontend_custom_connect_dispatch_column_value"}
												<div class="panel--th connect--column-value">
													{s name="connect_dispatch_minimum_basket_value"}Einkaufswert{/s}
												</div>
											{/block}

											{block name="frontend_custom_connect_dispatch_column_net"}
												<div class="panel--th connect--column-net">
													{s name="connect_dispatch_net_price"}Netto{/s}
												</div>
											{/block}

											{block name="frontend_custom_connect_dispatch_column_gross"}
												<div class="panel--th connect--column-gross">
													{s name="connect_dispatch_gross_price"}Brutto{/s}
												</div>
											{/block}
										</div>
									{/block}

									{block name="frontend_custom_connect_dispatch_panel_rules"}
										{foreach $rules as $rule}
											{block name="frontend_custom_connect_dispatch_panel_rule"}
												{foreach from=$rule.values item=ruleValue}
													{block name="frontend_custom_connect_dispatch_panel_values"}
														<div class="connect--table-values panel--tr">
															{block name="frontend_custom_connect_dispatch_panel_country"}
																<div class="panel--td connect--column-country">
																	<div class="column--label">
																		{s name="connect_dispatch_country_column_header"}Land{/s}
																	</div>
																	<div class="column--value">
																		{$ruleValue.value}
																	</div>
																</div>
															{/block}

															{block name="frontend_custom_connect_dispatch_panel_weight"}
																<div class="panel--td connect--column-weight">
																	<div class="column--label">
																		{s name="connect_dispatch_max_weight"}max Gewicht{/s}
																	</div>
																	<div class="column--value">
																		{if $rule.maxWeight}{$rule.maxWeight} kg{/if}
																	</div>
																</div>
															{/block}

															{block name="frontend_custom_connect_dispatch_panel_value"}
																<div class="panel--td connect--column-value">
																	<div class="column--label">
																		{s name="connect_dispatch_minimum_basket_value"}Einkaufswert{/s}
																	</div>
																	<div class="column--value">
																		{if $rule.minimumBasketValue}{$rule.minimumBasketValue} €{/if}
																	</div>
																</div>
															{/block}

															{block name="frontend_custom_connect_dispatch_panel_net"}
																<div class="panel--td connect--column-net">
																	<div class="column--label">
																		{s name="connect_dispatch_net_price"}Netto{/s}
																	</div>
																	<div class="column--value">
																		{$ruleValue.netPrice} €
																	</div>
																</div>
															{/block}

															{block name="frontend_custom_connect_dispatch_panel_gross"}
																<div class="panel--td connect--column-gross">
																	<div class="column--label">
																		{s name="connect_dispatch_gross_price"}Brutto{/s}
																	</div>
																	<div class="column--value">
																		{$ruleValue.grossPrice} €
																	</div>
																</div>
															{/block}
														</div>
													{/block}
												{/foreach}
											{/block}
										{/foreach}
									{/block}
								</div>
							{/block}
						{foreachelse}
							{block name="frontend_custom_connect_dispatch_panel_no_rules"}
								<span class="collapsible-dispatch--no-rules">{s name="connect_dispatch_no_rules_available"}Für dieses Lager sind zZt keine Regeln verfügbar.{/s}</span>
							{/block}
						{/foreach}

						{block name="frontend_custom_connect_dispatch_free_carriage_value"}
							{if $freeCarriage}
								<p class="connect--free-carriage">
									<span class="is--strong">{s name="connect_dispatch_free_carriage"}Ab einem Warenkorb-Wert von {$rule.values.0.value|currency} ist die Lieferung versandkostenfrei.{/s}</span>
								</p>
								{assign var="freeCarriage" value=""}
							{/if}
						{/block}
					</div>
				{/block}
			</div>
		{/block}
	{/foreach}
{/block}


{block name="frontend_custom_connect_store_dispatch"}
	<h1 class="store-dispatch--headline">
		{s name="connect_dispatch_direct"}Direkt-Versand{/s}
	</h1>
{/block}