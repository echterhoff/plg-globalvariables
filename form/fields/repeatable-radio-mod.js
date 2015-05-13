/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


String.prototype.matchAll = function (regexp) {
    var matches = [];
    this.replace(regexp, function () {
        var arr = ([]).slice.call(arguments, 0);
        var extras = arr.splice(-2);
        arr.index = extras[0];
        arr.input = extras[1];
        matches.push(arr);
    });
    return matches.length ? matches : null;
};

(function ($) {
    $(document).ready(function () {


        var radio_default = function (id) {
            var radio_elements = $('#' + id + '_table fieldset.radio');
            if (radio_elements.length && radio_elements.first().find('input[type="radio"]').length === 1) {
                var radio_input = radio_elements.find('input[type="radio"]');
//                console.log("CheckCheck", radio_input);
                radio_input.each(function () {
                    $(this).closest('td').addClass('default-switch');
                });
                radio_input.attr('name', radio_input.attr('name').replace(/\-\d+$/, ''));
                radio_input.addClass('row-skipping');
                if (!radio_elements.find('input[type="radio"]:checked').length) {
                    radio_input.first().prop('checked', true).trigger('change');
                }
                radio_elements.each(function () {
                    var input_element = $(this).find('input[type="radio"]');
                    var input_label = $(this).find('label[for="' + input_element.attr('id') + '"]');
                    if (input_element.val() == input_label.text()) {
                        input_label.empty().addClass('text-center');
                        input_element.css({'float': 'none'}).appendTo(input_label);
                    }
                });
            }

            $('select').off('change');
            $('select').on('change', function (e) {
                if ($(this).val()) {
                    var matches = $(this).attr('class').matchAll(/selection\-hides\-([^ \b]*)/g);
                    var select_element = $(this);
                    if (matches) {
                        $.each(matches, function (idx, match) {
                            if (typeof match !== 'undefined' && match && parseInt(select_element.val()) > 0) {
                                select_element.closest('tr').find('input[name^="' + match[1] + '"],select[name^="' + match[1] + '"]+div').hide();
                            } else if (typeof match !== 'undefined' && match) {
                                select_element.closest('tr').find('input[name^="' + match[1] + '"],select[name^="' + match[1] + '"]+div').show();
                            }
                        });
                    }
                }
            });

            $('input.row-skipping').off('change');
            $('input.row-skipping').on('change', function(e){
//                console.log("TTTT",e,$(this));

                var row_id = $(this).attr('id').match(/.*-(\d*)/);

//                console.log(row_id[1]);

                var matches = $(this).closest('fieldset').attr('class').matchAll(/selection\-hides\-([^ \b]*)/g);
                var select_element = $(this);
//                console.log(matches);
                if (matches) {
                    $.each(matches, function (idx, match) {
                        select_element.closest('table').find('input[name^="' + match[1] + '"],select[name^="' + match[1] + '"]+div').show();
                        select_element.closest('table').find('input[name^="' + match[1] +'-' + row_id[1] + '"],select[name^="' + match[1] + '-' + row_id[1] + '"]+div').hide();
//                        if (typeof match !== 'undefined' && match && parseInt(select_element.val()) > 0) {
//                            select_element.closest('tr').find('input[name^="' + match[1] + '"],select[name^="' + match[1] + '"]+div').hide();
//                        } else if (typeof match !== 'undefined' && match) {
//                            select_element.closest('tr').find('input[name^="' + match[1] + '"],select[name^="' + match[1] + '"]+div').show();
//                        }
                    });
                }

            });

            var table = $('#' + id + '_table');
//            console.log(table.html());
            table.find('tbody>tr:first>td').each(function (idx) {
                var input_type = $(this).find(':input').attr('type')
                if (input_type == 'checkbox' || input_type == 'radio') {
//                    console.log(idx, $(this).find(':input').attr('type'));
                    table.find('tr>:nth-child(' + (idx + 1) + ')').addClass('small-element');
                }
            });


        };

        var enable_repeated_row = function (enable_switch) {
            var row = enable_switch.first().closest('tr');

            if (row.parent().find('tr td.default-switch :input:not(:disabled)').length === 0) {
                row.find('.default-switch :input').first().prop('checked', true).trigger('change');
            }

            row.find(':input:not(.enable-switch)').prop('disabled', false);
            row.find('select~div').removeClass('disabled');
            row.find('select~div').prop('disabled', false);
            row.removeClass('disabled');

        }

        var disable_repeated_row = function (enable_switch) {
            var row = enable_switch.first().closest('tr');

            if (row.find('.default-switch :input:checked').length == 1) {
                row.parent().find('tr td.default-switch :input:not(:checked,:disabled)').prop('checked', true).trigger('change');
            }

            row.find(':input:not(.enable-switch)').prop('disabled', true);
            row.find('select~div').addClass('disabled');
            row.find('select~div').prop('disabled', true);
            row.addClass('disabled');
        }

        var toggle_repeated_row = function (enable_switch) {
            if (enable_switch.first().prop('checked')) {
                enable_repeated_row(enable_switch)
            } else {
                disable_repeated_row(enable_switch)
            }
        }

        $("input.form-field-repeatable").each(function () {
            var id = $(this).attr('id');

            $('#' + id).off('row-add');
            $('#' + id).on('row-add', function (e, on) {
                var row = $(on);
                var enable_switch = row.find('input.enable-switch[type="checkbox"]');
                if (enable_switch.length === 1) {
                    if (enable_switch.one().prop('checked')) {
                        enable_repeated_row(enable_switch)
                    } else {
                        disable_repeated_row(enable_switch)
                    }
                    enable_switch.one().on('click', function () {
                        toggle_repeated_row($(this));
                    });
                }
                radio_default(id);
                row.find('select').trigger('change');
                row.find('input[type="radio"]:checked').trigger('change');
            });
            $('#' + id).off('row-remove');
            $('#' + id).on('row-remove', function (e, on) {
                var row = $(on);
                var table = row.parent();
//                console.log("Row remove", row);
                //Wait for the cleanup to be done
                setTimeout(function(){
//                    console.log(table.find('tr td.default-switch :input:checked').length);
                    if (table.find('tr td.default-switch :input:checked').length === 0) {
//                        console.log(table.find('tr td.default-switch :input:not(:checked,:disabled)').first());
                        table.find('tr td.default-switch :input:not(:checked,:disabled)').first().prop('checked', true).trigger('change');
                    }
                },20);
            });

        });

        $("input.form-field-repeatable").on('weready', function (e) {


            var id = $(this).attr('id');

            var modal_dialog = $('#' + $(this).attr('id') + '_modal');
            var close_btn = modal_dialog.find('.close-modal');
            var save_btn = modal_dialog.find('.save-modal-data');
            close_btn.off();
            close_btn.on('click', function (e) {
//                e.preventDefault(),
//                modal_dialog.modal("hide");
                setTimeout(function () {
                    $("input.form-field-repeatable").trigger('weready');
                }, 100);
            });

            radio_default(id);

        });
    });
})(jQuery);
