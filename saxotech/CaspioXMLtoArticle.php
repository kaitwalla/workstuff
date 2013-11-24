<?
//Obviously this will be different depending on what type of information you're trying to get into Saxo, but this is a good starter template from taking Caspio XML data and importing it into Saxotech.

$adams = array('Bermudian Springs School District','Conewago Valley School District','Fairfield Area School District','Gettysburg Area School District','Littlestown Area School District','Upper Adams School District');

if ($_POST['saveIt']) {
	//Make sure it's an XML file
	if ($_FILES['xml']['type'] != 'text/xml') {
		echo 'Nope';
	}
	else {
		$xml = simplexml_load_file($_FILES['xml']['tmp_name']);
		// Object to store all the info in print-ready format
		$objPrinter = new stdClass();
		$table = $xml->Worksheet->Table;
		foreach ($table->Row as $row) {
			// Grab the school district code
			$tName = $row->Cell[0]->Data;
			//Skip the header fields
			if ($tName == 'school_district') {
				continue;
			}
			// Create an array for the district if not already avaialble
			if (!$objPrinter->$tName) {
				$objPrinter->$tName = array();
			}
			// Write the info into the district array
			$objPrinter->{$tName}[] = array('data'=>'• '.$row->Cell[1]->Data.', '.$row->Cell[2]->Data.': '.$row->Cell[3]->Data.' to '.$row->Cell[4]->Data.', '.$row->Cell[5]->Data,'district'=>$tName);
		}

		// Now we load in the premade story format
		$xml = simplexml_load_file('deedsblank.xml');
		$xml2 = $xml;

		// Add the filename for the various deeds files
		if ($_POST['date']) {
			$date = substr(str_replace('/', '', $_POST['date']),0,-4);
		}
		else {
			$date = new strtotime('Today');
		}
		$ydeeds = 'YDR-L-Deeds-York-'.$date;
		$adeeds = 'YDR-L-Deeds-Adams-'.$date;
		 
		//The filename formatting is wonky 
		$subj = $xml->head->addChild('meta','');
		$subj->addAttribute('name','subject');
		$subj->addAttribute('content', $ydeeds);
		//Kicker
		$xml->head->addChild('meta','York County Deed Transfers')->addAttribute('name','SAXo-Kicker');
		//Abstract
		$xml->body->{'body.head'}->abstract->addChild('p','York County Deed Transfers')->addAttribute('style','');

		$subj = $xml2->head->addChild('meta','');
		$subj->addAttribute('name','subject');
		$subj->addAttribute('content', $adeeds);
		$xml2->head->addChild('meta','Adams County Deed Transfers')->addAttribute('name','SAXo-Kicker');
		$xml2->body->{'body.head'}->abstract->addChild('p','Adams County Deed Transfers')->addAttribute('style','');	



		foreach ($objPrinter as $dist) {
			if (in_array($dist[0]['district'], $adams)) {
				// If it's Adams, put it in the Adams box.
				$x = 0;				
				foreach ($dist as $obj) {
					if ($x == 0) {
						$xml2->body->{'body.content'}->block->addChild('p',$obj['district'])->addAttribute('style','@@TX Subhead');
						$x = 1;
					}
					$xml2->body->{'body.content'}->block->addChild('p',preg_replace('/&/', '+', $obj['data']))->addAttribute('style','@@TX Calendar');		
				}
			}
			else {
				// Elsewise, York.
				$x = 0;				
				foreach ($dist as $obj) {
					if ($x == 0) {
						$xml->body->{'body.content'}->block->addChild('p',$obj['district'])->addAttribute('style','@@TX Subhead');
						$x = 1;
					}
					$xml->body->{'body.content'}->block->addChild('p',preg_replace('/&/', '+', $obj['data']))->addAttribute('style','@@TX Calendar');		
				}
			}
		}

		//Save that ish
		$xml->asXml($ydeeds.'.xml');
		$xml2->asXml($adeeds.'.xml');
		

		//Release it into the wilderness of Saxo via FTP
		$conn = ftp_connect($url); //Insert Saxo FTP URL
		$login = ftp_login($conn,$usr,$pass); //Connection info
		if (!$conn || !$login) { die('Connection attempt is a fail');};
		if ($upload = ftp_put($conn, '/Inputs/XML/'.$ydeeds.'.xml',$ydeeds.'.xml', FTP_ASCII)) {
			echo 'York worked';
		}
		if ($upload = ftp_put($conn, '/Inputs/XML/'.$adeeds.'.xml',$adeeds.'.xml', FTP_ASCII)) {
			echo 'Adams worked.';
		}
		ftp_close($conn);
	}
}

?>