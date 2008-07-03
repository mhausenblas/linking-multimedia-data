<?php
			include_once("common.php");
			include_once("foaf-module.php");
			include_once("flickr-module.php");
			include_once("sindice-module.php");

$TMP_GRAPH_URI = "http://tmp/";
$format = "rdfa";

// content negotation 

	if (stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml")) {		      
    header("Content-type: application/xhtml+xml;charset=utf-8"); // XHTML+RDFa; what modern Semantic Web agents are able to cope with
    $format = "rdfa";
  } 
  else {
    if (stristr($_SERVER["HTTP_ACCEPT"],"application/rdf+xml")) {
    	header("Content-type: application/rdf+xml;charset=utf-8"); // what legacy Semantic Web agents expect to see
    	$format = "rdfxml";
    }
    else { 
    	header("Content-type: text/html"); // fallback HTML (only for IE and other non-conforming UA)
    	$format = "rdfa";
    }
 	}


/* convert the information from a flickr page to linked data */
// use: http://143.224.254.32/Camicatzee/transform.php?URI=http://www.flickr.com/photos/gromgull/2565018601/
// list with SELECT DISTINCT * WHERE { GRAPH <http://tmp/2565018601> { ?s ?p ?o . } }
if(isset($_GET['URI'])){ 
	$photoURI = $_GET['URI']; 			
	if(stristr($format, "rdfxml")) echo convert2RDFXML($photoURI);
	else echo convert2RDFa($photoURI);		
}


// converts the flickr photo page info to RDF/XML
function convert2RDFXML($photoURI){
	global $store;
	global $TMP_GRAPH_URI;
	global $NAMESPACES;
	
	preg_match("/[0-9]+/", $photoURI, $matches);
	$photoID = $matches[0];
	$tmpGraph = $TMP_GRAPH_URI . $photoID;
		
	//echo "inspecting " . $photoID . "<br />";
		
	$flickrPersonURIs = extractPersonURIFromFlickrPhoto($photoID);		
	
	if($flickrPersonURIs == null){ // no note found on photo
		echo "<p>no notes found on " . $photoID . "</p>";
	}
	else { // there are notes
		if(isset($_GET['full'])) {
			// get the RDF from flickr and add it as well:
			$parser = ARC2::getRDFParser();
			$parser->parse($photoURI);
			$triples = $parser->getTriples();
		  $index = ARC2::getSimpleIndex($triples, false); 
			$store->insert($index, $tmpGraph);
		}
		// add depiction
		foreach($flickrPersonURIs as $personURI){	
			if($DO_DEBUG) echo "<p>$photoURI foaf:depicts $personURI</p>";
			$q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> INSERT INTO <$tmpGraph> { <$photoURI> foaf:depicts  <$personURI> . }";
			$store->query($q);
		}		
		// construct return value
		$q = "SELECT * FROM <$tmpGraph> { ?s ?p ?o }";
		$triples = $store->query($q, 'rows');
		
		$conf = array('ns' => $NAMESPACES);
		$ser = ARC2::getRDFXMLSerializer($conf);
		$r = $ser->getSerializedTriples($triples);
				
		// and remove the temp stuff again 
		$q = "DELETE FROM <$tmpGraph>";
		$store->query($q);		
	}
	return $r;
}
	

// converts the flickr photo page info to XHTML+RDFa
function convert2RDFa($photoURI){
	global $store;
	global $TMP_GRAPH_URI;
	global $NAMESPACES;
		
	$XHTMLRDFA_HEAD = "<?xml version=\"1.0\"?>
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML+RDFa 1.0//EN\" \"http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd\">
<html	xmlns=\"http://www.w3.org/1999/xhtml\"
      xmlns:scv=\"http://purl.org/NET/scovo#\"
			xmlns:foaf=\"http://xmlns.com/foaf/0.1/\"
			xmlns:dc=\"http://purl.org/dc/elements/1.1/\"
			xmlns:cc=\"http://web.resource.org/cc/\"
			xmlns:xsd=\"http://www.w3.org/2001/XMLSchema#\"
			xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"
			xmlns:rdfs=\"http://www.w3.org/2000/01/rdf-schema#\"
			xmlns:imreg=\"http://web-semantics.org/ns/image-regions#\">
<head>
	<title>CaMiCatzee transformation results for " . $photoURI . "</title>
	<link rel=\"stylesheet\" type=\"text/css\" href=\"http://sw.joanneum.at/CaMiCatzee/style.css\" />
</head>
<body>\n";
	
	
	preg_match("/[0-9]+/", $photoURI, $matches);
	$photoID = $matches[0];
	$tmpGraph = $TMP_GRAPH_URI . $photoID;
		
	//echo "inspecting " . $photoID . "<br />";
		
	$flickrPersonURIs = extractPersonURIFromFlickrPhoto($photoID);		
	//var_dump($flickrPersonURIs);
	if($flickrPersonURIs == null){ // no note found on photo
		echo "<p>no notes found on " . $photoID . "</p>";
	}
	else { // there are notes		
		// add depiction
		$person = $flickrPersonURIs[0]; 
		//echo "<p>$photoURI foaf:depicts $person</p>";
		$q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> INSERT INTO <$tmpGraph> { <$photoURI> foaf:depicts  <$person> . }";
		$store->query($q);
				
		// construct return value
		$q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> SELECT DISTINCT ?photo { GRAPH <$tmpGraph> { ?photo foaf:depicts <$person> . } }";
		//echo $q;
		
		$r = $XHTMLRDFA_HEAD;	
		$r .= "<div>";
		if($rows = $store->query($q, 'rows')) { 
			foreach ($rows as $row) {		
				$photopage = $row['photo'];	
				preg_match("/[0-9]+/", $photopage, $matches);
				$photoID = $matches[0];
				$photo = getFlickrPhotoMedium($photoID);
				//$r .= "<div about=\"$photo\">\n<a href=\"$photo\">$photo</a>\n<span rel=\"foaf:depicts\" resource=\"$person\"> depicts $person</span>\n";
				$r .= "<div about=\"$person\" rel=\"foaf:depiction\"><img src=\"$photo\" /> depicts <a href=\"$person\">$person</a> ";
				// evaluate coordinates
				$notes = getFlickrNote($photoID);
				for($k=0;$k<=sizeof($notes)-1;$k++){
					$xb = $notes["note"][$k]["x"] + $notes["note"][$k]["w"];
					$yb = $notes["note"][$k]["y"] + $notes["note"][$k]["h"]; 
					
					//$r .= "at x:" . $notes["note"][$k]["x"] . " y:" . $notes["note"][$k]["y"] . " width:" . $notes["note"][$k]["w"] . " height:" . $notes["note"][$k]["h"];
					$r .= " <div about=\"$photo\" typeof=\"foaf:Image\">\n";
					$r .= "  <span rel=\"imreg:region\" resource=\"[_:]\" />\n";
					$r .= " </div>\n";
					$r .= " <div about=\"[_:]\">\n";
					$r .= "  <span property=\"imreg:boundingBox\" content=\"". $notes["note"][$k]["x"] . "," . $notes["note"][$k]["y"] .  "," . $xb . "," .  $yb . "\"></span>";
					$r .= " </div>\n";
				}			  								
				$r .= "</div>\n";
			}
		}
		$r .= "</div>";
		$r .= "</body>\n</html>";	
		// and remove the temp stuff again 
		$q = "DELETE FROM <$tmpGraph>";
		//$store->query($q);		
	}
	return $r;
}


	
?>