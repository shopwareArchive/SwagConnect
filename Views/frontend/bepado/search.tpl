{extends file='frontend/index/index.tpl'}

{* Include the stylesheet if we're dealing with an bepado product *}
{block name="frontend_index_header_css_screen" append}
    <link rel="stylesheet" href="{link file='frontend/_resources/styles/bepado.css'}" />
{/block}

{* Sidebar left *}
{block name='frontend_index_content_left'}
    {if $searchResult->resultCount > 0}
        {include file='frontend/bepado/search_left.tpl'}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{* Main content *}
{block name='frontend_index_content'}
    <div id="center" class="grid_13">
        {block name='frontend_search_index_headline'}
            <h2>
                {if $searchResult->results|count gt 0}
                    {s name='SearchHeading' namespace='frontend/search/bepado'}Zu "{$searchQuery|escape}" wurden in diesem Shop keine Produkte gefunden,<br> eventuell sind die Produkte unserer Partnershops für Sie interessant.{/s}
                {else}
                    {s name='SearchHeadingEmpty' namespace='frontend/search/bepado'}Leider wurden zu "{$searchQuery|escape}" keine Artikel gefunden{/s}
                {/if}
            </h2>
        {/block}
        {block name='frontend_search_index_result'}
            <div class="bepado-search">
                <div class="listing" id="listing">
                    {foreach from=$searchResult->results item=result key=key name=list}
                        <div class="bepado-article">
                            <div class="bepado-img">
                                {if $result->images[0]}
                                    <img src="{$result->images[0]}">
                                {else}
                                    <img src="{link file='frontend/_resources/images/no_picture.jpg'}">
                                {/if}
                            </div>
                            <div class="bepado-info">
                                <h4>
                                    <a class="bepado-title" href="{$result->url}" title="{$result->title|escape}">{$result->title|escape}</a>
                                </h4>
                                <p><strong>Hersteller:</strong> {$result->vendor|escape}</p>
                                <p>Bei insgesamt <strong>{$result->shopCount}</strong> Anbietern gefunden.</p>
                            </div>
                            <div class="bepado-meta">
                                <p class="bepado-price">ab: <strong>{$result->priceFrom|currency}</strong></p>
                                <a href="{$result->url}" title="{$result->title|escape}" class="more">Zum Produkt</a>
                            </div>
                            <div class="bepado-clear"></div>
                        </div>
                    {/foreach}
                </div>
            </div>
            {if $searchResult->resultCount}
                <div class="clear">&nbsp;</div>
                {include file='frontend/listing/listing_actions.tpl'}
            {/if}
        {/block}
    </div>
{/block}

{block name='frontend_listing_actions_top'}
    <div class="top">
        <div class="sort-filter">&nbsp;</div>
        <form method="post" action="{url query=$searchQuery}">
            <div class="articleperpage rightalign">
                <label>{s name='ListingLabelItemsPerPage'}Artikel pro Seite:{/s}</label>
                <select name="limit" class="auto_submit">
                    {foreach from=$perPages item=value}
                        <option value="{$value}" {if $value == $perPage}selected="selected"{/if}>{$value}</option>
                    {/foreach}
                </select>
            </div>
        </form>
    </div>
{/block}

{block name='frontend_listing_actions_paging'}
    {if $pages.numbers|@count > 1}
        <div class="bottom">
            <div class="paging">
                <label>{se name='ListingPaging'}Blättern:{/se}</label>

                {if $pages.previous}
                    <a href="{url query=$searchQuery page=$pages.previous}" class="navi prev">
                        {s name="ListingTextPrevious"}&lt;{/s}
                    </a>
                {/if}

                {foreach from=$pages.numbers item=number}
                    {if $page == $number}
                        <a title="" class="navi on">{$page}</a>
                    {else}
                        <a href="{url query=$searchQuery perPage=$perPage page=$number}" title="" class="navi">
                            {$number}
                        </a>
                    {/if}
                {/foreach}

                {if $pages.next}
                    <a href="{url query=$searchQuery page=$pages.next}" class="navi more">{s name="ListingTextNext"}&gt;{/s}</a>
                {/if}
            </div>
            <div class="display_sites">
                {se name="ListingTextSite"}Seite{/se} <strong>{if $page}{$page}{else}1{/if}</strong> {se name="ListingTextFrom"}von{/se} <strong>{$numberPages}</strong>
            </div>
        </div>
    {/if}
{/block}

{block name="frontend_listing_actions_class"}
    <div class="listing_actions{if !$pages || $pages.numbers|@count < 2} normal{/if}">
{/block}
