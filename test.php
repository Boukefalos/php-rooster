<?php
require_once 'cache.php';
require_once 'template.php';

$oRooster = new Rooster;
$oCache = new Cache($oRooster);
$oCache->clean();

$iYear = 2011;
$iWeek = 36;
$iWeeks = $aConfig['weeks'];
$aGroups = array($aConfig['filters']['S'], $aConfig['filters']['F']);

$aWeeks = array();

try {
    $aGroups = $oCache->getGroups($aGroups);
    $oRooster->setWeek($iYear, $iWeek);
    $aData = array();
    for ($i = 0; $i < $iWeeks; ++$i) {
        $aWeeks[] = $oRooster->getWeek();
        foreach ($aGroups as $sGroup => $sName) {        
            try {
                $aData[$sName] = $oCache->getData($sGroup);
            } catch (Exception $e) {
                die(printf('<pre style="color: red"><strong>ERROR:</strong> %s</pre>', $e->getMessage()));
            }
        }
       $oRooster->nextWeek();
    }
} catch (Exception $e) {
    die(printf('<pre style="color: red"><strong>ERROR:</strong> %s</pre>', $e->getMessage()));
}

convertData($aData, $aTable, $aInfo);
echo Template::getWeeks($aTable, $aInfo, $aWeeks);


$oRooster = new Rooster;
$oCache = new Cache($oRooster);
$oCache->clean(true);

if (isset($_GET['group'])) {
    if (isset($aConfig['filters'][$_GET['group']])) {
        $aGroups = array_flip($oCache->getGroups(array($aConfig['filters'][$_GET['group']])));
        if (isset($aGroups[$_GET['group']])) {
            $iYear = isset($_GET['year']) ? $_GET['year'] : null;
            $iWeek = isset($_GET['week']) ? $_GET['week'] : null;
            try {
                die($oCache->getRooster($aGroups[$_GET['group']], $iYear, $iWeek));
            } catch (Exception $e) {
                die('Ongeldige week!');
            }
        }
    }
    die('Ongeldige groep!');
}

try {
    $aGroups = $oCache->getGroups(array($aConfig['filters']['S']));
    $oRooster->setWeek(2012, 10);
    $aData = array();
    for ($i = 0; $i < $aConfig['weeks']; ++$i) {
        foreach ($aGroups as $sGroup => $sName) {        
            try {
                $aData[$sName] = $oCache->getData($sGroup);
            } catch (Exception $e) {
                printf('<pre style="color: red"><strong>ERROR:</strong> %s</pre>', $e->getMessage());
            }
        }
       $oRooster->nextWeek();
    }
    file_put_contents('temp', serialize($aData));
} catch (Exception $e) {
    printf('<pre style="color: red"><strong>ERROR:</strong> %s</pre>', $e->getMessage());
}