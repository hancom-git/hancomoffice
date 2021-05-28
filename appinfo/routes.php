<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

return [
    "routes" => [
        // editor
        ["name" => "editor#index", "url" => "/{fileId}", "verb" => "GET"],
        ["name" => "editor#create", "url" => "/ajax/new", "verb" => "POST"],

        // preview
        ["name" => "preview#index", "url" => "/preview/{fileId}", "verb" => "GET"],
        
        // api
        ["name" => "callback#info", "url" => "/callback/{path}/info", 'requirements' => array('path' => '.+'), "verb" => "GET"],
        ["name" => "callback#info_root", "url" => "/callback/info", "verb" => "GET"],

        ["name" => "callback#list", "url" => "/callback/{path}/list", 'requirements' => array('path' => '.+'), "verb" => "GET"],
        ["name" => "callback#list_root", "url" => "/callback/list", "verb" => "GET"],

        ["name" => "callback#get", "url" => "/callback/{path}/get", 'requirements' => array('path' => '.+'), "verb" => "GET"],

        ["name" => "callback#put", "url" => "/callback/{path}/put", 'requirements' => array('path' => '.+'), "verb" => "POST"],

        ["name" => "callback#lock", "url" => "/callback/{path}/lock", 'requirements' => array('path' => '.+'), "verb" => "POST"],
        ["name" => "callback#unlock", "url" => "/callback/{path}/unlock", 'requirements' => array('path' => '.+'), "verb" => "POST"],
        
        // settings
        ["name" => "settings#save_address", "url" => "/ajax/settings/address", "verb" => "PUT"],
        ["name" => "settings#get_settings", "url" => "/ajax/settings", "verb" => "GET"],
    ]
];