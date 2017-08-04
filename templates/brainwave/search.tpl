<!-- Шаблон search.tpl -->
<!-- header.tpl -->
{include_tpl('header')}

{$loc_items_num = count($items)}
<!-- Главный контейнер (стили блоков / настройки фона, цвета текста и другие) -->
<div id="page-standart" style="padding: 130px 0 100px;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">


    <h1>
        {tlang('Search results')}
    </h1>

        <p>{tlang('Search for:')} <q>{$search_title}</q></p>
        <p>{tlang('Result:')} {$loc_items_num}</p>


        {if $loc_items_num > 0}
           
            {foreach $items as $item}
                {$loc_page_category_name = get_category_name($item.category)}
             
                    <h2>
                        <a href="{site_url($item.full_url)}">{$item.title}</a>
                    </h2>
                    {if $loc_page_category_name}
                    <p>
                        <span>{tlang('Category:')}</span>
                        <a href="{site_url($item.cat_url)}" class="">{$loc_page_category_name}</a>
                    </p>
                    {/if}
                    <div>
                        {if $item.parsedText}
                            {$item.parsedText}
                        {else:}
                            {$item.prev_text}
                        {/if}
                    </div>
              
            {/foreach}
           
        {else:}
            <p>
                {tlang('No results were found! Please try typing something else, or use the menu to find more content')}
            </p>
        {/if}

        {if $pagination}
        
            {$pagination}
       
        {/if}
                </div>
            </div>
        </div>
    </div>