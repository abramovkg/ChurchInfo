<?php
/*******************************************************************************
 *
 *  filename    : NoteEditor.php
 *  last change : 2003-01-07
 *  website     : http://www.infocentral.org
 *  copyright   : Copyright 2001, 2002 Deane Barker
 *
 *  InfoCentral is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 ******************************************************************************/

//Include the function library
require "Include/Config.php";
require "Include/Functions.php";

// Security: User must have Notes permission
// Otherwise, re-direct them to the main menu.
if (!$_SESSION['bNotes'])
{
	Redirect("Menu.php");
	exit;
}

//Set the page title
$sPageTitle = gettext("Add Contact or Pastoral Visit Note");

if (isset($_GET["PersonID"]))
	$iPersonID = FilterInput($_GET["PersonID"],'int');
else
	$iPersonID = 0;

if (isset($_GET["FamilyID"]))
	$iFamilyID = FilterInput($_GET["FamilyID"],'int');
else
	$iFamilyID = 0;

//To which page do we send the user if they cancel?
if ($iPersonID > 0)
{
	$sBackPage = "PersonView.php?PersonID=" . $iPersonID;
}
else
{
	$sBackPage = "FamilyView.php?FamilyID=" . $iFamilyID;
}

//Has the form been submitted?
if (isset($_POST["Submit"]))
{
	//Initialize the ErrorFlag
	$bErrorFlag = false;

	//Assign all variables locally
	$iNoteID = FilterInput($_POST["NoteID"],'int');
	$sNoteText = FilterInput($_POST["NoteText"],'htmltext');

	//If they didn't check the private box, set the value to 0
	if (isset($_POST["Private"]))
		$bPrivate = 1;
	else
		$bPrivate = 0;

	//Did they enter text for the note?
	if ($sNoteText == "")
	{
		$sNoteTextError = "<br><span style=\"color: red;\">You must enter text for this note.</span>";
		$bErrorFlag = True;
	}
	//Were there any errors?
	if (!$bErrorFlag)
	{
		//Are we adding or editing?
		if ($iNoteID <= 0)
		{
			$sSQL = "INSERT INTO note_nte (nte_per_ID, nte_fam_ID, nte_Private, nte_Text, nte_EnteredBy, nte_DateEntered) VALUES (" . $iPersonID . "," . $iFamilyID . "," . $bPrivate . ",'" . $sNoteText . "'," . $_SESSION['iUserID'] . ",'" . date("YmdHis") . "')";
		}

		else
		{
			$sSQL = "UPDATE note_nte SET nte_Private = " . $bPrivate . ", nte_Text = '" . $sNoteText . "', nte_DateLastEdited = '" . date("YmdHis") . "', nte_EditedBy = " . $_SESSION['iUserID'] . " WHERE nte_ID = " . $iNoteID;
		}

		//Execute the SQL
		RunQuery($sSQL);

		//Send them back to whereever they came from
		Redirect($sBackPage);
	}
}

else
{
	//Are we adding or editing?
	if (isset($_GET["NoteID"]))
	{
		//Get the NoteID from the querystring
		$iNoteID = FilterInput($_GET["NoteID"],'int');

		//Get the data for this note
		$sSQL = "SELECT * FROM note_nte WHERE nte_ID = " . $iNoteID;
		$rsNote = RunQuery($sSQL);
		extract(mysqli_fetch_array($rsNote));

		//Assign everything locally
		$sNoteText = $nte_Text;
		$bPrivate = $nte_Private;
		$iPersonID = $nte_per_ID;
		$iFamilyID = $nte_fam_ID;
	}
}

require "Include/Header.php";

?>

<form method="post">

    <div class="fields-container">
	<input type="hidden" name="PersonID" value="<?php echo $iPersonID; ?>">
	<input type="hidden" name="FamilyID" value="<?php echo $iFamilyID; ?>">
	<input type="hidden" name="NoteID" value="<?php echo $iNoteID; ?>">

        <div class="form-group">
          <label for="contactDate">Contact Date</label>
          <input type="text" class="TextColumnWithBottomBorder" value="" maxlength="10" id="contactDate">&nbsp;<input type="image" onclick="return showCalendar('contactDate', 'y-mm-dd');" src="Images/calendar.gif">
        </div>

        <div class="form-group">
          <label for="type">Contact Type</label>
          <select id="type">
            <option value=""></option>
            <option value="In-person">In-person</option>
            <option value="Call">Call</option>
            <option value="Text">Text</option>
            <option value="Email">Email</option>
            <option value="Mail">Mail</option>
          </select>
        </div>

        <div class="form-group">
          <label for="purpose">Purpose</label>
          <select id="purpose">
            <option value=""></option>
            <option value="Visit">Visit</option>
            <option value="Counseling">Counseling</option>
            <option value="Notification">Notification</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="form-group visit-group">
          <label for="visitStatus">Visit Status</label>
          <select id="visitStatus">
            <option value=""></option>
            <option value="Offered">Offered</option>
            <option value="Scheduled">Scheduled</option>
            <option value="Occurred">Occurred</option>
            <option value="Postponed">Postponed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Declined">Declined</option>
          </select>
        </div>

        <div class="form-group visit-group">
          <label for="visitDate">Visit Date</label>
          <input type="text" class="TextColumnWithBottomBorder" value="" maxlength="10" id="visitDate">&nbsp;<input type="image" onclick="return showCalendar('visitDate', 'y-mm-dd');" src="Images/calendar.gif">
        </div>

        <div class="form-group">
          <label for="notes">Notes</label>
          <textarea name="notes" id="notes"></textarea>
        </div>

	<textarea style="display:none;" name="NoteText" cols="70" rows="10"><?php echo $sNoteText; ?></textarea>
	<?php echo $sNoteTextError; ?>
    </div>


<p align="center">
	<input type="checkbox" value="1" name="Private" <?php if ($nte_Private != 0) { echo "checked"; } ?>>&nbsp;<?php echo gettext("Private"); ?>
</p>

<p align="center">
	<input type="submit" class="icButton" name="Submit" <?php echo 'value="' . gettext("Save") . '"'; ?>>
	&nbsp;
	<input type="button" class="icButton" name="Cancel" <?php echo 'value="' . gettext("Cancel") . '"'; ?> onclick="javascript:document.location='<?php echo $sBackPage; ?>';">
</p>
	</form>


<style>
  .fields-container { margin:0 auto; width:100%; max-width:400px; text-align:left; }
  .form-group { margin-bottom: 1em; }
  label { display: inline-block; width: 100px; margin-bottom: .5em; }
  textarea { width: 100%; height: 120px; }
  select, input[type="text"] { width: 120px; }
  .visit-group { display: none; }
</style>

<script>
var save = document.getElementsByName('Submit')[0];
var wholeNote = document.getElementsByName('NoteText')[0];
var contactDate = document.getElementById('contactDate');
var type = document.getElementById('type');
var purpose = document.getElementById('purpose');
var visitStatus = document.getElementById('visitStatus');
var visitDate = document.getElementById('visitDate');
var notes = document.getElementById('notes');

function showHideGroup(groupClass,triggerElement,valueTrigger) {
  var group = document.getElementsByClassName(groupClass);
  if (triggerElement.value == valueTrigger) {
    for (i = 0; i < group.length; i++) {
      group[i].style.display = "block";
    }
  } else {
    for (i = 0; i < group.length; i++) {
      group[i].style.display = "none";
      group[i].children[1].value = "";
    }
  }
}

//update the wholeNote field with selected values before saving the note
function updateVisitNote() {
  var string = "";
  if(contactDate.value.length > 0) {
    string +=  '\n' + 'CONTACT DATE: ' + contactDate.value;
    }
  if(type.value.length > 0) {
    string +=  '\n' + 'TYPE: ' + type.value;
    }
  if(purpose.value.length > 0) {
    string +=  '\n' + 'PURPOSE: ' + purpose.value;
    }
  if(visitStatus.value.length > 0) {
    string +=  '\n' + 'VISIT STATUS: ' + visitStatus.value;
    }
  if(visitDate.value.length > 0) {
    string +=  '\n' + 'VISIT DATE: ' + visitDate.value;
    }
  if(notes.value.length > 0) {
    if(string.length == 0) {
       string = notes.value;
      } else {
      string +=  '\n' + 'NOTES: ' + notes.value;
      }
    }
  wholeNote.value = string;
  wholeNote.appendChild(document.createTextNode(string));
}

//retrieve individual note field value from wholeNote field, for editing of notes
function setValue(field,fieldTitle) {
  var noteArr = wholeNote.value.split('\n');
  for (i = 0; i < noteArr.length; i++) {
    if (noteArr[i].match('^' + fieldTitle)) {
      field.value = noteArr[i].match(fieldTitle + ': (.*)')[1];
    }
  }
}

//populate the form with values from the wholeNote field on form load
function populateOnEdit() {
 setValue(contactDate,'CONTACT DATE');
 setValue(type,'TYPE');
 setValue(purpose,'PURPOSE');
 showHideGroup('visit-group',purpose,'Visit');
 setValue(visitStatus,'VISIT STATUS');
 setValue(visitDate,'VISIT DATE');
 setValue(notes,'NOTES');
}

if(wholeNote.value.match(/(CONTACT DATE: |TYPE: |PURPOSE: |VISIT STATUS: |VISIT DATE: |NOTES)/) == null) {
  notes.value = wholeNote.value;
} else {
populateOnEdit();
}

purpose.addEventListener('change', function() {
  showHideGroup('visit-group',this,'Visit');
});
save.addEventListener('mouseover', updateVisitNote);
save.addEventListener('focus', updateVisitNote);
</script>

<?php
require "Include/Footer.php";
?>

