/**
 *
 * (c) Copyright Hancom Inc
 *
 */

(function ($, OC) {

    $(document).ready(function () {
        OCA.HancomOffice = $.extend({}, OCA.HancomOffice);
        if (!OCA.HancomOffice.AppName) {
            OCA.HancomOffice = {
                AppName: "hancomoffice"
            };
        }

        function updateInputs() {
            var $inputs = $('.section-hancomoffice input[type="radio"]');
            var $selectedInput = $('input[type="radio"]:checked').get(0);
            $.each($inputs, function(index, $input) {
                if ($input !== $selectedInput) {
                    $($input)
                        .parent()
                        .find('input:not([type="radio"]), select')
                        .attr('disabled', true);
                } else {
                    $($input)
                        .parent()
                        .find('input:not([type="radio"]), select')
                        .attr('disabled', false);
                }
            });
        }

        updateInputs();

        $('.section-hancomoffice input[type="radio"]').click(function () {
            updateInputs();
        });

        $("#hancomofficeAddrSave").click(function () {
            $(".section-hancomoffice").addClass("icon-loading");

            var data = {
                docsconverter: $("#docsconverterUrl").val().trim()
            };
            var $selectedInput = $('input[type="radio"]:checked').get(0);
            switch ($selectedInput.value) {
                case 'demo':
                    data.type = 'demo';
                    data.demoserver = $("select#demo").val().trim();
                    delete data.documentserver;
                    break;
                case 'own':
                default:
                    data.type = 'own';
                    data.documentserver = $("#hancomofficeUrl").val().trim();
                    delete data.demoserver;
                    break;
            }

            $.ajax({
                method: "PUT",
                url: OC.generateUrl("apps/" + OCA.HancomOffice.AppName + "/ajax/settings/address"),
                data: data,
                success: function onSuccess(response) {
                    $(".section-hancomoffice").removeClass("icon-loading");
                    if (response && (response.documentserver != null)) {
                        if (response.error) {
                            OCP.Toast.error(t(OCA.HancomOffice.AppName, "Error when trying to connect") + " (" + response.error + ")");
                        } else {
                            OCP.Toast.success(t(OCA.HancomOffice.AppName, "Settings have been successfully updated"));
                        }
                    }
                }
            });
        });

        $(".section-hancomoffice-addr input").keypress(function (e) {
            var code = e.keyCode || e.which;
            if (code === 13) {
                $("#hancomofficeAddrSave").click();
            }
        });
    });

})(jQuery, OC);
