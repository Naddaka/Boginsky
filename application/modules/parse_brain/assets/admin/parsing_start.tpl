<div class="container">
    <section class="mini-layout">
        <div class="frame_title clearfix">
            <div class="pull-left">
                <span class="help-inline"></span>
                <span class="title">{lang('Управления процессом загрузки', 'parse_yandex_xml')}</span>
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


                </div>
            </div>
        </div>


        <div class="tab-pane active" id="parse_yandex_xml">
            <div class="span12" style="margin-left: 0px">


                <div class="span12" style="margin-left:  0px">
                    <table class="table  table-bordered table-hover table-condensed content_big_td">


                        <thead>
                        <tr>
                            <a href="{base_url()}admin/backup#backup_create" style="margin-left:15px;"><i
                                        class="icon-plus-sign"></i> {lang(' Перед началом парсинга, необходимо сделать бек-ап базы данных','parse_yandex_xml')}
                            </a>
                            <th colspan="4">
                                {lang(' Создание новых категорий','parse_yandex_xml')}
                            </th>
                            <th colspan="4">
                                {lang('Создание новых товаров c выбранных категорий','parse_yandex_xml')}
                            </th>


                        </tr>
                        </thead>
                        <tr>
                            <td colspan="4">


                                <div class="inside_padd parse_yandex_xml__frame">

                                    <a href="#" id="runScript" class="btn btn-primary pjax" data-action="run"
                                       onclick="cats_create('0')">Старт</a>

                                    <div class="form">

                                        <input id="offset_cat" name="offset_cat" type="hidden">
                                        <div class="action_name_cat" style="display: none;"></div>
                                        <div class="action_prop_cat" style="display: none;"></div>
                                        <div class="action_fill_prop_cat" style="display: none;"></div>
                                        <div class="action_finish_cat" style="display: none;"></div>


                                        <div class="progress_cat" style="display: none; background-color: lightgrey">
                                            <div class="bar_cat"></div>
                                        </div>


                                        {/*}<a href="#" id="refreshScript" class="btns"
                                               style="display: none;">Заново</a>{ */}
                                    </div>
                                </div>

                            </td>
                            <td colspan="4">
                                <div class="inside_padd parse_yandex_xml__frame">
                                    { $all_cats_to_parse_prod = count($settings[categories_to_parce])}
                                    <a href="#" id="runScript" class="btn btn-primary pjax" data-action="run"
                                       onclick="prods_create({echo $all_cats_to_parse_prod}, '0', '0')">Старт</a>

                                    <div class="form">

                                        <input id="offset_prod" name="offset_prod" type="hidden">
                                        <div class="action_name_prod" style="display: none;"></div>
                                        <div class="action_prop_prod" style="display: none;"></div>
                                        <div class="action_fill_prop_prod" style="display: none;"></div>
                                        <div class="action_finish_prod" style="display: none;"></div>


                                        <div class="progress_prod" style="display: none; background-color: lightgrey">
                                            <div class="bar_prod"></div>
                                        </div>
                                        <br/>
                                        <div class="progress_prods_cr"
                                             style="display: none; background-color: lightgrey">
                                            <div class="bar_prods_cr"></div>
                                        </div>


                                        {/*}<a href="#" id="refreshScript" class="btns"
                                               style="display: none;">Заново</a>{ */}
                                    </div>
                                </div>
                            </td>

                        </tr>

                        </tbody>
                    </table>
                </div>


                {/*}
                <div class="form">

                    <input id="offset" name="offset" type="hidden">
                    <div class="action_name" style="display: none;"></div>
                    <div class="action_prop" style="display: none;"></div>
                    <div class="action_fill_prop" style="display: none;"></div>
                    <div class="action_finish" style="display: none;"></div>


                    <div class="progress" style="display: none; background-color: lightgrey">
                        <div class="bar"></div>
                    </div>


                </div>
                { */}


            </div>
        </div>
    </section>

</div>

