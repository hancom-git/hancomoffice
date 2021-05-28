<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

    style("hancomoffice", "settings");
    script("hancomoffice", "settings");
    
?>
<div class="section section-hancomoffice section-hancomoffice-addr">

    <h2>
        Hancom Office Online
    </h2>
    <div>
        <p class="settings-hint"><?php p($l->t("Real-time collaborative editing in your web browser")) ?></p>
    </div>

    <div>
        <input type="radio" name="type" value="own" id="own" class="radio" <?php p($_["type"] === "own" ? "checked" : "") ?>>
        <label for="own"><?php p($l->t("Use your own server")) ?></label>
        <p class="option-inline">
            <em><?php p($l->t("Please enter the '<address>' for the server address below.")) ?></em>
        </p>
        <p class="option-inline">
            <input id="hancomofficeUrl" value="<?php p($_["documentserver"]) ?>" placeholder="https://<address>/" type="text">
        </p>
    </div>

    <div>
        <input type="radio" name="type" value="demo" id="demo" class="radio" <?php p($_["type"] === "demo" ? "checked" : "") ?>>
        <label for="demo"><?php p($l->t("Use a demo server")) ?></label>
        <p class="option-inline">
            <select id="demo">
                <?php foreach ($_["hosts"] as $name => $host) { ?>
                    <option value="<?php p($host) ?>" <?php p($_["demoserver"] === $host ? "selected" : "") ?>><?php p($name) ?></option>
                <?php } ?>
            </select>
        </p>
    </div>

    <hr />

    <div>
        <p class="settings-hint"><?php p($l->t("To enable preview, type the URL of your DocsConverter server.")) ?></p>
        <input id="docsconverterUrl" value="<?php p($_["docsconverter"]) ?>" placeholder="https://<address>/" type="text">
    </div>

    <div>
        <button id="hancomofficeAddrSave" class="button"><?php p($l->t("Save")) ?></button>
    </div>

</div>
