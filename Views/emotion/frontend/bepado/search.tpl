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
            <div>
                <div class="listing" id="listing-1col">
                    {foreach from=$searchResult->results item=result key=key name=list}
                        <div class="bepado-article artbox">
                            <div class="inner">

                                <a title="{$result->vendor|escape}" href="{$result->url}" class="bepado-img">
                                    {if $result->images[0]}
                                        <img src="{$result->images[0]}">
                                    {else}
                                        <img src="{link file='frontend/_resources/images/no_picture.jpg'}">
                                    {/if}
                                </a>

                                <a href="{$result->url}" class="title" title="{$result->title|escape}">{$result->title|escape}</a>

                                <p class="desc">
                                    <strong>Hersteller:</strong> {$result->vendor|escape}<br>
                                    Bei insgesamt <strong>{$result->shopCount}</strong> Anbietern gefunden.
                                </p>

                                <p class="price both">
                                    <span class="price">ab {$result->priceFrom|currency}</span>
                                </p>

                                <div class="actions">
                                    <a class="more" href="{$result->url}" title="{$result->title|escape}">Zum Produkt</a>
                                </div>
                                <div class="bepado-clear"></div>
                            </div>
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
