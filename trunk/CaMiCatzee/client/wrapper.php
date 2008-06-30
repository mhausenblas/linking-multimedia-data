<?php

if(isset($_GET['findFOAFfromURI'])){ 	
	$entity = urldecode($_GET['findFOAFfromURI']);
	$entityEsc = str_replace('#', '%23', $entity);
  echo file_get_contents("service.php?findFOAFfromURI=" . $entityEsc); 
}

if(isset($_GET['findFOAFfromName'])){ 	
	$entity = $_GET['findFOAFfromName'];
  echo file_get_contents("service.php?findFOAFfromName=" . urlencode($entity)); 
}

if(isset($_GET['photos4tag'])){ 
	$tag = $_GET['photos4tag']; 				
  echo file_get_contents("service.php?photos4tag=" . $tag); 
}

if(isset($_GET['list'])){ 
	$foaf = $_GET['list']; 				
  echo file_get_contents("service.php?list=" . $foaf); 
}

if(isset($_GET['URI'])){ 
	$URI = $_GET['URI']; 				
  echo file_get_contents("service.php?URI=" . $URI); 
}

if(isset($_GET['personURIInFOAF'])){ 
	$foaf = $_GET['personURIInFOAF']; 				
  echo file_get_contents("service.php?personURIInFOAF=" . $foaf); 
}

if(isset($_GET['personURI4Mail'])){ 
	$mail = $_GET['personURI4Mail']; 				
  echo file_get_contents("service.php?personURI4Mail=" . $mail); 
}


if(isset($_GET['aboutURI'])){ 
	$URI = $_GET['aboutURI']; 				
  echo file_get_contents("service.php?aboutURI=" . $URI); 
}

if(isset($_GET['user'])){ 
	$mail = $_GET['user']; 				
  echo file_get_contents("service.php?user=" . $mail); 
}

if(isset($_GET['photothumb'])){ 
	$photoID = $_GET['photothumb']; 				
  echo file_get_contents("service.php?photothumb=" . $photoID); 
}

if(isset($_GET['aboutuser'])){ 
	$userID = $_GET['aboutuser']; 				
  echo file_get_contents("service.php?aboutuser=" . $userID); 
}	

if(isset($_GET['photos4user'])){ 
	$mail = $_GET['photos4user']; 				
  echo file_get_contents("service.php?photos4user=" . $mail); 
}	

if(isset($_GET['match'])){ 
	$match = $_GET['match']; 				
	$person = urldecode($_GET['personURI']);
	$personURI = str_replace('#', '%23', $person);
	//echo $personURI;
  echo file_get_contents("service.php?match=$match&personURI=$personURI"); 
}	

if(isset($_GET['dumpInfo'])){ 
  echo file_get_contents("service.php?dumpInfo"); 
}

if(isset($_GET['report'])){ 
	$URI = $_GET['report']; 				
  echo file_get_contents("service.php?report=" . $URI); 
}
?>