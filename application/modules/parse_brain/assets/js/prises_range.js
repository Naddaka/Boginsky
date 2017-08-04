$(document).ready(function () {

    $('.niceCheck').on('click', function () {
        if ($(this).find('.wraper_activSettings').attr('checked')) {
            $('.wraperControlGroup').slideUp(500);
        } else {
            $('.wraperControlGroup').slideDown(500);
        }
    });

    $('body').on('click', 'table.variablesTable .editVariable', function () {
        var editor = $(this).closest('tr').find('div.variable');
        var editValue = $.trim(editor.text());
        editor.empty();
        editor.parent().find('.variableEdit').css('display', 'block').val(editValue);

        var editor1 = $(this).closest('tr').find('div.variableValue');
        var editvariableValue = $.trim(editor1.text());
        editor1.empty();
        editor1.parent().find('.variableValueEdit').css('display', 'block').val(editvariableValue);

        var editor2 = $(this).closest('tr').find('div.rangeValue');
        var editrangeValue = $.trim(editor2.text());
        editor2.empty();
        editor2.parent().find('.rangeValueEdit').css('display', 'block').val(editrangeValue);


        $(this).css('display', 'none');
        $(this).closest('tr').find('.refreshVariable').css('display', 'block');
    });


    $('body').on('click', '.addVariable', function () {
        $('.addVariableContainer').show();
        $(this).hide();
    });

    //$(this).closest('tr').find('span.prod-on_off').add($('[data-page="tovar"]')).off('click').on('click', function () {
    $('body').on('click', 'table.variablesTable .prod-on_off', function () {
        var page_id = $(this).closest('tr').find('span.prod-on_off').attr('data-id');
        $.ajax({
            type: 'POST',
            data: {
                provider_id: page_id
                //variable: variable
            },
            url: base_url + '/admin/components/cp/parse_brain/changeRangeActive/',
            onComplete: function (response) {
            }
        });
        if ($(this).closest('tr').find('span.prod-on_off').attr('data-active') === 0) {
            $(this).closest('tr').find('span.prod-on_off').removeClass('disable_tovar').css('right', '28px');
        }
        if ($(this).closest('tr').find('span.prod-on_off').attr('data-active') === 1) {
            $(this).closest('tr').find('span.prod-on_off').addClass('disable_tovar').css('left', '-28px');
        }

        //$('.prod-on_off').addClass('disable_tovar').css('left', '-28px');
    });
});


var Range = {
    insertVariable: function (curElem) {
        var activeEditor = tinyMCE.activeEditor.contentAreaContainer;
        var curEditor = $(curElem).closest('.control-group').find('div[id*="tinymce"].mce-edit-area');

        if ($(activeEditor).is(curEditor)) {
            tinyMCE.execCommand("mceInsertContent", false, ' ' + $(curElem).val() + ' ');
        }
    },
    delete: function (diapazon_id, curElement) {
        $.ajax({
            type: 'POST',
            data: {
                diapazon_id: diapazon_id
                //variable: variable
            },
            url: '/admin/components/cp/parse_brain/deleteRangeVariable/' + locale,
            success: function (data) {
                if (!data) {
                    showMessage(lang('Error'), lang('Variable is not removed'), 'r');
                    return false;
                }
                curElement.closest('tr').remove();
                showMessage(lang('Message'), lang('Variable successfully removed'));
            }
        });
    },
    update: function (curElement, diapazon_id) {
        var closestTr = curElement.closest('tr');
        var variable = closestTr.find('.variableEdit');
        var variableValue = closestTr.find('.variableValueEdit');
        var rangeValue = closestTr.find('.rangeValueEdit');

        //this.validateVariable(variable.val());

        $.ajax({
            type: 'POST',
            data: {
                variable: $.trim(variable.val()),
                variableValue: $.trim(variableValue.val()),
                rangeValue: $.trim(rangeValue.val()),
                diapazon_id: diapazon_id
//                template_id: template_id
            },
            url: '/admin/components/cp/parse_brain/updateOneRange/',
            success: function (data) {
                if (!data) {
                    showMessage(lang('Error'), lang('Variable is not updated'), 'r');
                    return false;
                }
                if (data === false) {
                    showMessage(lang('Error'), lang('Variable is not added'), 'r');
                    return false;
                }
                closestTr.find('.variable').text(variable.val());
                closestTr.find('.variableValue').text(variableValue.val());
                closestTr.find('.rangeValue').text(rangeValue.val());
                variable.css('display', 'none');
                variableValue.css('display', 'none');
                rangeValue.css('display', 'none');
                closestTr.find('.editVariable').css('display', 'block');
                closestTr.find('.refreshVariable').css('display', 'none');
                showMessage(lang('Message'), lang('Variable successfully updated'));
            }
        });
//        setTimeout(function () {
//            window.location.reload()
//        }, '300');
    },
    add: function (curElement, id) {
        var variable = curElement.closest('tr').find('.newVariableEdit');
        var variableValue = curElement.closest('tr').find('.newVariableValueEdit');
        var variableRange = curElement.closest('tr').find('.newRangeValueEdit');

        //this.validateVariable(variable.val());

        $.ajax({
            type: 'POST',
            data: {
                diapazon_id: id,
                variable: $.trim(variable.val()),
                variableValue: $.trim(variableValue.val()),
                variableRange: $.trim(variableRange.val())
            },
            url: '/admin/components/cp/parse_brain/addDiapazon/',
            success: function (data) {
                if (!data) {
                    showMessage(lang('Error'), lang('Variable is not added'), 'r');
                    return false;
                }
                if (data === false) {
                    showMessage(lang('Error'), lang('Variable is not added'), 'r');
                    return false;
                }
                //curElement.parent('div').find('.typeVariable').val('');
                //$('.addVariableContainer').css('display', 'none');
                //$('.addVariableContainer').find('input').val('');
                //$('.addVariable').show();
                //$(data).insertBefore('table.variablesTable .addVariableContainer');


                showMessage(lang('Message'), lang('Variable successfully added'));
                setTimeout(function () {
                    window.location.reload()
                }, '300');
            }
        });
    },
    updateVariablesList: function (curElement, template_id, locale) {
        if (!curElement.hasClass('active')) {
            $.ajax({
                type: 'POST',
                data: {
                    template_id: template_id
                },
                url: '/admin/components/cp/parse_brain/getTemplateVariables/' + locale,
                success: function (data) {
                    $('#userMailVariables').html(data);
                    $('#adminMailVariables').html(data);
                }
            });
        }
    },
    validateVariable: function (variable) {
        var variable = $.trim(variable);
        var variableValue = $.trim(variableValue);

        if (!variable) {
            showMessage(lang('Error'), lang('Enter variable'), 'r');
            exit;
        }

        if (!variable) {
            showMessage(lang('Error'), lang('Variable must have a value'), 'r');
            exit;
        }
    }
};
var range_function = new Object({
    rangeFunction: function () {
        $('.modal_send').modal();
    },
    rangeFunctionConfirm: function (href) {
        var ids = [];
        $('input[name=ids]:checked').each(function () {
            ids.push($(this).val());
        });
        // alert(ids);
        $.ajax({
            type: 'post', //тип запроса: get,post либо head
            url: href,
            data: {ids: ids},
            dataType: "html",
            success: function (respsdek) {
                $('#mainContent').after(respsdek);
                //alert(JSON.parse(respsdek));
                answers = JSON.parse(respsdek);
//                for (j = 0; j < answers.length; j++) {
//                    a = JSON.parse(answers[j]);
//                    alert(a[0]);
//                    if (a[1] == 'r') {
                showMessage(lang('Message'), answers, 'g');
//                    }
//                    if (a[1] == 'g') {
//                        showMessage(lang('Message'), a[0], a[1]);
//                    }
//                }
                $.pjax({
                    url: window.location.pathname,
                    container: '#mainContent'
                });
                $('.modal_send').modal('hide');
                //showMessage(lang('Message'),a[0] + a[1], a[2]);
                location.reload();
                return true;
            }
        });

    }
});
