<?php
set_time_limit(0);

$aConfig = array(
    'cache' => 6,
    'start' => array('hour' => 8, 'minute' => 45),
    'end' => array('hour' => 17, 'minute' => 45),
    'steps' => 4,
    'weeks' => 10,
    'year' => 2011,
    'week' => 36
);
$aConfig['step'] = 60 / $aConfig['steps'];

$aConfig['filters'] = array(
    'S' => '([\d]S) \(FEW\)',
    '1S' => '(1S) \(FEW\)',
    '2S' => '(2S) \(FEW\)',
    '3S' => '(3S) \(FEW\)',
    'F' => '([\d]F) \(FEW\)',
    '1F' => '(1F) \(FEW\)',
    '2F' => '(2F) \(FEW\)',
    '3F' => '(3F) \(FEW\)',    
    'mCh' => '(mCh-[a-z]+) \(FEW\)',
    'mCh-AS' => '(mCh-AS) \(FEW\)',
    'mCh-MDSC' => '(mCh-MDSC) \(FEW\)',
    'mCh-MSP' => '(mCh-MSP) \(FEW\)',    
    'mDDS' => '(mDDS-[a-z]+) \(FEW\)',
    'mDDS-BCCA' => '(mDDS-BCCA) \(FEW\)',
    'mDDS-BDA' => '(mDDS-BDA) \(FEW\)',
    'mDDS-CMCT' => '(mDDS-CMCT) \(FEW\)',
    'mDDS-DDS' => '(mDDS-DDS) \(FEW\)',
    'mDDS-DDSA' => '(mDDS-DDSA) \(FEW\)',
    'mDDS-DDTF' => '(mDDS-DDTF) \(FEW\)');

$aConfig['colors'] = array(
    'wc'  => array('Werkcollege', '#8064a2'), /* purple */
    'te'  => array('Tentamen',    '#c0504d'), /*    red */
    'ht'  => array('Hertentamen', '#f79646'), /* orange */
    'hc'  => array('Hoorcollege', '#4f81bd'), /*   blue */
    'pr'  => array('Practicum',   '#9bbb59'), /*  green */    
    'bij' => array('Bijeenkomst', '#4bacc6'), /*   aqua */
);

$aConfig['types'] = array(
    'wc' => array(),
    'te' => array('tent'),
    'ht' => array(),
    'hc' => array('h', 'h/w'),
    'pr' => array('prac'),    
    'bij' => array('pres', 'tutor'),
);

$aConfig['uva'] = array(
    '1S' => 'BSc SK_1',
    '2S' => 'BSc SK_2',
    '3S' => 'BSc SK_3',
    'mCh-AS' => 'MSc CH-AS',
    'mCh-MDSC' => 'MSc CH-MDSC'
);

$aConfig['groups'] = array(
    'S' => array('Bachelor Scheikunde', array(
        '1S' => '1e jaar',
        '2S' => '2e jaar',
        '3S' => '3e jaar')),
    'F' => array('Bachelor Farmaceutische wetenschappen', array(
        '1F' => '1e jaar',
        '2F' => '2e jaar',
        '3F' => '3e jaar')),
    'mCh' => array('Master Chemistry', array(
        'mCh-AS' => 'Analytical Sciences',
        'mCh-MDSC' => 'Molecular Design, Synthesis and Catalysis',
        'mCh-MSP' => 'Molecular Simulation & Photonics')),
    'mDDS' => array('Drug Discovery & Safety', array(
        'mDDS-BCCA' => 'Biomarkers and Clinical Chemical Analysis',
        'mDDS-BDA' => 'Biomolecular Drug Analysis',
        'mDDS-CMCT' => 'Computational Medicinal Chemistry & Toxicology',
        'mDDS-DDS' => 'Drug Design & Synthesis',
        'mDDS-DDSA' => 'Drug Disposition & Safety Assessment',
        'mDDS-DDTF' => 'Drug Discovery & Target Finding')));

$aDirectories = array('group', 'rooster', 'data');
foreach ($aDirectories as $sDirectory) {
    if (!file_exists($sDirectory)) {
        mkdir($sDirectory);
    }
}