<div class="row-category">
    {/*}
    <div class="control-group">
        <span class=""> <span class="">
                <input name="categories_to_parce[{echo $cat->id}]" id="categories_to_parce[{echo $cat->id}]" value="1"
                       type="checkbox" {if key_exists($cat->id,$settings)} checked="checked"{/if}/>
            </span>
        </span>
    </div>
    { */}
    <div style="width: 5%;">
        <div class="frame_prod-on_off" data-rel="tooltip" data-placement="top"
             data-original-title="{lang('Загружать товары','admin')}">
            {/*}<span class="prod-on_off cat_change_parse {if !key_exists($cat->id, $settings)} disable_tovar{/if}"
                      data-id="{echo $cat->id}"></span>{ */}
            <span class="prod-on_off cat_change_parse {if $cat->to_parse == false} disable_tovar{/if}"
                  data-id="{echo $cat->id}"></span>
        </div>
    </div>
    <div style="width: 5%;line-height: 24px;">
        <p>
            {echo $cat->isBrain}
        </p>
    </div>
    <div style="width: 5%;">
        <p>{echo $cat->id}</p>
    </div>
    <div class="share_alt" style="width: 20%;">
        <a href="{site_url($cat->url)}" class="go_to_site pull-right btn btn-small" data-rel="tooltip"
           data-placement="top" data-original-title="{lang('go to the website','admin')}" target="blank"><i
                    class="icon-share-alt"></i></a>
        <div class="title lev">
            {if $cat->parent != '-'}
                <span class="simple_tree">↳</span>
            {/if}

            {if $cat->hasChilds}
                <button type="button" class="btn btn-small my_btn_s"
                        style="display: none; " data-rel="tooltip"
                        data-placement="top" data-original-title="{lang('Collapse category','admin')}">
                    <i class="my_icon icon-minus"></i>
                </button>
                <button href="#cat{echo $cat->id}"
                        type="button"
                        class="btn btn-small my_btn_s btn-primary expandButton cat{echo $cat->id}"
                        data-rel="tooltip"
                        data-placement="top"
                        data-original-title="{lang('expand the category','admin')}"
                        onclick="ajaxLoadChildCategoryBrain(this,{echo $cat->id})">
                    <i class="my_icon icon-plus"></i>
                </button>
            {else:}
                <span class="folder-icons"></span>
            {/if}
            <a href="{$ADMIN_URL}categories/edit/{echo $cat->id}" class="" data-rel="tooltip" data-placement="top"
               data-original-title="{lang('Edit category','admin')}">{echo $cat->name}</a>
        </div>
    </div>
    <div style="width: 15%;line-height: 24px;">{echo $cat->parent}</div>


    <div style="width: 8%;line-height: 24px;">
        <div class="frame_prod-on_off" data-rel="tooltip" data-placement="top"
             data-original-title="{lang('show','admin')}">
            <span class="prod-on_off cat_change_active prop_active{if !$cat->active} disable_tovar{/if}"
                  data-id="{echo $cat->id}"></span>
        </div>
    </div>

    <div style="width: 8%;">
        <div class="frame_prod-on_off" data-rel="tooltip" data-placement="top"
             data-original-title="{lang('show','admin')}">
            <span class="prod-on_off cat_change_showinsite {if !$cat->show_in_menu} disable_tovar{/if}"
                  data-id="{echo $cat->id}"></span>
        </div>
    </div>


    <div class="control-group" style="width: 12%;">
        <div>
            { $price_types = ['def'=>'По умолчанию', 'rrc'=>'Рекомендованная цена', 'retail_price_uah'=>'Розничная цена
            Брейн']}

            <select data-pricetype-select data-category-id="{echo $cat->id}" name="price_type[{echo $cat->id}]"
                    id="price_type[{echo $cat->id}]" class="notchosen">
                {/*}
                <option value="rrc" {if  $cat->price_type=='0'} selected="selected" {/if} >По умолчанию</option>
                { */}

                {foreach $price_types as $key => $name}
                    <option value="{echo $key}" {if $cat->price_type==$key} selected="selected" {/if} >{echo $name}</option>
                {/foreach}
            </select>
        </div>
    </div>


    <div class="control-group" style="width: 7%;">
        <div class="p_r o_h frame_price number">
            <input type="text"
                   name="cat_percent[{echo $cat->id}]"
                   value="{echo preg_replace('/\.?0*$/','',number_format($cat->cat_percent, 2, ".", ""))}"
                   class="js_price"
                   id="refresh_percent{echo $cat->id}"
                   onkeyup="checkLenghtStr('refresh_percent{echo $cat->id}', 11, 5, event.keyCode);"
                   data-value="{echo number_format($cat->cat_percent, 2, ".", "")}">

            <button class="btn btn-small refresh_percent"
                    cat-id="{echo $cat->id}"
                    type="button">
                <i class="icon-refresh"></i>
            </button>
        </div>
    </div>


    <div class="control-group" style="width: 10%;">


        <select data-cat_brand-select data-category-id="{echo $cat->id}" name="cat_brand[{echo $cat->id}]"
                id="cat_brand[{echo $cat->id}]" class="notchosen">
            <option value="0" {if $cat->cat_brand_rrc== '0'} selected="selected" {/if} >Нет</option>
            {foreach $all_brands as $key_br => $name_br}
                <option value="{echo $name_br->id}" {if $cat->cat_brand_rrc==$name_br->id} selected="selected" {/if} >{echo $name_br->name}</option>
            {/foreach}
        </select>

    </div>


</div>
