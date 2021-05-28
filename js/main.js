/**
 *
 * (c) Copyright Hancom Inc
 *
 */

(function(OCA) {

    OCA.HancomOffice = $.extend({
        AppName: "hancomoffice",
    }, OCA.HancomOffice);

    var SETTINGS = OCA.HancomOffice.SETTINGS = {
        APP_BY_EXT: {}
    };

    function getFileExtension(fileName) {
        var extension = fileName.substr(fileName.lastIndexOf(".") + 1).toLowerCase();
        return extension;
    }

    OCA.HancomOffice.getSettings = function(callback) {
        if (SETTINGS.FORMATS) {
            callback();
        } else {
            $.get(
                OC.generateUrl("apps/" + OCA.HancomOffice.AppName + "/ajax/settings"),
                function onSuccess(response) {
                    if (!SETTINGS.WEBOFFICE_HOST) {
                        SETTINGS.WEBOFFICE_HOST = response.webofficeHost;
                        SETTINGS.DOCSCONVERTER_HOST = response.docsconverterHost;
                        SETTINGS.INSTANCE_ID = response.instanceid;
                        SETTINGS.FORMATS = response.formats;
                        $.each(response.formats || {}, function(ext, config) {
                            var app = SETTINGS.APP_BY_EXT[config.type];
                            if (!app) {
                                app = SETTINGS.APP_BY_EXT[config.type] = [];
                            }
                            app.push(ext);
                        });

                        SETTINGS.APPS = response.apps || {};
                        // console.log(SETTINGS);
                    }

                    callback();
                }
            );
        }
    };

    OCA.HancomOffice.getApp = function(fileName, callback) {
        OCA.HancomOffice.getSettings(function() {
            var ext = getFileExtension(fileName);
            for (var appName in SETTINGS.APP_BY_EXT) {
                if (~SETTINGS.APP_BY_EXT[appName].indexOf(ext)) {
                    OCA.HancomOffice.app = appName;
                    callback(appName);
                    return;
                }
            }
            callback();
        });
    };

    function createFile(name, fileList) {
        var dir = fileList.getCurrentDirectory();

        if (OCA.HancomOffice.Desktop) {
            var winEditor = window.open("");
            if (winEditor) {
                winEditor.document.write(t(OCA.HancomOffice.AppName, "Loading, please wait."));
                winEditor.document.close();
            }
        }

        var createData = {
            name: name,
            dir: dir
        };

        $.post(OC.generateUrl("/apps/" + OCA.HancomOffice.AppName + "/ajax/new"),
            createData,
            function onSuccess(response) {
                if (response.error) {
                    if (winEditor) {
                        winEditor.close();
                    }
                    OCP.Toast.error(response.error);
                    return;
                }

                fileList.add(response, { animate: true });
                openEditorIframe(fileList, response.name, response.id, dir);
                // OCA.HancomOffice.OpenEditor(response.id, dir, response.name, winEditor);

                OCP.Toast.success(t(OCA.HancomOffice.AppName, "File created"));
            }
        );
    };

    var currentFilePath;
    var lastSidebarFilePath;
    var fileListFromContext;

    function getShareToken() {
        var sharingTokenNode = document.getElementById('sharingToken');
        return sharingTokenNode ? sharingTokenNode.value : '';
    }

    function openEditorIframe(fileList, fileName, fileId, fileDir)  {
        if (fileList) {
            var filePath  = fileDir.replace(/\/$/, '') + '/' + fileName;
            currentFilePath = filePath;

            fileList.setViewerMode(true);
            fileList.setPageTitle(fileName);
            fileList.showMask();

            var url = OC.generateUrl("/apps/{appName}/{fileId}?filePath={filePath}&shareToken={shareToken}&inframe=true",
                {
                    appName: OCA.HancomOffice.AppName,
                    fileId: fileId,
                    filePath: filePath,
                    shareToken: getShareToken()
                }
            );
            showEditDecoration();

            var $iframe = $('<iframe id="hancomIframe" width="100%" height="100%" nonce="' + btoa(OC.requestToken) + '" scrolling="no" allowfullscreen src="' + url + '" />');
            $('#app-content').append($iframe);
        }
    }

    function openFileClick(fileName, context) {
        // var fileInfoModel = context.fileInfoModel || context.fileList.getModelForFile(fileName);

        fileListFromContext = context && context.fileList;
        if (fileListFromContext) {
			openEditorIframe(fileListFromContext, fileName, context.fileId || context.$file.attr('data-id'), context.dir);
		}
    };

    function getFileList() {
        if (fileListFromContext) {
            return fileListFromContext;
        }
		if (OCA.Files && OCA.Files.App) {
			return OCA.Files.App.fileList;
		}
		if (OCA.Sharing && OCA.Sharing.PublicApp) {
			return OCA.Sharing.PublicApp.fileList;
		}
		return null;
    }

    function toggleShare() {
        var fileList = getFileList();
        if (!$('#app-sidebar').is(':visible') && fileList && currentFilePath) {
            if (lastSidebarFilePath !== currentFilePath || !OCA.Files.Sidebar.file) {
                lastSidebarFilePath = currentFilePath;
                OCA.Files.Sidebar.open(currentFilePath);
            } else {
                OC.Apps.showAppSidebar();
            }
        } else {
            OC.Apps.hideAppSidebar();
        }
    }

    var closeTimer;
    var CLOSE_TIMEOUT = 1000;
    function sendCloseEditor() {
        var iframe = $('#hancomIframe').get(0);
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.postMessage("closeApp", "*");
            closeTimer = setTimeout(closeEditor, CLOSE_TIMEOUT);
        } else {
            closeEditor();
        }
    }

    function closeEditor() {
        clearTimeout(closeTimer);
        var fileList = getFileList();
        if (fileList) {
            fileList.setViewerMode(false);
            fileList.reload();
        }

        hideEditDecoration();
        currentFilePath = undefined;
    }

    function showEditDecoration() {
        $('body').css('overflow', 'hidden');
        $('#app-content #controls').hide();

        $('.header-right').hide();
        var $header = $('<div class="header-right" id="header-hancom"></div>');
        var inverted = OCA.Theming && OCA.Theming.inverted;

        var $shareButton = $('<div id="hancom-share"><div class="icon-share ' + (inverted ? '' : 'icon-white') + ' menutoggle" tabindex="0" role="button"><span class="hidden-visually">Share file</span></div></div>');
        $shareButton.on('click', toggleShare);
        $header.append($shareButton);

        var $closeButton = $('<div id="hancom-close"><div class="icon-close ' + (inverted ? '' : 'icon-white') + ' menutoggle" tabindex="0" role="button"><span class="hidden-visually">Close Hancom Office Online</span></div></div>');
        $closeButton.on('click', sendCloseEditor);
        $header.append($closeButton);

        $('#header').append($header);
    }

    function hideEditDecoration() {
        $('#header-hancom').remove();
        $('#hancomIframe').remove();
        $('.header-right').show();
        $('#app-content #controls').show();
        $('body').css('overflow', '');
    }

    window.addEventListener("message", function(event) {
        if (event.data === "hancomEditorClosed") {
            closeEditor();
        }
    });

    OCA.HancomOffice.FileList = {
        attach: function(fileList) {
            if (fileList.id == "trashbin") {
                return;
            }

            OCA.HancomOffice.getSettings(function() {
                $.each(OCA.HancomOffice.SETTINGS.FORMATS, function(_ext, config) {
                    // console.log(config);
                    fileList.fileActions.registerAction({
                        name: "hancomofficeOpen",
                        displayName: t(OCA.HancomOffice.AppName, "Edit in Hancom Office"),
                        mime: config.mime,
                        permissions: OC.PERMISSION_UPDATE,
                        iconClass: "icon-hancomoffice-open",
                        actionHandler: openFileClick
                    });

                    if (!OCA.Viewer || !SETTINGS.DOCSCONVERTER_HOST) {
                        fileList.fileActions.setDefault(config.mime, "hancomofficeOpen");
                    }
                });
            });

            initViewer();
        }
    };

    OCA.HancomOffice.NewFileMenu = {
        attach: function(menu) {
            var fileList = menu.fileList;

            if (fileList.id !== "files" && fileList.id !== "files.public") {
                return;
            }

            menu.addMenuEntry({
                id: "hancomofficeDocx",
                displayName: t(OCA.HancomOffice.AppName, "New document"),
                templateName: t(OCA.HancomOffice.AppName, "New document"),
                iconClass: "icon-hancomoffice-new-docx",
                fileType: "docx",
                actionHandler: function(name) {
                    createFile(name + ".docx", fileList);
                }
            });

            menu.addMenuEntry({
                id: "hancomofficeXlsx",
                displayName: t(OCA.HancomOffice.AppName, "New spreadsheet"),
                templateName: t(OCA.HancomOffice.AppName, "New spreadsheet"),
                iconClass: "icon-hancomoffice-new-xlsx",
                fileType: "xlsx",
                actionHandler: function(name) {
                    createFile(name + ".xlsx", fileList);
                }
            });

            menu.addMenuEntry({
                id: "hancomofficePpts",
                displayName: t(OCA.HancomOffice.AppName, "New presentation"),
                templateName: t(OCA.HancomOffice.AppName, "New presentation"),
                iconClass: "icon-hancomoffice-new-pptx",
                fileType: "pptx",
                actionHandler: function(name) {
                    createFile(name + ".pptx", fileList);
                }
            });
        }
    };

    var Viewer = {
        name: "HancomOfficeViewer",
        render: function(createElement) {
            var self = this;
            if (!self.active) {
                return null;
            }
            return createElement("iframe", {
                attrs: {
                    id: "hancomofficePreview",
                    scrolling: "no",
                    src: self.url,
                },
                on: {
                    load: function() {
                        self.doneLoading();
                    },
                },
            })
        },
        props: {
            active: {
                type: Boolean,
                default: false,
            },
            filename: {
                type: String,
                default: null
            },
            fileid: {
                type: Number,
                default: null
            }
        },
        data: function() {
            return {
                url: OC.generateUrl("/apps/{appName}/preview/{fileId}?filePath={filePath}&inframe=true&shareToken={shareToken}",
                    {
                        appName: OCA.HancomOffice.AppName,
                        fileId: this.fileid,
                        filePath: this.filename,
                        shareToken: getShareToken()
                    })
            }
        }
    };

    function initViewer() {
        if (OCA.Viewer) {
            var handlers = OCA.Viewer.availableHandlers;
            var isExist = handlers.filter(function(handler) {
                return handler.id === OCA.HancomOffice.AppName;
            }).length > 0;
            if (!isExist) {
                OCA.HancomOffice.getSettings(function() {
                    if (SETTINGS.DOCSCONVERTER_HOST) {
                        var mimes = $.map(SETTINGS.FORMATS, function(config) {
                            return config.mime;
                        });
                        OCA.Viewer.registerHandler({
                            id: OCA.HancomOffice.AppName,
                            group: null,
                            mimes: mimes,
                            component: Viewer
                        })
                    }
                });
            }
        }
    }

    var initPage = function() {
        OC.Plugins.register("OCA.Files.FileList", OCA.HancomOffice.FileList);
        OC.Plugins.register("OCA.Files.NewFileMenu", OCA.HancomOffice.NewFileMenu);

        initViewer();
    };

    $(document).ready(initPage);

})(OCA);
