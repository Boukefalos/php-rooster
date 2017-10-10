<?php
require_once 'config.php';
require_once 'rooster.php';

class Cache {
    var $oRooster;

    function Cache($oRooster) {
        $this->oRooster = $oRooster;
    }

    function getWeek(&$iYear, &$iWeek) {
        if (!isset($iYear)) {
            $iYear = $this->oRooster->iYear;
        }
        if (!isset($iWeek)) {
            $iWeek = $this->oRooster->iWeek;
        }    
    }

    function getCached($aFiles) {
        global $aConfig;
        $sContents = null;
        if (count($aFiles) > 0) {
            krsort($aFiles);
            $iTime = key($aFiles);
            $sFile = current($aFiles);
            if (time() / 3600 - $iTime <= $aConfig['cache']) {
                $sContents = file_get_contents($sFile);
                array_shift($aFiles);
            }
            foreach ($aFiles as $sFile) {
                unlink($sFile);
            }
        }
        return $sContents;
    }

    function getContents($sDirectory, $sExtension, $sGroup, $iYear = null, $iWeek = null) {
        $this->getWeek($iYear, $iWeek);
        $sGlob = sprintf('%s/%s-%d-%02d-*.%s', $sDirectory, md5($sGroup), $iYear, $iWeek, $sExtension);
        $sRegex = sprintf('~%s/[\da-f]+-[0-9]+-[0-9]+-([0-9]+).%s$~', $sDirectory, $sExtension);
        $aFiles = array();
        if (($aGlob = glob($sGlob)) === false) {
            return null;
        }
        foreach ($aGlob as $sFile) {
            if (preg_match($sRegex, $sFile, $aMatch)) {
                $aFiles[$aMatch[1]] = $sFile;
            }
        }
        return $this->getCached($aFiles);
    }

    function getGroups($aFilters) {
        $aGroups = $aTodo = array();
        foreach ($aFilters as $sFilter) {
            $sGlob = sprintf('group/%s-*.base64', md5($sFilter));
            $aFiles = array();
            $sContents = null;
            if (($aGlob = glob($sGlob)) !== false) {
                foreach ($aGlob as $sFile) {
                    if (preg_match('~group/[\da-f]+-([0-9]+).base64$~', $sFile, $aMatch)) {
                        $aFiles[$aMatch[1]] = $sFile;
                    }
                }
                $sContents = $this->getCached($aFiles);
            }        
            if (isset($sContents)) {
                $aFilterGroups = unserialize(base64_decode($sContents));
            } else {
                $aFilterGroups = $this->oRooster->getGroups(array($sFilter));
                $sFile = sprintf('group/%s-%d.base64', md5($sFilter), time() / 3600);
                file_put_contents($sFile, base64_encode(serialize($aFilterGroups)));
            }
            $aGroups = array_merge($aGroups, $aFilterGroups);
        }
        return $aGroups;
    }

    function getRooster($sGroup, $iYear = null, $iWeek = null) {
        $this->getWeek($iYear, $iWeek);
        $sRooster = $this->getContents('rooster', 'html', $sGroup, $iYear, $iWeek);
        if (!isset($sRooster)) {
            $this->oRooster->setWeek($iYear, $iWeek);
            $sRooster = $this->oRooster->getPage($sGroup);
            $sFile = sprintf('rooster/%s-%d-%02d-%d.html', md5($sGroup), $iYear, $iWeek, time() / 3600);
            file_put_contents($sFile, $sRooster);
        }
        return $sRooster;
    }

    function getData($sGroup, $iYear = null, $iWeek = null) {
        $this->getWeek($iYear, $iWeek);
        $sData = $this->getContents('data', 'base64', $sGroup, $iYear, $iWeek);
        if (isset($sData)) {
            return unserialize(base64_decode($sData));
        } else {
            $sRooster = $this->getContents('rooster', 'html', $sGroup, $iYear, $iWeek);
            if (isset($sRooster)) {
                $aData = $this->oRooster->getData($sRooster);
            } else {
                $this->oRooster->setWeek($iYear, $iWeek);
                $sRooster = $this->oRooster->getPage($sGroup);
                $sFile = sprintf('rooster/%s-%d-%02d-%d.html', md5($sGroup), $iYear, $iWeek, time() / 3600);
                file_put_contents($sFile, $sRooster);
                $aData = $this->oRooster->getData();
            }
            $sFile = sprintf('data/%s-%d-%02d-%d.base64', md5($sGroup), $iYear, $iWeek, time() / 3600);
            file_put_contents($sFile, base64_encode(serialize($aData)));
            return $aData;
        }
    }

    function clean($bAll = false) {
        global $aConfig;
        $aGroupGlob = glob('group/*-*.base64');
        $aRoosterGlob = glob('rooster/*-*-*-*.html');
        $aDataGlob = glob('data/*-*-*-*.base64');
        $aFiles = array_merge(
            $aGroupGlob === false ? array() : $aGroupGlob,
            $aRoosterGlob === false ? array() : $aRoosterGlob,
            $aDataGlob === false ? array() : $aDataGlob);
        foreach ($aFiles as $sFile) {
            $bDelete = true;
            if (!$bAll) {
                $aFile = explode('.', $sFile);
                if (count($aFile) == 2) {
                    $aFile = explode('-', current($aFile));
                    $iTime = array_pop($aFile);
                    if (time() / 3600 - $iTime <= $aConfig['cache']) {
                        $bDelete = false;
                    }
                }
            }
            if ($bDelete) {
                unlink($sFile);
            }
        }
    }
}