<?php
header('Content-Type: text/css');

require_once 'css/default.css';
require_once 'config.php';

echo "\n";
foreach ($aConfig['colors'] as $sType => $aColor) {
    printf(<<<CSS
tbody td.type-%s {
    background: %s;
}

CSS
    , $sType, $aColor[1]);
}
exit;