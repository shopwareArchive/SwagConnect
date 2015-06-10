<div class="grid_4 first" id="left">
    <div class="filter_search">
        {* Headline *}
        <h3 class="heading">{s name="SearchLeftHeadlineCutdown"}Suchergebnis einschr&auml;nken{/s}</h3>


        {* Filter by supplier *}
        {block name='frontend_search_filter_supplier'}
            <div class="searchbox">
                <h3>{se name='SearchLeftHeadlineSupplier'}Hersteller{/se}</h3>
                {assign var=searchVendorsFirst value=$searchResult->vendors|array_slice:0:10}
                {assign var=searchVendorsRest value=$searchResult->vendors|array_slice:10}

                <ul>
                    {if !$filterVendor}
                        {foreach from=$searchVendorsFirst item=count key=vendor}
                        {if $vendor}
                            <li><a href="{url query=$searchQuery perPage=$perPage vendor=$vendor}">{$vendor} ({$count})</a></li>
                        {/if}
                        {/foreach}

                        {if $searchVendorsRest}
                            <form name="frmsup" method="POST" action="{url query=$searchQuery perPage=$perPage}" id="frmsup">
                                <select name="vendor" class="auto_submit">
                                    <option value="">{se name='SearchLeftInfoSuppliers'}Bitte wählen..{/se}</option>
                                    {foreach from=$searchVendorsRest item=count key=vendor}
                                        <option value="{$vendor}">{$vendor} ({$count})</option>
                                    {/foreach}
                                </select>
                            </form>
                        {/if}
                    {else}
                        <li class="active">{$filterVendor}</li>
                        <li class="showall"><a href="{url query=$searchQuery perPage=$perPage}">{se name='SearchLeftLinkAllSuppliers' force}Alle Hersteller{/se}</a></li>
                    {/if}
                </ul>

            </div>
        {/block}

        {* Filter by price *}
    </div>
</div>