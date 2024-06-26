<?php
/*******************************************************************************
*
*  filename    : Menu.php
*  description : menu that appears after login, shows login attempts
*
*  http://www.churchdb.org/
*  Copyright 2001-2002 Phillip Hullquist, Deane Barker, Michael Wilt
*
*  Additional Contributors:
*  2006 Ed Davis
*
*
*  Copyright Contributors
*
*
*  ChurchInfo is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This file best viewed in a text editor with tabs stops set to 4 characters
*
******************************************************************************/

// Include the function library
require 'Include/Config.php';
require 'Include/Functions.php';

// Set the page title
$sPageTitle = gettext('Welcome to ChurchInfo');

require 'Include/Header.php';


echo '<p>'.gettext('Hi').' '.$_SESSION['UserFirstName'].'</p>';

if ($_SESSION['iLoginCount'] == 0) {

    echo '<p>'.gettext('This is your first login').'.</p>';

} else {

    $dLL = $_SESSION['dLastLogin'];
    $sSQL = "SELECT DAYNAME('$dLL') as dn, MONTHNAME('$dLL') as mn, DAYOFMONTH('$dLL') as dm, "
    .       "YEAR('$dLL') as y, HOUR('$dLL') as h, DATE_FORMAT('$dLL', ':%i') as m";
    extract(mysqli_fetch_array(RunQuery($sSQL)));

    echo '<p>'.gettext('Welcome back').'.';

    echo ' '.gettext('You last logged in on').' '.gettext("$dn").', '.gettext("$mn")
    .   " $dm, $y " . gettext('at') . " $h$m.</p>\n";

    echo '<p>'.gettext('There were').' '.$_SESSION['iFailedLogins'].' '
    .   gettext('failed login attempt(s) since your last successful login').'.</p>';

}

    
    echo '
    <style>
    .quicklinks-container {
    display: table-cell;
    border: solid gray 1px;
    }
    .quicklinks-subcontainer {
        float: left;
    padding: .5em;
    }
    .quicklink {
    padding: .5em;
    }
    .qlindent1 {
        padding-left: 2em;
    }
    </style>
    <b>Quick Links:</b>
    <div class="quicklinks-container">
        <div class="quicklinks-subcontainer">
            <div class="quicklink"><a href="SelectList.php?mode=person&Classification=1&Number=1000">Members</a></div>
            <div class="quicklink"><a href="SelectList.php?mode=family&Number=500">Families</a></div>
            <div class="quicklink"><a href="GroupList.php">Groups</a></div>
            <div class="quicklink"><a href="ListEvents.php">Events</a></div>
            <div class="quicklink"><a href="EventNames.php">Add Event</a></div>
        </div>
        <div class="quicklinks-subcontainer">
            <div class="quicklink"><a href="QueryList.php">Queries:</a></div>
            <div class="quicklink qlindent1"><a href="QueryView.php?QueryID=15">Advanced Search</a></div>
            <div class="quicklink qlindent1"><a href="QueryView.php?QueryID=25">Volunteer Search</a></div>
        </div>
    </div>
    <div><br><a href="https://icons8.com">Icon pack by Icons8</a></div>
    ';
    
    
require 'Include/Footer.php';
?>
