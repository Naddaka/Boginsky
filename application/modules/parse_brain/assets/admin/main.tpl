<div class="container">
    <section class="mini-layout">
        <div class="frame_title clearfix">
            <div class="pull-left">
                <span class="help-inline"></span>
                <span class="title">{lang('Управления выгрузки Брейн', 'parse_brain')}</span>
            </div>
            <div class="pull-right">
                <div class="d-i_b">
                    <span class="help-inline"></span>
                    <a href="{$BASE_URL}admin/components/modules_table" class="t-d_n m-r_15 ">
                        <span class="f-s_14">←</span>
                        <span class="t-d_u">{lang('Back', 'parse_brain')}</span>
                    </a>
                    <a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/settings">
                        <i class="icon-wrench"></i>
                        {lang('Настройки', 'parse_brain')}
                    </a>
                    <a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/cat_prices">
                        <i class="icon-wrench"></i>
                        {lang('Категории и тип цен, применяимые к ней ', 'parse_brain')}
                    </a>
                    {/*}<a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/prices_ranges">
                        <i class="icon-wrench"></i>
                        {lang('Настройки цен по диапазонам ', 'parse_brain')}
                    </a>{ */}
                    <a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/getParsing_types">
                        <i class="icon-wrench"></i>
                        {lang('Начать загрузку ', 'parse_brain')}
                    </a>

                </div>
            </div>
        </div>
    </section>

</div>
