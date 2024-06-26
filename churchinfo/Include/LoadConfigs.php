<?php
/*******************************************************************************
*
*  filename    : Include/LoadConfigs.php
*  website     : http://www.churchdb.org
*  description : global configuration 
*                   The code in this file used to be part of part of Config.php
*
*  Copyright 2001-2005 Phillip Hullquist, Deane Barker, Chris Gebhardt, 
*                      Michael Wilt, Timothy Dearborn
*
*
*  LICENSE:
*  (C) Free Software Foundation, Inc.
*
*  ChurchInfo is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 3 of the License, or
*  (at your option) any later version.
*
*  This program is distributed in the hope that it will be useful, but
*  WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
*  General Public License for more details.
*
*  http://www.gnu.org/licenses
*
*  This file best viewed in a text editor with tabs stops set to 4 characters.
*  Please configure your editor to use soft tabs (4 spaces for a tab) instead
*  of hard tab characters.
*
******************************************************************************/

// Establish the database connection (mysqli_ library)
$cnChurchInfo = mysqli_connect($sSERVERNAME,$sUSER,$sPASSWORD,$sDATABASE);

$sql = "SHOW TABLES FROM `$sDATABASE`";
$tableRes = mysqli_query ($cnChurchInfo, $sql);
$tablecheck = mysqli_num_rows($tableRes);

if (!$tablecheck) {
    die ("There are no tables installed in your database.  Please install the tables.");
}

// Initialize the session
ini_set('session.cookie_httponly', '1'); // fix bug#249- prevent cross-site scripting attacks
session_start();

// Avoid consecutive slashes when $sRootPath = '/'
if (strlen($sRootPath) < 2) $sRootPath = '';

// Some webhosts make it difficult to use DOCUMENT_ROOT.  Define our own!
$sDocumentRoot = dirname(dirname(__FILE__));

$version = mysqli_fetch_row(mysqli_query($cnChurchInfo, "SELECT version()"));

if (substr($version[0],0,3) >= "4.1") {
    mysqli_query($cnChurchInfo, "SET NAMES 'utf8'");
}

// Read values from config table into local variables
// **************************************************
$sSQL = "SELECT cfg_name, IFNULL(cfg_value, cfg_default) AS value "
      . "FROM config_cfg WHERE cfg_section='General'";
$rsConfig = mysqli_query($cnChurchInfo, $sSQL);         // Can't use RunQuery -- not defined yet
if ($rsConfig) {
    while (list($cfg_name, $value) = mysqli_fetch_row($rsConfig)) {
        $$cfg_name = $value;
    }
}

if (isset($_SESSION['iUserID'])) {      // Not set on Default.php
    // Load user variables from user config table.
    // **************************************************
    $sSQL = "SELECT ucfg_name, ucfg_value AS value "
          . "FROM userconfig_ucfg WHERE ucfg_per_ID='".$_SESSION['iUserID']."'";
    $rsConfig = mysqli_query($cnChurchInfo, $sSQL);     // Can't use RunQuery -- not defined yet
    if ($rsConfig) {
        while (list($ucfg_name, $value) = mysqli_fetch_row($rsConfig)) {
            $$ucfg_name = $value;
                $_SESSION[$ucfg_name] = $value;
//              echo "<br>".$ucfg_name." ".$_SESSION[$ucfg_name];
        }
    }
}

$sMetaRefresh = '';  // Initialize to empty

require_once("winlocalelist.php");

if (!function_exists("stripos")) {
  function stripos($str,$needle) {
   return strpos(strtolower($str),strtolower($needle));
  }
}

if (!(stripos(php_uname('s'), "windows") === false)) {
//  $sLanguage = $lang_map_windows[strtolower($sLanguage)];
    $sLang_Code = $lang_map_windows[strtolower($sLanguage)];
} else {
    $sLang_Code = $sLanguage;
}
putenv("LANG=$sLang_Code");
setlocale(LC_ALL, $sLang_Code, $sLang_Code.".utf8", $sLang_Code.".UTF8", $sLang_Code.".utf-8", $sLang_Code.".UTF-8");

if (isset($sTimeZone)) {
    date_default_timezone_set($sTimeZone);
}

// Get numeric and monetary locale settings.
$aLocaleInfo = localeconv();

// This is needed to avoid some bugs in various libraries like fpdf.
setlocale(LC_NUMERIC, 'C');

// patch some missing data for Italian.  This shouldn't be necessary!
if ($sLanguage == 'it_IT')
{
    $aLocaleInfo['thousands_sep'] = '.';
    $aLocaleInfo['frac_digits'] = '2';
}

if (function_exists('bindtextdomain'))
{
    $domain = 'messages';

    $sLocaleDir = 'locale';
    if (!is_dir($sLocaleDir))
        $sLocaleDir = '../' . $sLocaleDir;

    bind_textdomain_codeset ($domain, 'UTF-8' );
    bindtextdomain($domain, $sLocaleDir);
    textdomain($domain);
}
else
{
    if ($sLanguage != 'en_US')
    {
        // PHP array version of the l18n strings
        $sLocaleMessages = "locale/$sLanguage/LC_MESSAGES/messages.php";

        if (!is_readable($sLocaleMessages))
            $sLocaleMessages = "../$sLocaleMessages";

        require ($sLocaleMessages);

        // replacement implementation of gettext for broken installs
        function gettext($text)
        {
            global $locale;

            if (!empty($locale[$text]))
                return $locale[$text];
            else
                return $text;
        }
    }
    else
    {
        // dummy gettext function
        function gettext($text)
        {
            return $text;
        }
    }

    function _($text) { return gettext($text); }
}
?>
