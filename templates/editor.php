<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

    style("hancomoffice", "embed");
    script("hancomoffice", "main");
    script("hancomoffice", "listener");
    script("hancomoffice", "editor");
?>


<div id="iframeEditor"
    data-lang="<?php p($_["lang"]) ?>"
    data-id="<?php p($_["fileId"]) ?>"
    data-path="<?php p($_["filePath"]) ?>"
    data-user-id="<?php p($_["userId"]) ?>"
    data-share-token="<?php p($_["share-token"]) ?>"></div>
