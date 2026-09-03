<?php

$root = dirname(__DIR__);
$featureFiles = [
    'com_ra_treasurer/administrator/forms/claimtype.xml',
    'com_ra_treasurer/administrator/forms/filter_claimtypes.xml',
    'com_ra_treasurer/administrator/src/Controller/ClaimtypeController.php',
    'com_ra_treasurer/administrator/src/Controller/ClaimtypesController.php',
    'com_ra_treasurer/administrator/src/Model/ClaimtypeModel.php',
    'com_ra_treasurer/administrator/src/Model/ClaimtypesModel.php',
    'com_ra_treasurer/administrator/src/Table/ClaimtypeTable.php',
    'com_ra_treasurer/administrator/src/View/Claimtype/HtmlView.php',
    'com_ra_treasurer/administrator/src/View/Claimtypes/HtmlView.php',
    'com_ra_treasurer/administrator/tmpl/claimtype/default.php',
    'com_ra_treasurer/administrator/tmpl/claimtype/edit.php',
    'com_ra_treasurer/administrator/tmpl/claimtypes/default.php',
];

foreach ($featureFiles as $relativePath) {
    $path = $root . '/' . $relativePath;

    if (!is_file($path)) {
        throw new RuntimeException('Missing claim-type feature file: ' . $relativePath);
    }

    $contents = file_get_contents($path);

    if (preg_match('/event/i', $contents)) {
        throw new RuntimeException('An event reference remains in ' . $relativePath . '.');
    }

    if (strpos($contents, 'Text::_') !== false || preg_match('/\b(?:COM|JTOOLBAR|JGRID|JSTATUS)_[A-Z0-9_]+\b/', $contents)) {
        throw new RuntimeException('A language key remains in ' . $relativePath . '.');
    }
}

$model = file_get_contents($root . '/com_ra_treasurer/administrator/src/Model/ClaimtypesModel.php');
$table = file_get_contents($root . '/com_ra_treasurer/administrator/src/Table/ClaimtypeTable.php');
$manifest = file_get_contents($root . '/com_ra_treasurer/administrator/ra_treasurer.xml');
$installSql = file_get_contents($root . '/com_ra_treasurer/administrator/sql/install.mysql.utf8.sql');
$installer = file_get_contents($root . '/com_ra_treasurer/script.php');

if (strpos($model, "#__ra_claim_types") === false
    || strpos($table, "#__ra_claim_types") === false
    || strpos($installSql, "#__ra_claim_types") === false) {
    throw new RuntimeException('The claim-type MVC feature and installation schema must use #__ra_claim_types.');
}

if (strpos($manifest, 'option=com_ra_treasurer&amp;view=claimtypes') === false) {
    throw new RuntimeException('The claim-types administrator menu is missing.');
}

if (substr_count($installer, '$this->ensureClaimTypesTable();') !== 2) {
    throw new RuntimeException('The claim-types table must be ensured on install and update.');
}

echo "Claim types feature contract tests passed\n";
