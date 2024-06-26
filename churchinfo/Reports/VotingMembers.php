<?php
/*******************************************************************************
*
*  filename    : Reports/VotingMembers.php
*  last change : 2005-03-26
*  description : Creates a PDF with names of voting members for a particular fiscal year
*
*  InfoCentral is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
******************************************************************************/

require "../Include/Config.php";
require "../Include/Functions.php";
require "../Include/ReportFunctions.php";
require "../Include/ReportConfig.php";

//Get the Fiscal Year ID out of the querystring
$iFYID = FilterInput($_POST["FYID"],'int');
$_SESSION['idefaultFY'] = $iFYID; // Remember the chosen FYID
$iRequireDonationYears = FilterInput($_POST["RequireDonationYears"],'int');
$output = FilterInput($_POST["output"]);

class PDF_VotingMembers extends ChurchInfoReport {

	// Constructor
    function __construct () {
		parent::__construct("P", "mm", $this->paperFormat);

		$this->SetFont("Times",'',10);
		$this->SetMargins(20,20);

		$this->SetAutoPageBreak(false);
		$this->AddPage();
	}
}

$pdf = new PDF_VotingMembers();

// Read in report settings from database
$rsConfig = mysqli_query($cnChurchInfo, "SELECT cfg_name, IFNULL(cfg_value, cfg_default) AS value FROM config_cfg WHERE cfg_section='ChurchInfoReport'");
if ($rsConfig) {
	while (list($cfg_name, $cfg_value) = mysqli_fetch_row($rsConfig)) {
		$pdf->$cfg_name = $cfg_value;
	}
}

$topY = 10;
$curY = $topY;

$pdf->WriteAt ($pdf->leftX, $curY, (gettext ("Voting members ") . MakeFYString ($iFYID)));
$curY += 10;

$votingMemberCount = 0;

// Get all the families
$sSQL = "SELECT fam_ID, fam_Name FROM family_fam WHERE 1 ORDER BY fam_Name";
$rsFamilies = RunQuery($sSQL);

// Loop through families
while ($aFam = mysqli_fetch_array($rsFamilies)) {
	extract ($aFam);
	
	// Get pledge date ranges
	$donation = "no";
	if ($iRequireDonationYears > 0) {
		$startdate = $iFYID + 1995 - $iRequireDonationYears;
		$startdate .= "-" . $iFYMonth . "-" . "01";
		$enddate = $iFYID + 1995 + 1;
		$enddate .= "-" . $iFYMonth . "-" . "01";
		
		// Get payments only
		$sSQL = "SELECT COUNT(plg_plgID) AS count FROM pledge_plg
			WHERE plg_FamID = " . $fam_ID . " AND plg_PledgeOrPayment = 'Payment' AND
				 plg_date >= '$startdate' AND plg_date < '$enddate'";
		$rsPledges = RunQuery($sSQL);
		list ($count) = mysqli_fetch_row($rsPledges);
		if ($count > 0)
			$donation = "yes";
	}
		
	if (($iRequireDonationYears==0) || $donation == "yes") {

		$pdf->WriteAt ($pdf->leftX, $curY, $fam_Name);

		//Get the family members for this family
		$sSQL = "SELECT per_FirstName, per_LastName, cls.lst_OptionName AS sClassName
				FROM person_per
				INNER JOIN list_lst cls ON per_cls_ID = cls.lst_OptionID AND cls.lst_ID = 1
				WHERE per_fam_ID = " . $fam_ID . " AND cls.lst_OptionName='" . gettext ("Member") . "'";
				
		$rsFamilyMembers = RunQuery($sSQL);

		if (mysqli_num_rows($rsFamilyMembers) == 0)
			$curY += 5;

		while ($aMember = mysqli_fetch_array($rsFamilyMembers)) {
			extract ($aMember);
			$pdf->WriteAt ($pdf->leftX + 30, $curY, ($per_FirstName . " " . $per_LastName));
			$curY += 5;
			if ($curY > 245) {
				$pdf->AddPage();
				$curY = $topY;
			}
			$votingMemberCount += 1;
		}
		if ($curY > 245) {
			$pdf->AddPage();
			$curY = $topY;
		}
	}
}

$curY += 5;
$pdf->WriteAt ($pdf->leftX, $curY, "Number of Voting Members: " . $votingMemberCount);

header('Pragma: public');  // Needed for IE when using a shared SSL certificate
if ($iPDFOutputType == 1)
	$pdf->Output("VotingMembers" . date("Ymd") . ".pdf", "D");
else
	$pdf->Output();	
?>
