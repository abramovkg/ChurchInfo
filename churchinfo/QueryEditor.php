<?php
/*******************************************************************************
 *
 *  filename    : QueryEditor.php
 *  last change : 2017-05-21  Matthew Girouard
 *
 ******************************************************************************/

//Include the function library
require "Include/Config.php";
require "Include/Functions.php";

//Set the page title
$sPageTitle = gettext("Query Editor");

require "Include/Header.php";

// Security: User must be an Admin to access this page.  It allows unrestricted database access!
// Otherwise, re-direct them to the main menu.
if (!$_SESSION['bAdmin'])
{
	Redirect("Menu.php");
	exit;
}

//Get the QueryID out of the querystring
if (array_key_exists ("QueryID", $_GET))
	$iQueryID = FilterInput($_GET["QueryID"],'int');
else
	$iQueryID = 0;

//Get the Action out of the querystring
if (array_key_exists ("Action", $_GET)) 
	$iAction = FilterInput($_GET["Action"],'char'); 
else 
	$iAction = 0; 

$sAction = FilterInput($_GET["Action"]); 

$sClass = "";
$sNameError = "";
$sDescriptionError = ""; 
$sSqlCodeError = ""; 
$bError = false;

// If the form is being submitted, then submit 
// Else if the page was loaded with existing query (Edit or Delete mode), then prepopulate the fields.  If in Delete mode, the fields will be disabled. 
// Else if the page was loaded without existing values (Add mode), then do not prepopulate the fields
if (isset($_POST["Submit"]))
{
	$sName = FilterInput($_POST["Name"]);
	$sDescription = FilterInput($_POST["Description"]);
	$sSqlCode = FilterInput($_POST["SqlCode"]);

	//Error checking.  Removed the error checking for Delete because the disabled field values are not passed into the form. 
    if ($sAction != "Delete")
	{
		if (strlen($sName) < 1)
		{
			$sNameError = "<font color=\"red\">" . gettext("You must enter a name") . "</font>";
			$bError = True;
		}

		//Error checking
		if (strlen($sDescription) < 1 )
		{
			$sDescriptionError = "<font color=\"red\">" . gettext("You must enter a description") . "</font>";
			$bError = True;
		}

		//Error checking 
		if (strlen($sSqlCode) < 1 )
		{
			$sSqlCodeError = "<font color=\"red\">" . gettext("SQL Code cannot be blank") . "</font>";
			$bError = True;
		}
	}

	//If no errors, let's update
	if (!$bError)
	{
		//Vary the SQL based on Action
		if ($sAction=="Create" && $iQueryID=="")
		{
			$sSQL = "INSERT INTO query_qry (qry_Name,qry_Description,qry_SQL) VALUES ('" . $sName . "','" . $sDescription . "','" . $sSqlCode . "')"; 
		}
		elseif ($sAction=="Edit")
		{
			$sSQL = "UPDATE query_qry SET qry_Name = '" . $sName . "', qry_Description = '" . $sDescription . "', qry_SQL = '" . $sSqlCode . "' where qry_ID = " . $iQueryID; 
		}
		elseif ($sAction=="Delete")
		{
			$sSQL = "DELETE FROM query_qry WHERE qry_ID = " . $iQueryID ; 
		}
		else
		{
			echo "The action failed!";
		}

		//Execute the SQL
		RunQuery($sSQL);

		//Route back to the list
		Redirect("QueryList.php");
	}
} 
// Launching in Edit Mode 
elseif ($iQueryID > 0) {
	//Get the data on this property
	$sSQL = "select * from query_qry where qry_ID = " . $iQueryID; 
	$rsProperty = mysqli_fetch_array(RunQuery($sSQL));
	extract($rsProperty);

	//Assign values locally
	$sName = $qry_Name;
	$sDescription = $qry_Description;
	$sSqlCode = $qry_SQL;
} 
// Launching in Add Mode 
else {
	$sName = "";
	$sDescription = "";
	$sClass = "";
}
?> 

<form method="post" action="QueryEditor.php?Action=<?php echo $sAction; ?>&amp;QueryID=<?php echo $iQueryID; ?>">

<table cellpadding="4">
	<tr>
		<td align="right"><b><?php echo gettext("Name:"); ?></b></td>
		<td><input type="text" name="Name" value="<?php echo htmlentities(stripslashes($sName),ENT_NOQUOTES, "UTF-8"); ?>" size="40" <?php if($sAction=="Delete") echo 'disabled';?> > 			<?php echo $sNameError; ?>
		</td> 
	</tr>
	<tr>
		<td align="right"><b><?php echo gettext("Description:"); ?></b></td>
		<td><input type="text" name="Description" value="<?php echo htmlentities(stripslashes($sDescription),ENT_NOQUOTES, "UTF-8"); ?>" size="40" <?php if($sAction=="Delete") echo 'disabled';?> > 			<?php echo $sDescriptionError; ?>
		</td> 
	</tr>
	<tr>
		<td align="right" valign="top"><b><?php echo gettext("SQL Code:"); ?></b></td>
		<td><textarea name="SqlCode" cols="60" rows="10" <?php if($sAction=="Delete") echo 'disabled';?>><?php echo htmlentities(stripslashes($sSqlCode),ENT_NOQUOTES, "UTF-8"); ?></textarea>    <?php echo $sSqlCodeError; ?> 
        </td> 
	</tr>
	<tr>	
		<td colspan="2" align="center">
            <?php if($sAction=="Delete") 
				echo '<strong>This will be permanently deleted!</strong> 
				<p> 
				<input type="submit" class="icButton" name="Submit" value="' . gettext("Delete") . '">' ; ?> &nbsp;
			<?php if($sAction=="Create" || $sAction=="Edit") 
				echo '<input type="submit" class="icButton" name="Submit" value="' . gettext("Save") . '">'; ?> &nbsp;
			<input type="button" class="icButton" name="Cancel" <?php echo 'value="' . gettext("Cancel") . '"'; ?> onclick="document.location='QueryList.php';">
		</td>
	</tr>
</table>

</form>

<?php
require "Include/Footer.php";
?>

