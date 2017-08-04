<section class="mini-layout">
    <div class="frame_title clearfix">
        <div class="pull-left">
            <span class="help-inline"></span>
            <span class="title">{lang('Product categories','admin')}</span>
            <label class="control-group control-group--vert-align">
                                                            <span class="span4">
                                                                <span class="popover_ref" data-original-title="">
                                                                    <i class="icon-info-sign"></i>
                                                                </span>
                                                                <div class="d_n">

                                                                    <blockquote>
                                                                       <p><strong>Parse</strong>.Отметить категории, в которых будут обновлятся/создаватся товары с Брейн.
                                                                           {/*}Если не отметьть ни одной категории, то будут обновляться товары со всех категорий{ */}
                                                                       </p>
                                                                    </blockquote>

                                                                    <blockquote>
                                                                        <p><strong>Price type</strong>. Тип цены, которая будет применена для товаров с данной категории.</p>
                                                                    </blockquote>


                                                                </div>
                                                            </span>
            </label>
        </div>
        <div class="pull-right">
            <span class="help-inline"></span>
            <a href="{$BASE_URL}admin/components/modules_table" class="t-d_n m-r_15 ">
                <span class="f-s_14">←</span>
                <span class="t-d_u">{lang('Back', 'parse_brain')}</span>
            </a>
            <a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/settings">
                <i class="icon-wrench"></i>
                {lang('Настройки', 'parse_brain')}
            </a>
            {/*}<a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/prices_ranges">
                <i class="icon-wrench"></i>
                {lang('Настройки цен по диапазонам ', 'parse_brain')}
            </a>{ */}
            <a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/getParsing_types">
                <i class="icon-wrench"></i>
                {lang('Начать загрузку ', 'parse_brain')}
            </a>

            {/*}
            <div class="pull-right">
                <button type="button"
                        class="btn btn-small btn-primary action_on formSubmit"
                        data-form="#wishlist_settings_form"
                        data-action="tomain">
                    <i class="icon-ok"></i>{lang('Save', 'parse_brain')}
                </button>
            </div>
            { */}
        </div>
    </div>

    <form method="post" action="{site_url('admin/components/cp/parse_brain/save_categiries_settings')}"
          class="form-horizontal" id="wishlist_settings_form">
        <div class="frame_level table table-hover table-condensed">
            <div id="category">
                <div class="row-category p_cat_row-category head"
                     {if !sizeof($tree) && !$_GET['fast_create']}style="display: none" {/if}>
                    <div style="width: 5%;">
                        {lang('Parse','admin')}
                    </div>
                    <div style="width: 5%;">{lang('Is BRAIN','admin')}</div>
                    <div style="width: 5%;">ID</div>
                    <div style="width: 20%;">{lang('Title','admin')}</div>
                    <div style="width: 15%;">{lang('Parent Category','admin')}</div>


                    <div style="width: 8%;">{lang('Active','admin')}</div>
                    <div style="width: 8%;">{lang('Menu','admin')}</div>
                    <div style="width: 12%;">{lang('Price type','admin')}</div>
                    <div style="width: 7%;">{/*}{lang('% наценки на розн. цену','admin')}{ */}
                        <span class="popover_ref" data-original-title="">
                        <i class="icon-info-sign"></i><p><strong>{lang('%','admin')}</strong></p>
                    </span>
                        <div class="d_n">
                            <blockquote>
                                <p><strong>{lang('% наценки на розн. цену','admin')}</strong></p>
                            </blockquote>
                        </div>
                    </div>
                    <div style="width: 10%;">{lang('RRC для бренда в категории','admin')}</div>
                </div>


                <div class="body_category">
                    {$htmlTree}
                </div>


            </div>
            </br>
            {if !sizeof($tree)}
                <div class="alert alert-info">
                    {lang('There are no categories at the site','admin')}
                </div>
            {/if}
        </div>
        <div class="clearfix">
        </div>


        {form_csrf()}
    </form>
</section>
