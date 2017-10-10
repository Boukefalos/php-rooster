<?php
require_once 'config.php';
require_once 'template.php';
require_once 'cache.php';
require_once 'vurooster.php';
require_once 'uvarooster.php';

$bForm = true;
if (isset($_POST['action']) && $_POST['action'] == 'view') {
    /* Weeks */
    $iWeeks = isset($_POST['weeks']) ? $_POST['weeks'] : $aConfig['weeks'];

    /* Week */
    $bNow = true;
    if (isset($_POST['week-from'])) {
        $aWeek = explode('-', $_POST['week-from']);
        if (count($aWeek == 2)) {
            $iYear = $aWeek[0];
            if (($iYear == $aConfig['year'] && $aWeek[1] >= $aConfig['week'] && $aWeek[1] <= 52) || ($iYear == $aConfig['year'] + 1 && $aWeek[1] > 1 && $aWeek[1] < $aConfig['week'])) {
                list($iYear, $iWeek) = $aWeek;
                $bNow = false;
            }
        }
    }
    if ($bNow === true) {
        list($iYear, $iWeek) = now();
    }

    /* Groups */    
    if (isset($_POST['group'])) {
        $aSelected = array_intersect($_POST['group'], array_keys($aConfig['filters']));
        foreach ($aConfig['groups'] as $sCategory => $aGroups) {
            $aGroupSelected = array();
            foreach ($aGroups[1] as $sValue => $sText) {
                if (in_array($sValue, $aSelected)) {
                    $aGroupSelected[] = $sValue;
                }
            }
            if (count($aGroupSelected) == count($aGroups[1])) {
                $aSelected = array_diff($aSelected, $aGroupSelected);
                $aSelected[] = $sCategory;
            }
        }
        $aGroups = array();
        foreach ($aSelected as $sGroup) {
            if (isset($aConfig['filters'][$sGroup])) {
                $aGroups[] = $aConfig['filters'][$sGroup];
            }
        }
        if (count($aGroups) > 0) {
            $oVuRooster = new VuRooster;
            $oVuCache = new Cache($oVuRooster);
            $oVuCache->clean();

            $oUvaRooster = new UvaRooster;
            $oUvaCache = new Cache($oUvaRooster);

            $aWeeks = array();
            try {
                $aGroups = $oVuCache->getGroups($aGroups);
                $oVuRooster->setWeek($iYear, $iWeek);
                $oUvaRooster->setWeek($iYear, $iWeek);

                $aTables = $aInfos = array();
                for ($i = 0; $i < $iWeeks; ++$i) {
                    $aWeeks[] = $oVuRooster->getWeek();
                    $aVuData = $aUvaData = array();
                    foreach ($aGroups as $sGroup => $sName) {        
                        try {
                            $aVuData[$sName] = $oVuCache->getData($sGroup);
                            if (isset($aConfig['uva'][$sName])) {
                                $aUvaData[$sName] = $oUvaCache->getData($aConfig['uva'][$sName]);
                            }                            
                        } catch (Exception $e) {
                            die(printf('<pre style="color: red"><strong>ERROR:</strong> %s</pre>', $e->getMessage()));
                        }
                    }
                    convertData($aVuData, $aTable, $aInfo);
                    convertData($aUvaData, $aTable, $aInfo);
                    $aTables[] = $aTable;
                    $aInfos[] = $aInfo;
                    $oVuRooster->nextWeek();
                    $oUvaRooster->nextWeek();
                }
                echo Template::getWeeks($aTables, $aInfos, $aWeeks);
            } catch (Exception $e) {
                die(printf('<pre style="color: red"><strong>ERROR:</strong> %s</pre>', $e->getMessage()));
            }            
        }
        $bForm = false;
    }
}

if ($bForm === true) {
    echo Template::getForm();
}