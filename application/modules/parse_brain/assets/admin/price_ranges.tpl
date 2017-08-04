<div class="container">
    <div class="modal hide fade modal_send">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3>{lang('Установления наценки по диапазонах цен ', 'parse_brain')}</h3>
        </div>
        <div class="modal-body">
            <p>{lang('Установить наценки', 'parse_brain')}?</p>
        </div>
        <div class="modal-footer">
            <a href="#" class="btn btn-primary"
               onclick="range_function.rangeFunctionConfirm('/admin/components/cp/parse_brain/confirm_range_price/')">{lang('Применить', 'edostavka')}</a>
            <a href="#" class="btn" onclick="$('.modal').modal('hide');">{lang('Cancel', 'parse_brain')}</a>
        </div>

    </div>

    <section class="mini-layout">
        <div class="frame_title clearfix">
            <div class="pull-left">
                <span class="help-inline"></span>
                <span class="title">{lang('Настройки диапазоном для изминения цен', 'parse_brain')}</span>
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
                <a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/cat_prices">
                    <i class="icon-wrench"></i>
                    {lang('Категории и тип цен, применяимые к ней ', 'parse_brain')}
                </a>
                <a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/prices_ranges">
                    <i class="icon-wrench"></i>
                    {lang('Настройки цен по диапазонам ', 'parse_brain')}
                </a>
                <a class="btn btn-small " href="{$BASE_URL}admin/components/cp/parse_brain/getParsing_types">
                    <i class="icon-wrench"></i>
                    {lang('Начать загрузку ', 'parse_brain')}
                </a>
                <button type="button"
                        class="btn btn-small btn-danger disabled action_on"
                        onclick="range_function.rangeFunction()"
                        id="del_sel_property">
                    <i class="icon-refresh"></i>{lang('Применить наценку', 'parse_brain')}
                </button>
            </div>
        </div>
        <div class="row-fluid">

            <div class="tab-content">

                <div id="variables">
                    <div class="inside_padd">
                        <table class="table  table-bordered table-hover table-condensed variablesTable t-l_a">
                            <thead>
                            <th class="t-a_c span1">
                                <span class="frame_label">
                                    <span class="niceCheck b_n">
                                        <input type="checkbox"/>
                                    </span>
                                </span>
                            </th>
                            <th>{lang('Начало диапазона', 'parse_brain')}</th>
                            <th>{lang('Конец диапазона', 'parse_brain')}</th>
                            <th>{lang('Процент наценки', 'parse_brain')}</th>
                            {/*}
                            <th>{lang('Активен', 'parse_brain')}</th>
                            { */}
                            <th>{lang('Edit', 'parse_brain')}</th>
                            <th>{lang('Delete', 'parse_brain')}</th>
                            </thead>
                            {/*} {foreach $shop_brands as $shop_brand}
                                {var_dumps($shop_brand->currentTranslations[\MY_Controller::getCurrentLocale()]->name)}
                                {dd($shop_brand->id)}
                            {/foreach}{ */}
                            {foreach $prises_range as  $key=>$diapazon}
                                <tr>
                                    <td class="t-a_c">
                                        <span class="frame_label">
                                            <span class="niceCheck b_n">
                                                <input type="checkbox" name="ids" value="{echo $key}"/>
                                            </span>
                                        </span>

                                    </td>
                                    <td class="span5">
                                        <div class="variable" id="variable">
                                            {echo $diapazon['begin']}
                                        </div>
                                        <input type="number" name="variableEdit" class="variableEdit"
                                               style="display: none" required="required"/>
                                    </td>
                                    <td class="span5">
                                        <div class="variableValue" id="variableValue">
                                            {echo $diapazon['end']}
                                        </div>
                                        <input type="number" name="variableValueEdit" class="variableValueEdit"
                                               style="display: none" required="required"/>

                                    </td>
                                    <td class="span5">
                                        <div class="rangeValue" id="rangeValue">
                                            {echo $diapazon['percent']}
                                        </div>
                                        <input type="number" name="rangeValueEdit" class="rangeValueEdit"
                                               style="display: none" required="required"/>
                                    </td>
                                    {/*}
                                    <td>
                                        <div class="frame_prod-on_off" data-rel="tooltip" data-placement="top"
                                             data-original-title="показать">
                                            {if $diapazon['active'] == 1}
                                                <span class="prod-on_off" data-id="{echo $key}" data-active="1"></span>
                                            {else:}
                                                <span class="prod-on_off disable_tovar" data-id="{echo $key}"
                                                      data-active="0"></span>
                                            {/if}
                                        </div>
                                    </td>
                                    { */}
                                    <td style="width: 100px">
                                        <button class="btn my_btn_s btn-small editVariable" type="button">
                                            <i class="icon-edit"></i>
                                        </button>
                                        <button data-update="count" onclick="Range.update($(this), '{echo $key}')"
                                                class="btn btn-small refreshVariable my_btn_s" type="button"
                                                style="display: none;">
                                            <i class="icon-ok"></i>
                                        </button>
                                    </td>
                                    <td class="span1">
                                        <button class="btn my_btn_s btn-small btn-danger " type="button"
                                                onclick="Range.delete({echo $key}, $(this))">
                                            <i class="icon-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            {/foreach}
                            <tr class="addVariableContainer" style="display: none">
                                <td class="t-a_c"></td>
                                <td class="span1">
                                    <input type="number" name="newVariableEdit" class="newVariableEdit"
                                           required="required"/>
                                </td>
                                <td class="span4">
                                    <input type="number" name="newVariableValueEdit" class="newVariableValueEdit"
                                           required="required"/>
                                </td>
                                <td class="span4">
                                    <input type="number" name="newRangeValueEdit" class="newRangeValueEdit"
                                           required="required"/> %
                                </td>

                                <td class=""></td>
                                <td style="width: 100px" colspan="2">
                                    <button data-update="count" onclick="Range.add($(this), {echo $key+1});"
                                            data-variable="" class="btn btn-small" type="button"
                                            style="display: block; margin-top: 4px;margin-left: 4px">
                                        <i class="icon-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        </table>
                        <button class="btn btn-small btn-success addVariable">
                            <i class="icon-plus icon-white"></i>&nbsp;{lang('Add new diapazon', 'parse_brain')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>