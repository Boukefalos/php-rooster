<?php
require_once 'rooster.php';

// t=deze week, n=volgende week, +x=xe week vanaf begin academisch jaar
class VuRooster extends Rooster {
    const URL_SEMESTER = 'http://rooster.vu.nl/';

    var $sCookieFile;
    var $rCurl;
    var $sUrl;
    var $aAspFields;
    var $sContents;
    var $bOnGroupsPage;

    function __construct() {
        $this->sCookieFile = tempnam('tmp', 'curl');
        $this->rCurl = curl_init();
        curl_setopt_array($this->rCurl, array(
            CURLOPT_COOKIESESSION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_REFERER => true,
            CURLOPT_COOKIEJAR => $this->sCookieFile,
            CURLOPT_COOKIEFILE => $this->sCookieFile,
            CURLOPT_URL => self::URL_SEMESTER));
        parent::__construct();
    }

    function __destruct() {    
        curl_close($this->rCurl);
        unlink($this->sCookieFile);
    }

    function setWeek($iYear, $iWeek) {
        parent::setWeek($iYear, $iWeek);
        $this->sWeek = sprintf('+%d', $this->sWeek);
        curl_setopt($this->rCurl, CURLOPT_POST, false);
        $this->execute();
        $this->bOnGroupsPage = false;
    }

    function buildQuery($aFields) {
        $aQuery = array();
        foreach ($aFields as $sKey => $mValue) {
            $aQuery[] = sprintf('%s=%s', $sKey, $mValue);
        }
        return implode('&', $aQuery);
    }

    function getAspFields() {
        if ($this->bOnGroupsPage) {
            return $this->aAspFields;
        }
        preg_match('~id="__VIEWSTATE" value="([^"]+)"~i', $this->sContents, $aViewState);
        preg_match('~id="__EVENTVALIDATION" value="([^"]+)"~i', $this->sContents, $aEventValidation);
        if (count($aViewState) < 2 || count($aEventValidation) < 2) {
            throw new Exception('Failed to get asp fields from contents');
        }
        return $this->aAspFields = array(
            '__VIEWSTATE' => urlencode($aViewState[1]),
            '__EVENTVALIDATION' => urlencode($aEventValidation[1]));
    }

    function setPostFields($aPostFields) {
        try {
            $aAspFields = $this->getAspFields();
        } catch (Exception $e) {
            throw new Exception('Failed to get required asp fields from contents');
        }
        $sQuery = $this->buildQuery(array_merge($aAspFields, $aPostFields));
        curl_setopt($this->rCurl, CURLOPT_POSTFIELDS, $sQuery);
    }

    function execute() {
        if (!($sContents = curl_exec($this->rCurl))) {
            throw new Exception('Failed to execute request');
        }
        $this->sContents = $sContents;
    }

    function loadGroupsPage() {
        if ($this->bOnGroupsPage) {
            return;
        }
        try {
            /* Enter group selection mode */
            $this->setPostFields(array(
                '__EVENTTARGET' => 'LinkBtn_studentsets'));
            $this->execute();

            /* Stay on groups page */
            $this->getAspFields();
            $this->bOnGroupsPage = true;
        } catch (Exception $e) {
            throw new Exception('Failed to navigate to groups page');
        }
    }

    function getGroups($aFilters) {
        $this->loadGroupsPage();
        preg_match('~<select [^>]* id="dlObject" [^>]*>~', $this->sContents, $aMatch);
        if (count($aMatch) == 0) {
            throw new Exception('Failed to get object list from page');
        }
        $aPart = explode($aMatch[0], $this->sContents);
        $sPart = $aPart[1];
        $aPart = explode('</select>', $sPart);
        $sPart = $aPart[0];
        $aGroups = array();
        foreach ($aFilters as $sFilter) {                
            $sRegex = sprintf('~"([^"]+)">%s~i', $sFilter);
            preg_match_all($sRegex, $sPart, $aMatches, PREG_SET_ORDER);
            foreach ($aMatches as $aMatch) {
                $aGroups[$aMatch[1]] = $aMatch[2];
            }
        }
        return $aGroups;
    }

    function getPage($sObject) {
        try {
            $this->loadGroupsPage();

            /* Select group and period */
            $this->setPostFields(array(
                'tLinkType' => 'studentsets',
                'dlObject' => urlencode($sObject),
                'lbWeeks' => $this->sWeek,
                'dlType' => urlencode('TextSpreadsheet;SWS_Groep'),
                'bGetTimetable' => null));

            $this->execute();
            return $this->sContents;
        } catch (Exception $e) {
            throw new Exception('Failed to load page');
        }
    }

    function getData($sRooster = null) {
        $aDays = explode('<table class=\'spreadsheet\'', isset($sRooster) ? $sRooster : $this->sContents);
        if (count($aDays) != 8) {
            throw new Exception('Page does not contain valid data');
        }
        array_shift($aDays);
        array_pop($aDays);
        array_pop($aDays);
        $aData = array();
        foreach ($aDays as $iDay => $sDay) {
            $aColumns = null;
            $aRows = explode('<tr>', $sDay);
            $sHeader = array_shift($aRows);
            if (!isset($aColumns)) {
                preg_match_all('~<td>([^>]*)</td>~', $sHeader, $aMatches);
                foreach ($aMatches[1] as $iColumn => $sColumn) {
                    switch ($sColumn) {
                        case 'Start':
                            $aColumns[$iColumn] = 'start';
                            break;
                        case 'Einde':
                            $aColumns[$iColumn] = 'end';
                            break;
                        case 'Vaknaam':
                            $aColumns[$iColumn] = 'course';
                            break;
                        case 'Type':
                            $aColumns[$iColumn] = 'type';
                            break;
                        case 'Zalen':
                            $aColumns[$iColumn] = 'room';
                            break;
                    }
                }
            }
            $aData[$iDay] = array();
            foreach ($aRows as $sRow) {
                $sRow = str_replace('<br>', null, $sRow);
                preg_match_all('~<td>([^>]*)</td>~', $sRow, $aMatches);
                $aInfo = array();
                foreach ($aColumns as $iColumn => $sColumn) {              
                    $aInfo[$sColumn] = $aMatches[1][$iColumn];
                }
                $aData[$iDay][] = $aInfo;
            }
        }
        return $aData;
    }
}