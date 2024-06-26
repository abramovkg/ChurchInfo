<?php
/*******************************************************************************
 *
 *  filename    : ReportList.php
 *  last change : 2003-03-20
 *  website     : http://www.infocentral.org
 *  copyright   : Copyright 2003 Chris Gebhardt
 *
 *  InfoCentral is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 ******************************************************************************/

require 'Include/Config.php';
require 'Include/Functions.php';

//Set the page title
$sPageTitle = gettext('Report Menu');

$today = getdate();
$year = $today['year'];

require 'Include/Header.php';

?>

<p>
<a class="MediumText" href="GroupReports.php"><?php 
    echo gettext('Reports on groups and roles'); ?></a>
<br>
<?php 
    echo gettext('Report on group and roles selected (it may be a multi-page PDF).'); ?>
</p>


<?php if ($_SESSION['bCreateDirectory'] == 1) { ?>
	<p>
	<a class="MediumText" href="DirectoryReports.php"><?php echo gettext('Members Directory'); ?></a>
	<br>
	<?php echo gettext('Printable directory of all members, grouped by family where assigned'); ?>
	</p>
<?php } ?>

<?php /*
<p>
<a href=''><?php echo gettext('Members Directory w/Photos'); ?></a>
<br>
<?php echo gettext('Printable directory of all members. Family photos where available / Individual photos otherwise.'); ?>
</p> */ ?>

<p>
<a class="MediumText" href="LettersAndLabels.php"><?php 
    echo gettext('Letters and Mailing Labels'); ?></a>
<br><?php 
    echo gettext('Generate letters and mailing labels.'); ?>
</p>

<p>
<a class="MediumText" href="SundaySchool.php"><?php 
    echo gettext('Sunday School Reports'); ?></a>
<br><?php 
    echo gettext('Generate class lists and attendance sheets'); ?>
</p>

<?php
    if ($_SESSION['bFinance']) {
	echo '<p>';
	echo '<a class="MediumText" href="FinancialReports.php">';
    echo gettext('Financial Reports')."</a><br>\n"; 
    echo gettext('Pledges and Payments')."</p>"; 
}
?>

<?php
    if ($_SESSION['bAdmin']) {
	echo '<p>';
	echo '<a class="MediumText" href="CanvassAutomation.php">';
    echo gettext('Canvass Automation') . "</a><br>";
    echo gettext('Automated support for conducting an every-member canvass.');
}
?>

<p>
<span class="MediumText"><u><?php echo gettext("Event Attendance"); ?></u></span>
<br>
<?php echo gettext("Generate attendance -AND- non-attendance reports for events"); ?>
<br>
<?php
//$sSQL = "SELECT * FROM event_types";
$sSQL = "SELECT DISTINCT event_types.* FROM event_types RIGHT JOIN events_event ON event_types.type_id=events_event.event_type ORDER BY type_id ";
$rsOpps = RunQuery($sSQL);
$numRows = mysqli_num_rows($rsOpps);

// List all events
    for ($row = 1; $row <= $numRows; $row++)
    {
        $aRow = mysqli_fetch_array($rsOpps);
        extract($aRow);
        if (is_null ($type_id))
            $type_id = 0;
        if (is_null ($type_name))
            $type_name = "";
        echo '&nbsp;&nbsp;&nbsp;<a href="EventAttendance.php?Action=List&Event='.
            $type_id.'&Type='.gettext($type_name).'" title="List All '.
            gettext($type_name).' Events"><strong>'.gettext($type_name).
            '</strong></a>'."<br>\n";
    }
?>
</p>


<?php 
if ($bUSAddressVerification) {
    echo '<p>';
	echo '<a class="MediumText" href="USISTAddressVerification.php">';
    echo gettext('US Address Verification Report')."</a><br>\n";
    echo gettext('Generate report comparing all US family addresses '. 
		'with United States Postal Service Standard Address Format.<br>')."\n";
}
?>
</p>


<?php
require 'Include/Footer.php';
?>
