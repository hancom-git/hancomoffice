/**
 *
 * (c) Copyright Hancom Inc
 *
 */

(function (OCA) {

    OCA.HancomOffice = $.extend({
        AppName: "hancomoffice",
        mode: null
    }, OCA.HancomOffice);

    var API_VERSION = "v1.0";
    var DATA_KEY = 'ThinkfreeWeboffice';

    var messageListeners = {
        result: {},
        event: {}
    };

    function registerListener(type, method, callback) {
        messageListeners[type][method] = callback;
    }

    function registerResultListener(method, callback) {
        return registerListener('result', method, callback);
    }

    function registerEventListener(method, callback) {
        return registerListener('event', method, callback);
    }

    function hexhash() {
        return Math.floor(Math.random() * 0x10000).toString(16);
    }

    function generateId(type) {
        return type + '_' + hexhash() + hexhash() + hexhash();
    }

    function parseJSON(data) {
        var result;
        try {
            result = JSON.parse(data);
        } catch(e) {
            result = data;
        }
        return result;
    }

    function createCommand(method, data) {
        var app = OCA.HancomOffice.SETTINGS.APPS[OCA.HancomOffice.app];
        if (!app) {
            return;
        }
        return {
            type: 'cmd',
            app: app.name,
            version: API_VERSION,
            method: method,
            id: generateId('cmd'),
            params: data.params || []
        };
    }

    function sendCommand(method, data) {
        OCA.HancomOffice.getSettings(function() {
            var message = {};
            message[DATA_KEY] = createCommand(method, data || {});

            var iframe = $('iframe').get(0);
            if (message[DATA_KEY] && iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage(JSON.stringify(message), '*');
            } else {
                console.log('ERROR SENDING COMMAND');
            }
        });
    };

    OCA.HancomOffice.closeApp = function() {
        sendCommand('App.close', {});
        delete OCA.HancomOffice.app;
    };

    OCA.HancomOffice.documentPrint = function() {
        sendCommand('Document.print', {});
    };

    OCA.HancomOffice.documentDownload = function() {
        sendCommand('Document.download', {});
    };

    OCA.HancomOffice.documentDownloadPdf = function() {
        sendCommand('Document.downloadPdf', {});
    };

    OCA.HancomOffice.documentGoToBookmark = function() {
        sendCommand('Document.goToBookmark', {});
    };

    // results

    registerResultListener("App.close", function(data) {
        // console.log("App.close", data);
    });

    registerResultListener("Document.print", function(data) {
        // console.log("Document.print", data);
    });

    registerResultListener("Document.download", function(data) {
        // console.log("Document.download", data);
    });

    registerResultListener("Document.downloadPdf", function(data) {
        // console.log("Document.downloadPdf", data);
    });

    registerResultListener("Document.goToBookmark", function(data) {
        // console.log("Document.goToBookmark", data);
    });

    // events

    registerEventListener("App.loaded", function(data) {
        // console.log("App.loaded", data);
    });

    registerEventListener("App.closed", function(data) {
        // console.log("App.closed", data);
        window.parent.postMessage("hancomEditorClosed", "*");
    });

    registerEventListener("Document.saved", function(data) {
        // console.log("Document.saved", data);
    });

    registerEventListener("Document.invalidated", function(data) {
        // console.log("Document.invalidated", data);
    });

    window.addEventListener("message", function(event) {
        if (event.data === "closeApp") {
            OCA.HancomOffice.closeApp();
            return;
        }
        if (event.source !== window) {
            var data = event.data ? parseJSON(event.data)[DATA_KEY] : undefined;
            var listeners = data && messageListeners[data.type];
            var listener = listeners ? listeners[data.method] : undefined;
            if (listener) {
                listener(data);
            }
        }
    }, false);

})(OCA);
