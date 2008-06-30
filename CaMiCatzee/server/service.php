<?php
			include_once("common.php");
			include_once("foaf-module.php");
			include_once("flickr-module.php");
			include_once("sindice-module.php");

/************************************************************************************************/
/* HTTP GET interface: admin operations                                                         */

if(isset($_GET['reset'])) {
		$store->reset();
		echo "store has been reseted, master\n";
}

if(isset($_GET['init'])) {
		// TODO: init with defaults
		echo "store has been initiated, master\n";
}

/************************************************************************************************/
/* HTTP GET interface: atomic operations                                                        */

/* list all person depictions */
if(isset($_GET['dumpInfo'])) {
		echo dumpAllData();				
}

/* find FOAF documents where the URI is contained */
if(isset($_GET['findFOAFfromURI'])){ 
	$entity = $_GET['findFOAFfromURI']; 			
	echo lookupURIInSindice($entity);
}

/* find FOAF documents where the name is contained */
if(isset($_GET['findFOAFfromName'])){ 
	$entity = $_GET['findFOAFfromName']; 				
	echo lookupNameInSindice($entity);
}

/* dump info from a FOAF document */
if(isset($_GET['URI'])){ 
	$URI = $_GET['URI']; 			
		
	$foafInfo = getFOAFInfo($URI);
	
	if($foafInfo == null) {
		dumpFOAFInfo($URI);
		$foafInfo = getFOAFInfo($URI);
		if($DO_DEBUG) echo "added " . $foafInfo["personURI"] . " to my database ..."; 
	}
	addMailToPerson($URI, $foafInfo["personURI"], $foafInfo["personMail"]);
	echo $foafInfo["personMail"];
}

/* guess person URI from FOAF document */
if(isset($_GET['personURIInFOAF'])){ 
	$URI = $_GET['personURIInFOAF']; 			
		
	$foafInfo = getFOAFInfo($URI);
		 
	echo $foafInfo["personURI"];
}	

/* mail -> person URI */
if(isset($_GET['personURI4Mail'])){ 
	$mail = $_GET['personURI4Mail']; 					 
	//echo $mail;
	echo getPersonURIFromMail($mail);
}

/* return information from FOAF document (name, depiction, etc.)*/
if(isset($_GET['aboutURI'])){ 
	$URI = $_GET['aboutURI']; 			
		
	$foafInfo = getFOAFInfo($URI);
	
	if($foafInfo == null) {
		dumpFOAFInfo($URI);
		$foafInfo = getFOAFInfo($URI);
		if($DO_DEBUG) echo "added " . $foafInfo["personURI"] . " to my database"; 
	}
			 
	echo "<p>found depcition in <a href=\"$URI\">$URI</a>:</p><p>";
	echo "&lt;" . $foafInfo["personURI"] . "&gt; (with e-mail address <a href=\"mailto:";
	echo $foafInfo["personMail"] . "\">" . $foafInfo["personMail"] . "</a>) <br /> is depcited at <a href=\"";
	echo $foafInfo["personDepictionURI"] . "\">" . $foafInfo["personDepictionURI"];
	echo "</p>";
	echo "<p><img src=\"" . $foafInfo["personDepictionURI"] . "\"></p>";		
}	

/* return the photo page of a photo */
if(isset($_GET['photo'])){ 
	$photoID = $_GET['photo']; 		
	//var_dump(getFlickrPhotoPage($photoID));
	$photoPage = getFlickrPhotoPage($photoID);
	echo $photoPage;
}

/* return the thumbnail URI of a photo */
if(isset($_GET['photothumb'])){ 
	$photoID = $_GET['photothumb']; 		
	//var_dump(getFlickrPhotoThumbnail($photoID));
	$photoThumbURI = getFlickrPhotoThumbnail($photoID);
	echo $photoThumbURI;
}
/* lookup flickr user with email address*/
if(isset($_GET['user'])){ 
	$user = $_GET['user']; 	
	echo getFlickrUserIDFromEMail($user);
}

/* return some info about flickr user */
if(isset($_GET['aboutuser'])){ 
	$userID = $_GET['aboutuser']; 	
	$userInfo = getInfoAboutFlickrUserID($userID);
	//var_dump($userInfo["username"]);
	echo "username: " . $userInfo["username"] . "<br />"; 
	echo "real name: " . $userInfo["realname"] . "<br />"; 
	echo "location: " . $userInfo["location"] . "<br />"; 
}

/* list photos tagged with a specific tag */
if(isset($_GET['photos4tag'])){ 
	$tag = urldecode($_GET['photos4tag']);
	//echo $tag; 	
	$photoList = getPhotosTaggedWith($tag);
	//if($DO_DEBUG) var_dump($photoList);
	for($i=0;$i<=sizeof($photoList);$i++){
		$r .= $photoList[$i]["id"] . ",";
	}	
	echo $r;
}
/* list all public photos of a flickr user */
if(isset($_GET['photos4user'])){ 
	$photos4user = $_GET['photos4user']; 	
	$photoListofUser = getPublicPhotosFromUser($photos4user);
	//if($DO_DEBUG) var_dump($photoListofUser);
	for($i=0;$i<=sizeof($photoListofUser);$i++){
		$r .= $photoListofUser[$i]["id"] . ",";
	}	
	echo $r;
}

/* match notes with person ID (HTTP URI) */
if(isset($_GET['match'])){ 
	$currentPhotoID = $_GET['match']; 			
	//echo "inspecting " . $currentPhotoID . "<br />";
		
	$flickrPersonURIs = extractPersonURIFromFlickrPhoto($currentPhotoID);		
	$targetPersonURI = urldecode($_GET['personURI']);
	//echo "target person is <" . $targetPersonURI . "><br />";
	
	if($flickrPersonURIs == null){ // no note found on photo
		echo "<p>no notes found on " . $currentPhotoID . "</p>";
	}
	else { // there are notes
		foreach($flickrPersonURIs as $personURI){			
			//echo "check <" . $personURI . ">";
			if(strcmp($targetPersonURI, $personURI) == 0){
				$photoPage = getFlickrPhotoPage($currentPhotoID);
				$photoThumbURI = getFlickrPhotoThumbnail($currentPhotoID);
				addMatch($personURI, $photoPage);
				echo "<p>found " . $targetPersonURI . " in <a href=\"$photoPage\"><img class=\"imatch\" src=\"$photoThumbURI\" /></a>";						
				echo "</p>";
			}
			else {
				echo "<p>no -  person " . $targetPersonURI . " is not in this pic.</p>";
			}	
		}
	}
}

/* list all matched photos from flickr that depict a person with person ID (HTTP URI) */
if(isset($_GET['list'])){ 
	$URI = $_GET['list']; 			
	//var_dump(getDepictionInfo($URI));
	$matchList = getDepictionInfo($URI);
	
	if($matchList != null) {
		$r = "<ul>";
		for($i=0;$i<=sizeof($matchList)-1;$i++){
			$r .= "<li><a href=\"$matchList[$i]\">" . $matchList[$i] . "</a></li>";
		}
		$r .= "</ul>";
	}
	else $r = "<p>no results yet</p>";
	echo $r;
}	

/* generate standalone XHTML+RDFa document that gives a tabular overview of all user depictions */
if(isset($_GET['report'])){ 
	$user = $_GET['report']; 				
	echo reportOnUser($user);
}


if(isset($_GET['getMIMEType'])){ 
	$URI = $_GET['getMIMEType']; 
	echo getMIMETypeOfResourceWithURI($URI);	
}

	
?>