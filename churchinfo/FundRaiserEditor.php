<?php
/*******************************************************************************
 *
 *  filename    : FundRaiserEditor.php
 *  last change : 2009-04-15
 *  website     : http://www.churchdb.org
 *  copyright   : Copyright 2009 Michael Wilt
 *
 *  ChurchInfo is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 ******************************************************************************/

//Include the function library
require "Include/Config.php";
require "Include/Functions.php";

$linkBack = FilterInputArr($_GET,"linkBack");
$iFundRaiserID = FilterInputArr($_GET,"FundRaiserID", 'int');

if ($iFundRaiserID>0) {
	// Get the current fund raiser record
	$sSQL = "SELECT * from fundraiser_fr WHERE fr_ID = " . $iFundRaiserID;
	$rsFRR = RunQuery($sSQL);
	extract(mysqli_fetch_array($rsFRR));
	// Set current fundraiser
	$_SESSION['iCurrentFundraiser'] = $iFundRaiserID;
}

if ($iFundRaiserID > 0) {
	$sPageTitle = gettext("Fund Raiser #") . $iFundRaiserID . " " . $fr_title;
} else {
	$sPageTitle = gettext("Create New Fund Raiser");
}

$sDateError = "";

//Is this the second pass?
if (isset($_POST["FundRaiserSubmit"]))
{
	//Get all the variables from the request object and assign them locally
	$dDate = FilterInputArr($_POST,"Date");
	$sTitle = FilterInputArr($_POST,"Title");
	$sDescription = FilterInputArr($_POST,"Description");
	
	//Initialize the error flag
	$bErrorFlag = false;

	// Validate Date
	if (strlen($dDate) > 0)
	{
		list($iYear, $iMonth, $iDay) = sscanf($dDate,"%04d-%02d-%02d");
		if ( !checkdate($iMonth,$iDay,$iYear) )
		{
			$sDateError = "<span style=\"color: red; \">" . gettext("Not a valid Date") . "</span>";
			$bErrorFlag = true;
		}
	}

	//If no errors, then let's update...
	if (!$bErrorFlag)
	{
		// New deposit slip
		if ($iFundRaiserID <= 0)
		{
			$sSQL = "INSERT INTO fundraiser_fr (fr_date, fr_title, fr_description, fr_EnteredBy, fr_EnteredDate) VALUES (".
			"'" . $dDate . "','" . $sTitle . "','" . $sDescription . "'," . $_SESSION['iUserID'] . ",'" . date("YmdHis") . "')";
			$bGetKeyBack = True;
		// Existing record (update)
		} else {
			$sSQL = "UPDATE fundraiser_fr SET fr_date = '" . $dDate . "', fr_title = '" . $sTitle . "', fr_description = '" . $sDescription . "', fr_EnteredBy = ". $_SESSION['iUserID'] . ", fr_EnteredDate='" . date("YmdHis") . "' WHERE fr_ID = " . $iFundRaiserID . ";";
			$bGetKeyBack = false;
		}
		//Execute the SQL
		RunQuery($sSQL);

		// If this is a new fundraiser, get the key back
		if ($bGetKeyBack)
		{
			$sSQL = "SELECT MAX(fr_ID) AS iFundRaiserID FROM fundraiser_fr";
			$rsFundRaiserID = RunQuery($sSQL);
			extract(mysqli_fetch_array($rsFundRaiserID));
			$_SESSION['iCurrentFundraiser'] = $iFundRaiserID;
		}

		if (isset($_POST["FundRaiserSubmit"]))
		{
			if ($linkBack != "") {
				Redirect($linkBack);
			} else {
				//Send to the view of this FundRaiser
				Redirect("FundRaiserEditor.php?linkBack=" . $linkBack . "&FundRaiserID=" . $iFundRaiserID);
			}
		}
	}
} else {

	//FirstPass
	//Are we editing or adding?
	if ($iFundRaiserID>0)
	{
		//Editing....
		//Get all the data on this record
																		
		$sSQL = "SELECT * FROM fundraiser_fr WHERE fr_ID = " . $iFundRaiserID;
		$rsFundRaiser = RunQuery($sSQL);
		extract(mysqli_fetch_array($rsFundRaiser));

		$dDate = $fr_date;
		$sTitle = $fr_title;
		$sDescription = $fr_description;
	}
	else
	{
		$dDate = "";
		$sTitle = "";
		$sDescription = "";
	}
}

if ($iFundRaiserID > 0) {
	//Get the items for this fundraiser
	$sSQL = "SELECT di_ID, di_Item, di_multibuy,
	                a.per_FirstName as donorFirstName, a.per_LastName as donorLastName,
	                b.per_FirstName as buyerFirstName, b.per_LastName as buyerLastName,
	                di_title, di_sellprice, di_estprice, di_materialvalue, di_minimum
	         FROM donateditem_di
	         LEFT JOIN person_per a ON di_donor_ID=a.per_ID
	         LEFT JOIN person_per b ON di_buyer_ID=b.per_ID
	         WHERE di_FR_ID = '" . $iFundRaiserID . "' ORDER BY di_multibuy,substr(di_item,1,1),cast(substr(di_item,2) as unsigned integer),substr(di_item,4)"; 
	 $rsDonatedItems = RunQuery($sSQL);
} else {
	$rsDonatedItems = 0;
	$dDate = date("Y-m-d");	// Set default date to today
}

// Set Current Deposit setting for user
if ($iFundRaiserID > 0) {
	$_SESSION['iCurrentFundraiser'] = $iFundRaiserID;		// Probably redundant
//	$sSQL = "UPDATE user_usr SET usr_currentDeposit = '$iFundRaiserID' WHERE usr_per_id = \"".$_SESSION['iUserID']."\"";
//	$rsUpdate = RunQuery($sSQL);
}

require "Include/Header.php";

?>

<form method="post" action="FundRaiserEditor.php?<?php echo "linkBack=" . $linkBack . "&FundRaiserID=".$iFundRaiserID?>" name="FundRaiserEditor">

<table cellpadding="3" align="center">

	<tr>
		<td align="center">
		<input type="submit" class="icButton" value="<?php echo gettext("Save"); ?>" name="FundRaiserSubmit">
			<input type="button" class="icButton" value="<?php echo gettext("Cancel"); ?>" name="FundRaiserCancel" onclick="javascript:document.location='<?php if (strlen($linkBack) > 0) { echo $linkBack; } else {echo "Menu.php"; } ?>';">
			<?php
				if ($iFundRaiserID > 0) {
					echo "<input type=button class=icButton value=\"".gettext("Add Donated Item")."\" name=AddDonatedItem onclick=\"javascript:document.location='DonatedItemEditor.php?CurrentFundraiser=$iFundRaiserID&linkBack=FundRaiserEditor.php?FundRaiserID=$iFundRaiserID&CurrentFundraiser=$iFundRaiserID';\">\n";
					echo "<input type=button class=icButton value=\"".gettext("Generate Catalog")."\" name=GenerateCatalog onclick=\"javascript:document.location='Reports/FRCatalog.php?CurrentFundraiser=$iFundRaiserID';\">\n";
					echo "<input type=button class=icButton value=\"".gettext("Generate Bid Sheets")."\" name=GenerateBidSheets onclick=\"javascript:document.location='Reports/FRBidSheets.php?CurrentFundraiser=$iFundRaiserID';\">\n";
					echo "<input type=button class=icButton value=\"".gettext("Generate Certificates")."\" name=GenerateCertificates onclick=\"javascript:document.location='Reports/FRCertificates.php?CurrentFundraiser=$iFundRaiserID';\">\n";
					echo "<input type=button class=icButton value=\"".gettext("Batch Winner Entry")."\" name=BatchWinnerEntry onclick=\"javascript:document.location='BatchWinnerEntry.php?CurrentFundraiser=$iFundRaiserID&linkBack=FundRaiserEditor.php?FundRaiserID=$iFundRaiserID&CurrentFundraiser=$iFundRaiserID';\">\n";
				}
			?>
		</td>
	</tr>

	<tr>
		<td>
		<table cellpadding="3">
			<tr>
				<td class="LabelColumn"<?php addToolTip("Format: YYYY-MM-DD<br>or enter the date by clicking on the calendar icon to the right."); ?>><?php echo gettext("Date:"); ?></td>
				<td class="TextColumn"><input type="text" name="Date" value="<?php echo $dDate; ?>" maxlength="10" id="sel1" size="11">&nbsp;<input type="image" onclick="return showCalendar('sel1', 'y-mm-dd');" src="Images/calendar.gif"> <span class="SmallText"><?php echo gettext("[format: YYYY-MM-DD]"); ?></span><font color="red"><?php echo $sDateError ?></font></td>
			</tr>
			
			<tr>
				<td class="LabelColumn"><?php echo gettext("Title:"); ?></td>
				<td class="TextColumn"><input type="text" name="Title" id="Title" value="<?php echo $sTitle; ?>"></td>
			</tr>

			<tr>
				<td class="LabelColumn"><?php echo gettext("Description:"); ?></td>
				<td class="TextColumn"><input type="text" name="Description" id="Description" value="<?php echo $sDescription; ?>"></td>
			</tr>
		</table>
		</td>
	</form>
</table>

<br>

<b><?php echo gettext("Donated items for this fundraiser:"); ?></b>
<br>

<table cellpadding="5" cellspacing="0" width="100%">

<tr class="TableHeader">
	<td><?php echo gettext("Item"); ?></td>
	<td><?php echo gettext("Multiple"); ?></td>	
	<td><?php echo gettext("Donor"); ?></td>
	<td><?php echo gettext("Buyer"); ?></td>
	<td><?php echo gettext("Title"); ?></td>
	<td><?php echo gettext("Sale Price"); ?></td>
	<td><?php echo gettext("Est Value"); ?></td>
	<td><?php echo gettext("Material Value"); ?></td>
	<td><?php echo gettext("Minimum Price"); ?></td>
	<td><?php echo gettext("Delete"); ?></td>
</tr>

<?php
$tog = 0;

//Loop through all donated items
if ($iFundRaiserID > 0 && $rsDonatedItems->num_rows != 0) {
	while ($aRow = mysqli_fetch_array($rsDonatedItems))
	{
		extract($aRow);
		
		if ($di_Item == "") {
			$di_Item = "~";
		}
	
		$sRowClass = "RowColorA";
	?>
		<tr class="<?php echo $sRowClass ?>">
			<td>
				<a href="DonatedItemEditor.php?DonatedItemID=<?php echo $di_ID . "&linkBack=FundRaiserEditor.php?FundRaiserID=" . $iFundRaiserID;?>"><?php echo $di_Item;?></a>
			</td>
			<td>
				<?php if ($di_multibuy) echo "X";?>&nbsp;
			</td>
			<td>
				<?php echo $donorFirstName . " " . $donorLastName ?>&nbsp;
			</td>
			<td>
				<?php if ($di_multibuy) echo gettext ("Multiple"); else echo $buyerFirstName . " " . $buyerLastName ?>&nbsp;
			</td>
			<td>
				<?php echo $di_title ?>&nbsp;
			</td>
			<td align=center>
				<?php echo $di_sellprice ?>&nbsp;
			</td>
			<td align=center>
				<?php echo $di_estprice ?>&nbsp;
			</td>
			<td align=center>
				<?php echo $di_materialvalue ?>&nbsp;
			</td>
			<td align=center>
				<?php echo $di_minimum ?>&nbsp;
			</td>
			<td>
				<a href="DonatedItemDelete.php?DonatedItemID=<?php echo $di_ID . "&linkBack=FundRaiserEditor.php?FundRaiserID=" . $iFundRaiserID;?>">Delete</a>
			</td>
		</tr>
	<?php
	} // while
}// if
?>

</table>

<?php
require "Include/Footer.php";
?>
