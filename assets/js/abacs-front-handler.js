$ = jQuery;

var form_id = abacs_params.form_id;
var ajax_url = abacs_params.ajax_url;

$(document).ready(function () {

    $(document.body).on('updated_checkout', function () {
        $("#"+form_id+"-payment-receipt").on('change', function () {
            var pointer = $(this)
            $(".receipt-error").remove();
            var section = pointer.closest('.wc-abacs-form');

            var file_data = pointer.prop('files')[0];

            receipt_upload(file_data, section);
        });


        $(".wc-abacs-remove-file").on('click', function (e) {
            e.preventDefault();
            var receipt_url = $('.abac_response').text();
            var pointer = $(this);
            var data = {
                'action': 'abacs_unlink_receipt_file',
                'receipt_url': receipt_url,
            };

            $.post(ajax_url, data, function (response) {

                $(".abac_response").text('');
                pointer.hide();
                $(".abacs-html").show();
                $("#abac_receipt_url").val('');
                $("#" + form_id + "-payment-receipt").val('');
            });

        });
    });



    function receipt_upload(file_data, section) {

        if (file_data) {
            $('.abac_response').text('');
            $(".wc-abacs-remove-file").hide();
            section.find('.fa').addClass('fa-spinner');
            var mine_type = file_data.type;
            var mine_type_data = mine_type.split('/');

            if (mine_type_data[0] == 'image' || mine_type == "application/pdf") {

                // calling the ajax to upload file to the upload directory
                var form_data = new FormData();

                form_data.append('receipt_file', file_data);
                $.ajax({
                    url: ajax_url + '?action=abacs_receipt_upload',
                    type: 'post',
                    data: form_data,
                    enctype: 'multipart/form-data',
                    processData: false,
                    contentType: false,
                    cache: false,
                    success: function (response) {
                        section.find('.fa').removeClass('fa-spinner');
                        $(".abacs-html").hide();
                        $("#abac_receipt_url").val(response);
                        $(".abac_response").append(response);
                        $(".wc-abacs-remove-file").show();
                    },
                });

            } else {
                $("#" + form_id + '-cc-form').append('<div class="text-danger receipt-error">Please upload image or pdf file...</div>');
                section.find('.fa').removeClass('fa-spinner');
                return false;
            }
        }
    }

});