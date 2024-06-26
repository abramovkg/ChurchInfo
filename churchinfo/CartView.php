<?php
/*******************************************************************************
*
*  filename    : CartView.php
*  website     : http://www.churchdb.org
*
*  Copyright 2001-2003 Phillip Hullquist, Deane Barker, Chris Gebhardt
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
*  This file best viewed in a text editor with tabs stops set to 4 characters
*
******************************************************************************/

function ExportCartToCSV()
{
    $sSQL  =    " DROP TEMPORARY TABLE IF EXISTS tmp_canvassers ";
    RunQuery($sSQL);

    // Make temporary copy of person_per table and call it tmp_canvassers
    $sSQL  =    " CREATE TEMPORARY TABLE tmp_canvassers ".
                " SELECT * FROM person_per ";
    RunQuery($sSQL);

    $sSQL  =    " SELECT lst_OptionName AS Classification, fam_Name AS Family, ".
                " person_per.per_LastName AS Last_Name, ".
                " person_per.per_FirstName AS First_Name, ".
                " fam_HomePhone, person_per.per_HomePhone AS per_HomePhone, ".
                " fam_Address1, fam_Address2, fam_City, ".
                " fam_State, fam_Zip, person_per.per_DateEntered AS DateEntered, ".
                " tmp_canvassers.per_LastName AS Cnvsr_Last_Name, ".
                " tmp_canvassers.per_FirstName AS Cnvsr_First_Name ".
                " FROM person_per ".
                " LEFT JOIN family_fam ON fam_ID = person_per.per_fam_ID ".
                " LEFT JOIN list_lst ON lst_OptionID = person_per.per_cls_ID ".
                " LEFT JOIN tmp_canvassers ON tmp_canvassers.per_ID = fam_Canvasser ".
                " WHERE person_per.per_ID ".
                "     IN (" . ConvertCartToString($_SESSION['aPeopleCart']) . ") ".
                " AND lst_ID='1' ".
                " ORDER BY fam_Name, fam_ID, Last_Name, First_Name ";
    
    //Run the SQL
    $rsQueryResults = RunQuery($sSQL);

    $sCSVstring = "";

    if (MySQLError() != "")
    {
        $sCSVstring = gettext("An error occured: ") . MySQLError ();
    }
    else
    {

        //Loop through the fields and write the header row
        for ($iCount = 0; $iCount < mysqli_num_fields($rsQueryResults); $iCount++)
        {
            $sCSVstring .= mysqli_fetch_field_direct($rsQueryResults, $iCount)->name . ",";
        }

        $sCSVstring .= "\n";

        //Loop through the recordsert
        while($aRow =mysqli_fetch_array($rsQueryResults))
        {
            //Loop through the fields and write each one
            for ($iCount = 0; $iCount < mysqli_num_fields($rsQueryResults); $iCount++)
            {
                $sCSVstring .= $aRow[$iCount] . ",";
            }

            $sCSVstring .= "\n";
        }
    }

    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=Cart-" . date("Ymd-Gis") . ".csv");
    header("Content-Transfer-Encoding: binary");
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public'); 
    echo $sCSVstring;
    exit;

}

function FixHTAccess ()
{
    $rf = fopen("tmp_attach/.htaccess", "w");
    fprintf ($rf, "<filesmatch \".(htaccess|htpasswd|ini|php|fla|psd|log|sh|pl|py|html|jsp|asp|shtml|htm|cgi)$\">\n");
    fprintf ($rf, "Order allow,deny\n");
    fprintf ($rf, "Deny from all\n");
    fprintf ($rf, "</filesmatch>\n");
    fclose ($rf);
}
// Include the function library

require "Include/Config.php";
require "Include/Functions.php";
require "Include/LabelFunctions.php";

if (isset($_POST["rmEmail"]))
{
     rmEmail();
}

if (isset($_POST["cartcsv"]))
{

    // If user does not have CSV Export permission, redirect to the menu.
    if (!$bExportCSV) 
    {
       Redirect("Menu.php");
       exit;
    }

    ExportCartToCSV();
    exit;
}

// Set the page title and include HTML header
$sPageTitle = gettext("View Your Cart");
require "Include/Header.php";

// Confirmation message that people where added to Event from Cart
if (array_key_exists('aPeopleCart', $_SESSION) and count($_SESSION['aPeopleCart']) == 0) {
        if (!array_key_exists("Message", $_GET)) {
            echo "<p align=\"center\" class=\"LargeText\">" . gettext("You have no items in your cart.") . "</p>";
        } else {
            switch ($_GET["Message"]) {
                case "aMessage":
                    echo '<p align="center" class="LargeText">'.$_GET["iCount"].' '.($_GET["iCount"] == 1 ? "Record":"Records").' Emptied into Event ID:'.$_GET["iEID"].'</p>'."\n";
                break;
            }
        }
        echo '<p align="center"><input type="button" name="Exit" class="icButton" value="'.gettext("Back to Menu").'" '."onclick=\"javascript:document.location='Menu.php';\"></p>\n";

} else {

        // Create array with Classification Information (lst_ID = 1)
        $sClassSQL  = "SELECT * FROM list_lst WHERE lst_ID=1 ORDER BY lst_OptionSequence";
        $rsClassification = RunQuery($sClassSQL);
        unset($aClassificationName);
        $aClassificationName[0] = "Unassigned";
        while ($aRow = mysqli_fetch_array($rsClassification))
        {
            extract($aRow);
            $aClassificationName[intval($lst_OptionID)]=$lst_OptionName;
        }

        // Create array with Family Role Information (lst_ID = 2)
        $sFamRoleSQL  = "SELECT * FROM list_lst WHERE lst_ID=2 ORDER BY lst_OptionSequence";
        $rsFamilyRole = RunQuery($sFamRoleSQL);
        unset($aFamilyRoleName);
        $aFamilyRoleName[0] = "Unassigned";
        while ($aRow = mysqli_fetch_array($rsFamilyRole))
        {
            extract($aRow);
            $aFamilyRoleName[intval($lst_OptionID)]=$lst_OptionName;
        }


        $sSQL = "SELECT * FROM person_per LEFT JOIN family_fam ON person_per.per_fam_ID = family_fam.fam_ID WHERE per_ID IN (" . ConvertCartToString($_SESSION['aPeopleCart']) . ") ORDER BY per_LastName";
        $rsCartItems = RunQuery($sSQL);
        $iNumPersons = mysqli_num_rows($rsCartItems);

        $sSQL = "SELECT distinct per_fam_ID FROM person_per LEFT JOIN family_fam ON person_per.per_fam_ID = family_fam.fam_ID WHERE per_ID IN (" . ConvertCartToString($_SESSION['aPeopleCart']) . ") ORDER BY per_fam_ID";
        $iNumFamilies = mysqli_num_rows(RunQuery($sSQL));

        if ($iNumPersons > 16)
        {
        ?>
        <center>
        <form method="get" action="CartView.php#GenerateLabels">
        <input type="submit" class="icButton" name="gotolabels" 
        value="<?php echo gettext("Go To Labels");?>">
        </form></center>
        <?php
        }

        echo '<p align="center">' . gettext("Your cart contains") . ' ' . $iNumPersons . ' ' . gettext("persons from") . ' ' . $iNumFamilies . ' ' . gettext("families.") . '</p>';

        echo '<table align="center" width="70%" cellpadding="4" cellspacing="0">';
        echo '<tr class="TableHeader">';
        echo '<td><b>' . gettext("Name") . '</b></td>';
        echo '<td align="center"><b>' . gettext("Address?") . '</b></td>';
        echo '<td align="center"><b>' . gettext("Email?") . '</b></td>';
        echo '<td><b>' . gettext("Remove") . '</b></td>';
        echo '<td align="center"><b>' . gettext("Classification") . '</b></td>';
        echo '<td align="center"><b>' . gettext("Family Role") . '</b></td>';

        $sEmailLink = "";
        $iEmailNum = 0;
        $sRowClass = "RowColorA";
        $email_array = array ();

        while ($aRow = mysqli_fetch_array($rsCartItems))
        {
                $sRowClass = AlternateRowStyle($sRowClass);

                extract($aRow);

                $sEmail = SelectWhichInfo($per_Email, $fam_Email, False);
                if (strlen($sEmail) == 0 && strlen ($per_WorkEmail) > 0) {
                	$sEmail = $per_WorkEmail;                	
                }
                
                if (strlen($sEmail)) {
                    $sValidEmail = gettext("Yes");
                    if (!stristr($sEmailLink, $sEmail)) {
                        $email_array[] = $sEmail;

                        if ($iEmailNum == 0) {       
                        	// Comma is not needed before first email address
                            $sEmailLink .= $sEmail;
                            $iEmailNum++;
                        } else
                            $sEmailLink .= $sMailtoDelimiter . $sEmail;
                    }
                } else {
                        $sValidEmail = gettext("No");
                }

                $sAddress1 = SelectWhichInfo($per_Address1, $fam_Address1, False);
                $sAddress2 = SelectWhichInfo($per_Address2, $fam_Address2, False);

                if (strlen($sAddress1) > 0 || strlen($sAddress2) > 0)
                        $sValidAddy = gettext("Yes");
                else
                        $sValidAddy = gettext("No");

                echo '<tr class="' . $sRowClass . '">';
                echo '<td><a href="PersonView.php?PersonID=' . $per_ID . '">' . FormatFullName($per_Title, $per_FirstName, $per_MiddleName, $per_LastName, $per_Suffix, 1) . '</a></td>';

                echo '<td align="center">' . $sValidAddy . '</td>';
                echo '<td align="center">' . $sValidEmail . '</td>';
                echo '<td><a onclick="saveScrollCoordinates()" 
                        href="CartView.php?RemoveFromPeopleCart=' . 
                        $per_ID . '">' . gettext("Remove") . '</a></td>';
                echo '<td align="center">' . $aClassificationName[$per_cls_ID] . '</td>';
                echo '<td align="center">' . $aFamilyRoleName[$per_fmr_ID] . '</td>';

                echo "</tr>";
        }

        echo "</table>";
}

if (count($_SESSION['aPeopleCart']) != 0)
{
        echo "<br><table align=\"center\" cellpadding=\"15\"><tr><td valign=\"top\">";
        echo "<p align=\"center\" class=\"MediumText\">";
        echo "<b>" . gettext("Cart Functions") . "</b><br>";
        echo "<br>";
        echo "<a href=\"CartView.php?Action=EmptyCart\">" . gettext("Empty Cart") . "</a>";

        if ($_SESSION['bManageGroups']) {
                echo "<br>";
                echo "<a href=\"CartToGroup.php\">" . gettext("Empty Cart to Group") . "</a>";
        }
        if ($_SESSION['bAddRecords']) {
                echo "<br>";
                echo "<a href=\"CartToFamily.php\">" . gettext("Empty Cart to Family") . "</a>";
        }
        echo "<br>";
        echo "<a href=\"CartToEvent.php\">" . gettext("Empty Cart to Event") . "</a>";

        // Only show CSV export link if user is allowed to CSV export.
        if ($bExportCSV) 
        {
            /* Link to CSV export */
            echo "<br>";
            echo "<a href=\"CSVExport.php?Source=cart\">" . gettext("CSV Export") . "</a>";
        }

        if ($iEmailNum > 0) {
                // Add default email if default email has been set and is not already in string
                if ($sToEmailAddress != "" && $sToEmailAddress != "myReceiveEmailAddress" && !stristr($sEmailLink, $sToEmailAddress))
                        $sEmailLink .= $sMailtoDelimiter . $sToEmailAddress;
                $sEmailLink = urlencode($sEmailLink);  // Mailto should comply with RFC 2368
                if ($bEmailMailto) { // Does user have permission to email groups with mailto
                echo "<br><a href=\"mailto:" . $sEmailLink ."\">". gettext("Email Cart") . "</a>";
                echo "<br><a href=\"mailto:?bcc=".$sEmailLink."\">".gettext("Email (BCC)")."</a>";
                }
        }
        echo "<br><a href=\"MapUsingGoogle.php?GroupID=0\">" . gettext("Map Cart") . "</a>";
        echo "<br><a href=\"Reports/NameTags.php?labeltype=74536&labelfont=times&labelfontsize=36\">" . gettext("Name Tags") . "</a>";
        echo "</p></td>";
?>
        <td>
        <a name="GenerateLabels"></a>

        <script language="JavaScript" type="text/javascript"><!--
        function codename() 
        {
            if(document.labelform.bulkmailpresort.checked)
            {
                document.labelform.bulkmailquiet.disabled=false;
            }
            else
            {
                document.labelform.bulkmailquiet.disabled=true;
                document.labelform.bulkmailquiet.checked=false;
            }
        }
    
        //-->
        </SCRIPT>



    <form method="get" action="Reports/PDFLabel.php" name="labelform">
        <table cellpadding="4" align="center">
                <?php
                LabelGroupSelect("groupbymode");

                echo '  <tr><td class="LabelColumn">' . gettext("Bulk Mail Presort") . '</td>';
                echo '  <td class="TextColumn">';
                echo '  <input name="bulkmailpresort" type="checkbox" onclick="codename()"';
                echo '  id="BulkMailPresort" value="1" ';
                if (array_key_exists ("buildmailpresort", $_COOKIE) and $_COOKIE["bulkmailpresort"])
                    echo "checked";
                echo '  ><br></td></tr>';

                echo '  <tr><td class="LabelColumn">' . gettext("Quiet Presort") . '</td>';
                echo '  <td class="TextColumn">';
                echo '  <input ';
                if (array_key_exists ("buildmailpresort", $_COOKIE) and !$_COOKIE["bulkmailpresort"])
                    echo 'disabled ';   // This would be better with $_SESSION variable
                                        // instead of cookie ... (save $_SESSION in mysql)
                echo 'name="bulkmailquiet" type="checkbox" onclick="codename()"';
                echo '  id="QuietBulkMail" value="1" ';
                if (array_key_exists ("bulkmailquiet", $_COOKIE) and $_COOKIE["bulkmailquiet"] && array_key_exists ("buildmailpresort", $_COOKIE) and $_COOKIE["bulkmailpresort"])
                    echo "checked";
                echo '  ><br></td></tr>';

                ToParentsOfCheckBox("toparents");
                LabelSelect("labeltype");
                FontSelect("labelfont");
                FontSizeSelect("labelfontsize");
                StartRowStartColumn();
                IgnoreIncompleteAddresses();
                LabelFileType();
                ?>                             

                <tr>
                        <td></td>
                        <td><input type="submit" class="icButton" value="<?php echo gettext("Generate Labels");?>" name="Submit"></td>
                </tr>
    </table></form></td></tr></table>

<?php
// Only show CSV export link if user is allowed to CSV export.
if ($bExportCSV) 
{
    ?>
    <div align="center">
    <form method="post" action="CartView.php">
    <?php echo "<br><h2>" . gettext("Export Cart to CSV File") . "</h2>"; ?>
    <input type="submit" class="icButton" name="cartcsv" 
            value="<?php echo gettext("Create CSV File");?>">
    </form>
    </div>
    <?php
} 

// Only show create directory link if user is allowed to create directories
if ($_SESSION['bCreateDirectory'] == 1)
{
?>
<div align="center"><form method="get" action="DirectoryReports.php">
<?php echo "<br><h2>" . gettext("Create Directory From Cart") . "</h2>"; ?>
<input type="submit" class="icButton" name="cartdir" 
       value="<?php echo gettext("Cart Directory");?>">
</form></div>
<?php
}

    if (($bEmailSend) && ($bSendPHPMail)) {
        if (isset($email_array)) {
            $bcc_list = "";
            foreach ($email_array as $email_address) {
                // Add all address except the default
                // avoid sending to this address twice
                if ($email_address != $sToEmailAddress) {
                    $bcc_list .= $email_address . ", ";
                }
            }
            if ($sToEmailAddress) {
                // append $sToEmailAddress
                $bcc_list .= $sToEmailAddress;
            } else {
                // remove the last ", "
                $bcc_list = substr($bcc_list, 0, strlen($bcc_list) - 2 );
            }
        }

        $sEmailForm = ""; // Initialize to empty

        ?><div align="center"><table><tr><td align="center"><?php
        echo "<br><h2>" . gettext("Send Email To People in Cart") . "</h2>";
 
        // Check if there are pending emails that have not been delivered
        // A user cannot send a new email until the previous email has been sent
    
        $sSQL  = "SELECT COUNT(emp_usr_id) as countjobs "
               . "FROM email_message_pending_emp "
               . "WHERE emp_usr_id='".$_SESSION['iUserID']."'";

        $rsPendingEmail = RunQuery($sSQL);
        $aRow = mysqli_fetch_array($rsPendingEmail);
        extract($aRow);

        $sSQL  = "SELECT COUNT(erp_usr_id) as countrecipients "
               . "FROM email_recipient_pending_erp "
               . "WHERE erp_usr_id='".$_SESSION['iUserID']."'";
        $rsCountRecipients = RunQuery($sSQL);
        $aRow = mysqli_fetch_array($rsCountRecipients);
        extract($aRow);

        if ($countjobs) {
            // There is already a message composed in mysql
            // Let's check and make sure it has not been sent.
            $sSQL = "SELECT * FROM email_message_pending_emp "
                  . "WHERE emp_usr_id='".$_SESSION['iUserID']."'";

            $rsPendingEmail = RunQuery($sSQL);
            $aRow = mysqli_fetch_array($rsPendingEmail);
            extract($aRow);

            if ($emp_to_send==0 && $countrecipients==0) {
                // if both are zero the email job has not started.  In this
                // case the user may edit the email and/or change the distribution

                // This user has no email messages stored mysql

                $sEmailSubject = "";
                if (array_key_exists ('emailsubject', $_POST))
                	$sEmailSubject = stripslashes($_POST['emailsubject']);
                $sEmailMessage = "";
                if (array_key_exists ('emailmessage', $_POST))
	                $sEmailMessage = stripslashes($_POST['emailmessage']);
                $hasAttach = 0;
                $attachName = "";

                if (array_key_exists ('Attach', $_FILES)) {
	        		$attachName = $_FILES['Attach']['name'];
	        		$hasAttach = 1;
                	move_uploaded_file ($_FILES['Attach']['tmp_name'], "tmp_attach/".$attachName);
                	FixHTAccess();
                }
                
                if (strlen($sEmailSubject.$sEmailMessage)) {

                    // User has edited a message.  Update mysql.                
                    $sSQLu = "UPDATE email_message_pending_emp ".
                             "SET emp_subject='" . EscapeString ($sEmailSubject) . "'," .
                             "    emp_message='". EscapeString($sEmailMessage) . "', " .
                             "    emp_attach_name='".$attachName."',".
                             "    emp_attach='".$hasAttach."' ".
                             "WHERE emp_usr_id='".$_SESSION['iUserID']."'";

                    RunQuery($sSQLu);

                } else {

                    // Retrieve subject and message from mysql

                    $rsPendingEmail = RunQuery($sSQL);
                    $aRow = mysqli_fetch_array($rsPendingEmail);
                    extract($aRow);

                    $sEmailSubject = $emp_subject;
                    $sEmailMessage = $emp_message;
                    $attachName = $emp_attach_name;
                }

                $sEmailForm = "sendoredit";                

            } else { 
                // This job has already started.  The user may not change the message
                // or the distribution once emails have actually been sent.
                $sEmailForm = 'resumeorabort';
            }


        } elseif (isset($email_array)) {

            // This user has no email messages stored mysql
			$sEmailSubject = "";
			$sEmailMessage = "";
			$hasAttach = 0;
			$attachName = "";
        	
			if (array_key_exists ('emailsubject', $_POST))
	            $sEmailSubject = stripslashes($_POST['emailsubject']);
			if (array_key_exists ('emailmessage', $_POST))
	            $sEmailMessage = stripslashes($_POST['emailmessage']);	            
            if (array_key_exists ('Attach', $_FILES)) {
	        	$attachName = $_FILES['Attach']['name'];
	        	$hasAttach = 1;
                move_uploaded_file ($_FILES['Attach']['tmp_name'], "tmp_attach/".$attachName);
                FixHTAccess ();
	        }

            if (strlen($sEmailSubject.$sEmailMessage)) {

                // User has written a message.  Store it in mysql.
                // Since this is the first time use INSERT instead of UPDATE                
                $sSQL = "INSERT INTO email_message_pending_emp ".
                        "SET " . 
                            "emp_usr_id='" .$_SESSION['iUserID']. "',".
                            "emp_to_send='0'," .
                            "emp_subject='" . EscapeString($sEmailSubject) . "',".
                            "emp_message='" . EscapeString($sEmailMessage) . "',".
                            "emp_attach_name='" .$attachName . "',".
                			"emp_attach='".$hasAttach."'";

                RunQuery($sSQL);

                $sEmailForm = 'sendoredit';

            } else {

                // There is no pending message.  User may compose a new message.
                $sEmailForm = 'compose';

            }
        }

        if ($sEmailForm == 'compose') {

            echo '<form method="post" action="EmailEditor.php">'."\n";

            foreach ($email_array as $email_address) {
                // Add all address except the default
                // avoid sending to this address twice
                if ($email_address != $sToEmailAddress) {
                    echo '<input type="hidden" name="emaillist[]" value="' .
                                                            $email_address . '">';
                }
            }
            if ($sToEmailAddress) { // The default address gets the last email
            echo '<input type="hidden" name="emaillist[]" value="'.$sToEmailAddress.'">'."\n";
            }

            echo '<input type="submit" class="icButton" name="submit" '.
                 'value ="'.gettext("Compose Email").'">'."\n</form>";
            
        } elseif ($sEmailForm == 'sendoredit') {

            //Print the From, To, and Email List with the Subject and Message

            echo "\n</td></tr></table></div>\n";

            echo "<hr>\r\n";
            echo '<p class="MediumText"><b>'.gettext("From:").'</b> "'.$sFromName.'"';
            echo ' &lt;'.$sFromEmailAddress.'&gt;<br>'."\n";
            echo '<b>'.gettext("To (blind):").'</b> '.$bcc_list.'<br>'."\n";

            echo '<b>'.gettext("Subject:").'</b> '.htmlspecialchars($sEmailSubject).'<br>';
            
            if (strlen ($attachName) > 0) {
            	echo '<b>'.gettext("Attach file:").'</b> '.htmlspecialchars($attachName).'<br>';
            }

            echo '</p><hr><textarea cols="72" rows="20" readonly class="MediumText" ';
            echo 'style="border:0px;">'. htmlspecialchars($sEmailMessage) . '</textarea><br>';
            echo "<hr>\n";

            // Create button to edit this message.
            echo '<div align="center"><table><tr><td>'."\n";
            echo '<form method="post" action="EmailEditor.php">'."\n";

            foreach ($email_array as $email_address) {
                // Add all address except the default
                // avoid sending to this address twice
                if ($email_address != $sToEmailAddress) {
                    echo '<input type="hidden" name="emaillist[]" value="' .
                                                            $email_address . '">';
                }
            }
            if ($sToEmailAddress) {  // The default address gets the last email
            echo '<input type="hidden" name="emaillist[]" value="'.$sToEmailAddress.'">'."\n";
            }

            echo '<input type="hidden" name="mysql" value="true">'."\n";

            echo '<input type="submit" class="icButton" name="submit" '.
                     'value ="'.gettext("Edit Email").'">'."\n</form>";

            // Create button to send this message
            echo "</td>\n<td>";

            echo '<form method="post" action="EmailSend.php">'."\n";

            foreach ($email_array as $email_address) {
                // Add all address except the default
                // avoid sending to this address twice
                if ($email_address != $sToEmailAddress) {
                    echo '<input type="hidden" name="emaillist[]" value="' .
                                                            $email_address . '">';
                }
            }
            if ($sToEmailAddress) { // The default address gets the last email
            echo '<input type="hidden" name="emaillist[]" value="'.$sToEmailAddress.'">'."\n";
            }

            echo '<input type="hidden" name="mysql" value="true">'."\n";
            echo '<input type="submit" class="icButton" name="submit" '.
                     'value ="'.gettext("Send Email").'">'."\n</form>";
            // Create button to Delete this message
            echo "</td>\n<td>";
            echo '<form method="post" action="CartView.php">'."\n";
            echo '<input type="hidden" name="rmEmail" value="true">'."\n";
            echo '<input type="submit" class="icButton" name="rmEail" '.
                 'value ="'.gettext("Delete Email").'">'."\n</form>";

        } elseif ($sEmailForm == 'resumeorabort') {

            // The user has two choices
            echo "<table>\n<tr><td>";
            echo 'The previous email did not succesfully complete. You may';
            echo "</td></tr>\n<tr><td>";
            echo '1 Resume at point of failure (no duplicates will be sent)';
            echo "</td></tr>\n<tr><td>";
            echo '2 Abort (discard everything)';
            echo "</td></tr>\n<tr><td>";
            echo '3 View Log';
            echo "</td></tr>\n</table>";

            // Create button to resume this job.
            echo '<div align="center"><table><tr><td>'."\n";
            echo '<form method="post" action="EmailSend.php">'."\n";

            echo '<input type="hidden" name="resume" value="true">'."\n";

            echo '<input type="submit" class="icButton" name="submit" '.
                     'value ="'.gettext("Resume").'">'."\n</form>";


            // Create button to abort
            echo "</td>\n<td>";

            echo '<form method="post" action="EmailSend.php">'."\n";

            // The default address gets the last email
            echo '<input type="hidden" name="abort" value="true">'."\n";

            echo '<input type="submit" class="icButton" name="submit" '.
                     'value ="'.gettext("Abort").'">'."\n</form>";

            // Create button to view log
            echo "</td>\n<td>";

            echo '<form method="post" action="EmailSend.php">'."\n";

            // The default address gets the last email
            echo '<input type="hidden" name="viewlog" value="true">'."\n";

            echo '<input type="submit" class="icButton" name="submit" '.
                     'value ="'.gettext("View Log").'">'."\n</form>";



        } else  { // ($sEmailForm == 'viewjobstatus')
            //echo '<br>job status form goes here<br>';
            echo "<br><br>";
            echo "It has been $tTimeSinceLastAttempt seconds since the last email ";
            echo "was attempted<br>\n";
            echo "$iWaitTime seconds must elapse before sending another email.<br>\n";

            $iComeBack = $iWaitTime - $tTimeSinceLastAttempt;
            echo "Refresh this page in $iComeBack seconds.<br>\n";

            $sSQL = 'SELECT * FROM email_job_log_'.$_SESSION['iUserID'].' '.
                    'ORDER BY ejl_id';

            $rsEJL = RunQuery($sSQL, FALSE); // FALSE means do not stop on error
            $sError = MySQLError ();

            if ($sError) {
                echo '<br>'.$sError;
                echo '<br>'.$sSQL;

            } else {
                $sHTMLLog = '<br><br><div align="center"><table>';            
                while ($aRow = mysqli_fetch_array($rsEJL)) {
                    extract($aRow);

                    $sTime = date('i:s', intval($ejl_time)).'.';
                    $sTime .= substr($ejl_usec,0,3);
                    $sMsg = stripslashes($ejl_text);
                    $sHTMLLog .= '<tr><td>'.$sTime.'</td><td>'.$sMsg.'</td></tr>'."\n";
                }
                $sHTMLLog .= '</table></div>';
                echo $sHTMLLog;
            }

        }
        echo '<a name="email"></a>'; // anchor used by EmailEditor.php
        echo "</td></tr></table></div>\n";
    }
}

require "Include/Footer.php";

function rmEmail()
{
        $iUserID = $_SESSION['iUserID']; // Read into local variable for faster access
        // Delete message from emp
    $sSQL = "DELETE FROM email_message_pending_emp ".
            "WHERE emp_usr_id='$iUserID'";
    RunQuery($sSQL);

    // Delete recipients from erp (not really needed, this should have already happened)
    // (no harm in trying again)
    $sSQL = "DELETE FROM email_recipient_pending_erp ".
            "WHERE erp_usr_id='$iUserID'";
    RunQuery($sSQL);
        echo '<font class="SmallError">Deleted Email message succesfuly</font>';
}

?>
