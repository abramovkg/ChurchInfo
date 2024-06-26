<?php
/*******************************************************************************
 *
 *  filename    : CartToGroup.php
 *  last change : 2003-06-23
 *  description : Add cart records to a group
 *
 *  http://www.infocentral.org/
 *  Copyright 2001-2003 Phillip Hullquist, Deane Barker, Chris Gebhardt
 *
 *  InfoCentral is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 ******************************************************************************/

// Include the function library
require "Include/Config.php";
require "Include/Functions.php";

// Security: User must have Manage Groups & Roles permission
if (!$_SESSION['bManageGroups'])
{
	Redirect("Menu.php");
	exit;
}

// Was the form submitted?
if (isset($_POST["Submit"]) && count($_SESSION['aPeopleCart']) > 0) {

	// Get the GroupID
	$iGroupID = FilterInput($_POST["GroupID"],'int');
	if (array_key_exists ("GroupRole", $_POST))
		$iGroupRole = FilterInput($_POST["GroupRole"],'int');
	else
		$iGroupRole = 0;

	// Loop through the session array
	$iCount = 0;
	foreach ($_SESSION['aPeopleCart'] as $per2grp) {
		AddToGroup($per2grp,$iGroupID,$iGroupRole);
		$iCount += 1;
	}

	$sGlobalMessage = $iCount . " records(s) successfully added to selected Group.";

	Redirect("GroupView.php?GroupID=" . $iGroupID . "&Action=EmptyCart");

}

// Get all the groups
$sSQL = "SELECT * FROM group_grp ORDER BY grp_Name";
$rsGroups = RunQuery($sSQL);

// Set the page title and include HTML header
$sPageTitle = gettext("Add Cart to Group");
require "Include/Header.php";

if (count($_SESSION['aPeopleCart']) > 0)
{
?>

<script type="text/javascript">
var IFrameObj; // our IFrame object

function UpdateRoles()
{
	var group_ID = document.getElementById('GroupID').value;
	if (!document.createElement) {return true};
	var IFrameDoc;
	var URL = 'RPCdummy.php?mode=GroupRolesSelect&data=' + group_ID;
	if (!IFrameObj && document.createElement) {
		var tempIFrame=document.createElement('iframe');
		tempIFrame.setAttribute('id','RSIFrame');
		tempIFrame.style.border='0px';
		tempIFrame.style.width='0px';
		tempIFrame.style.height='0px';
		IFrameObj = document.body.appendChild(tempIFrame);

		if (document.frames) {
			// For IE5 Mac
			IFrameObj = document.frames['RSIFrame'];
		}
	}

	if (navigator.userAgent.indexOf('Gecko') !=-1
		&& !IFrameObj.contentDocument) {
		// For NS6
		setTimeout('AddToCart()',10);
		return false;
	}

	if (IFrameObj.contentDocument) {
		// For NS6
		IFrameDoc = IFrameObj.contentDocument;
	} else if (IFrameObj.contentWindow) {
		// For IE5.5 and IE6
		IFrameDoc = IFrameObj.contentWindow.document;
	} else if (IFrameObj.document) {
		// For IE5
		IFrameDoc = IFrameObj.document;
	} else {
		return true;
	}

	IFrameDoc.location.replace(URL);
	return false;
}

function updateGroupRoles(generated_html)
{
	if (generated_html == "invalid") {
		document.getElementById('GroupRoles').innerHTML = '<p class="LargeError"><?php echo gettext("Invalid Group or No Roles Available!"); ?><p>';
	} else {
		document.getElementById('GroupRoles').innerHTML = generated_html;
	}
}
</script>

<p align="center"><?php echo gettext("Select the group to which you would like to add your cart:"); ?></p>
<form method="post">
<table align="center">
	<tr>
		<td class="LabelColumn"><?php echo gettext("Select Group:"); ?></td>
		<td class="TextColumn">
			<?php
			// Create the group select drop-down
			echo "<select id=\"GroupID\" name=\"GroupID\" onChange=\"UpdateRoles();\"><option value=\"0\">" . gettext("None") . "</option>";
			while ($aRow = mysqli_fetch_array($rsGroups)) {
				extract($aRow);
				echo "<option value=\"" . $grp_ID . "\">" . $grp_Name . "</option>";
			}
			echo "</select>";
			?>
		</td>
	</tr>
	<tr>
		<td class="LabelColumn"><?php echo gettext("Select Role:"); ?></td>
		<td class="TextColumn"><span id="GroupRoles"><?php echo gettext("No Group Selected"); ?></span></td>
	</tr>
</table>
<p align="center">
<BR>
<input type="submit" class="icButton" name="Submit" value=<?php echo '"' . gettext("Add to Group") . '"'; ?>>
<BR><BR>--<?php echo gettext("OR"); ?>--<BR><BR>
<a href="GroupEditor.php?EmptyCart=yes"><?php echo gettext("Create a New Group"); ?></a>
<BR><BR>
</p>
</form>
<?php
}
else
	echo "<p align=\"center\" class=\"LargeText\">" . gettext("Your cart is empty!") . "</p>";

require "Include/Footer.php";
?>
