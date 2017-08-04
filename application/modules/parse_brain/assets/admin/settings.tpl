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
                    <button type="button"
                            class="btn btn-small btn-primary action_on formSubmit"
                            data-form="#wishlist_settings_form"
                            data-action="tomain">
                        <i class="icon-ok"></i>{lang('Save', 'parse_brain')}
                    </button>
                </div>
            </div>
        </div>

        <div class="row-fluid m-t_20">
            <form method="post" action="{site_url('admin/components/cp/parse_brain/save')}"
                  class="form-horizontal"
                  id="wishlist_settings_form">
                <div class="span6">
                    <table class="table table-striped table-bordered table-hover table-condensed t-l_a">
                        <thead>
                        <tr>
                            <th colspan="6">
                                {lang('Настройка соединения', 'parse_brain')}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="6">
                                <div class="inside_padd">


                                    <div class="control-group">
                                        <label class="control-label"
                                               for="settings[login]">{lang('Логин', 'parse_brain')}
                                            :</label>
                                        <div class="controls">
                                            <input name="settings[login]" id="settings[login]"
                                                   value="{$settings['login']}" type="text"/>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label"
                                               for="settings[password]">{lang('Пароль', 'parse_brain')}
                                            :</label>
                                        <div class="controls">
                                            <input name="settings[password]" id="settings[password]"
                                                   value="{$settings['password']}" type="text"/>
                                        </div>
                                    </div>

                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                {form_csrf()}
            </form>
        </div>
    </section>
</div>

