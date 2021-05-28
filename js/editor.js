/**
 *
 * (c) Copyright Hancom Inc
 *
 */

(function ($, OCA) {

    OCA.HancomOffice = $.extend({
        AppName: "hancomoffice"
    }, OCA.HancomOffice);

    function getIframeUrl(callback) {
        var mode = "EDITOR";
        var $iframeEditor = $("#iframeEditor");
        var fileId = $iframeEditor.data("id");
        var filePath = $iframeEditor.data("path");
        var userId = $iframeEditor.data("user-id");
        var shareToken = $iframeEditor.data("share-token");
        var lang = $iframeEditor.data("lang");

        if (!fileId) {
            OCP.Toast.error(t(OCA.HancomOffice.AppName, "Error opening file"), {timeout: -1});
            callback();
            return;
        }

        OCA.HancomOffice.getApp(filePath, function(app) {
            if (app) {
                OCA.HancomOffice.app = app;

                var urlParams = [
                    ["app", app + "_" + mode],
                    ["lang", lang],
                    ["fid", fileId],
                    ["docId", fileId],
                    ["user_id", userId],
                    ["share_token", shareToken],
                    ["host", location.href.replace(/apps\/hancomoffice\/.*/, "apps/hancomoffice/callback")]
                ].map(function(keyvalue) {
                    return keyvalue.join("=");
                }).join("&");
                var hostUrl = OCA.HancomOffice.SETTINGS.WEBOFFICE_HOST + "cloud-office/api/fw";
                var officeUrl = hostUrl + filePath + "/open?" + urlParams;

                callback(officeUrl);
            } else {
                callback();
            }
        });
    }

    OCA.HancomOffice.InitEditor = function () {
        var $iframeEditor = $("#iframeEditor");
        getIframeUrl(function(url) {
            if (url) {
                var iframe = $('<iframe src="' + url + '" id="hancomIframe" width="100%" height="100%"></iframe>');
                $iframeEditor.append(iframe);
            } else {
                OCP.Toast.error(t(OCA.HancomOffice.AppName, "Error opening file"), {timeout: -1});
            }
        });
    };

    $(document).ready(OCA.HancomOffice.InitEditor);

})(jQuery, OCA);
