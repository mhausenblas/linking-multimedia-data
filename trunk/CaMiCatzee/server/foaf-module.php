<?php
	
function getDepictionInfo($URI){
	global $store;
	global $DO_DEBUG;
	global $DEPICTION_SUBGRAPH_URI;
	
	$foafInfo = getFOAFInfo($URI);
	$personURI = $foafInfo["personURI"];
	//echo $personURI;
	
	$index = 0;		
	$r = array();	
	
	// return user depiction
	$q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> SELECT DISTINCT * FROM <" . $personURI . $DEPICTION_SUBGRAPH_URI . "> WHERE{ ?flickrURI foaf:depicts  <$personURI> . }";
	
	if($DO_DEBUG) echo htmlentities($q);
	
	if($rows = $store->query($q, 'rows')) { 
		foreach ($rows as $row) {
			$r[$index] = $row['flickrURI'];
			$index++;
		}
	}
	return $r;
	
}

function dumpFOAFInfo($URI){
	global $store;
	global $DO_DEBUG;
	
	$q = "ASK WHERE { GRAPH <$URI> { ?s ?p ?o .} }";
	
	if($DO_DEBUG) echo htmlentities($q);
	
	$haveFOAF = $store->query($q, 'rows');
	
	if(!$haveFOAF){ // dump FOAF info
		$mimeTypeOfFOAFDoc = getMIMETypeOfResourceWithURI($URI);
		$parser = ARC2::getRDFParser();		
		if(stristr($mimeTypeOfFOAFDoc, "RDF")) { // a RDF/XML resource
			$parser->parse($URI);
		}
		else { // any other, such as RDFa
			$parser->parse("http://www.w3.org/2007/08/pyRdfa/extract?uri=$URI");
		}
		
		$triples = $parser->getTriples();
  	$index = ARC2::getSimpleIndex($triples, false); 
		$store->insert($index, $URI);	
	}
}

function getFOAFInfo($URI){
	global $store;
	global $DO_DEBUG;
	$r  = array();	
	
	
	// trying to guess what the person URI is 
	
	
	// guess #1  -  a person is the primary topic of the FOAF document
	$q  = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> SELECT DISTINCT * FROM <" . $URI . "> WHERE {  ?x foaf:primaryTopic ?person .  }";
	if($DO_DEBUG) echo htmlentities($q);		
	if($rows = $store->query($q, 'rows')) { 
		foreach ($rows as $row) {
			$r["personURI"] = $row['person'];
			//echo $r["personURI"];
			$r["personDepictionURI"] = $row['personDepictionURI'];
			$r["personMail"] = $row['mail'];
		}
	}		
	else {
		// guess #2  -  a person has a depiction and there is somewhere an e-mail address attached
		$q  = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> SELECT * FROM <" . $URI . "> WHERE { ?person foaf:depiction ?personDepictionURI .  OPTIONAL { ?x foaf:mail ?mail . } }";
		if($DO_DEBUG) echo htmlentities($q);		
		if($rows = $store->query($q, 'rows')) { 
			foreach ($rows as $row) {
				$r["personURI"] = $row['person'];
				//echo $r["personURI"];
				$r["personDepictionURI"] = $row['personDepictionURI'];
				$r["personMail"] = $row['mail'];
			}
		}			
	}	
		
	return $r;
}

function getPersonURIFromMail($mail){
	global $store;
	global $DO_DEBUG;
		
	// return user depiction
	$q  = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> SELECT * WHERE { ?personURI a foaf:Person; foaf:mail \"". $mail . "\" . }";
	
	if($DO_DEBUG) echo htmlentities($q);
	
	if($rows = $store->query($q, 'rows')) { 
		foreach ($rows as $row) {
			$r = $row['personURI'];
		}
	}
	return $r;
}


function reportOnUser($URI) {
	global $store;
	global $DO_DEBUG;
	global $DEPICTION_SUBGRAPH_URI;
	
	$dDone = array();
	$j = 0;
	$foafInfo = getFOAFInfo($URI);
	$personURI = $foafInfo["personURI"];
	$reportURI = urlencode("http://sw.joanneum.at/CaMiCatzee/wrapper.php?report=$URI");
	
	$dURI = $personURI . $DEPICTION_SUBGRAPH_URI;

	$q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> SELECT  DISTINCT * WHERE { GRAPH <$dURI> { ?f foaf:depicts ?p . } }";
	
	if($DO_DEBUG) echo htmlentities($q);
		
	$r = "<?xml version=\"1.0\"?>
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML+RDFa 1.0//EN\" \"http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd\">
<html	xmlns=\"http://www.w3.org/1999/xhtml\"
      xmlns:scv=\"http://purl.org/NET/scovo#\"
			xmlns:foaf=\"http://xmlns.com/foaf/0.1/\"
			xmlns:dc=\"http://purl.org/dc/elements/1.1/\"
			xmlns:cc=\"http://web.resource.org/cc/\"
			xmlns:xsd=\"http://www.w3.org/2001/XMLSchema#\"
			xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"
			xmlns:rdfs=\"http://www.w3.org/2000/01/rdf-schema#\">
<head>
	<title>CaMiCatzee report on $personURI</title>
	<link rel=\"stylesheet\" type=\"text/css\" href=\"http://sw.joanneum.at/CaMiCatzee/style.css\" />
</head>
<body>\n";
	
	$r .= "<div style=\"background-color:#303030;\" ><a href=\"http://www.w3.org/2007/08/pyRdfa/extract?uri=$reportURI\"><img src=\"http://www.w3.org/Icons/SW/Buttons/sw-rdfa-gray.png\" alt=\"convert to RDF/XML\"/></a></div>";
	$r .= "<div about=\"$personURI\" style=\"text-align:center;\">\n";
	$r .= "<h2>About <a href=\"$personURI\">$personURI</a> I have the following information</h2>\n";
	if($rows = $store->query($q, 'rows')) { 
		foreach ($rows as $row) {			
			$f = $row['f'];
			if(!in_array($f, $dDone)) {
				$pattern = "/[0-9]+/";
				preg_match($pattern, $f, $matches);
				$fImg = getFlickrPhotoThumbnail($matches[0]);
				$URI4Tags = lookupTagsInSindice($matches[0]);
				$r .= "<div style=\"border: 1px dotted #f0f0f0; padding: 20px; margin: 20px 300px 10px 300px\" about=\"$f\">\n";
				$r .= "<h3 property=\"dc:title\">" . getTitleOfDepiction($URI, $f) . "</h3>";
				$r .= "<div>in <a href=\"$f\">$f</a> \n</div>";			
				$r .= "<div about=\"$personURI\" rel=\"foaf:depiction\"><img src=\"" . $fImg . "\" /></div>";
				$r .= "<div>tagged with: <ul>";
				for($i=0;$i<=sizeof($URI4Tags)-1;$i++){
					$r .= "<li>$URI4Tags[$i]</li>";			
				}
				$r .= "</ul></div>";			
				$r .= "</div>";			
				$dDone[$j] = $f; 
				$j++;
			}
		}
	}		
	$r .= "</div>";					
	$r .= "</body>\n</html>";	
	return $r;
}


function getTitleOfDepiction($URI, $depiction) {
	global $store;
	global $DO_DEBUG;
	global $DEPICTION_SUBGRAPH_URI;
	
	$foafInfo = getFOAFInfo($URI);
	$personURI = $foafInfo["personURI"];
	
	$dURI = $personURI . $DEPICTION_SUBGRAPH_URI;

	$q = "PREFIX dc: <http://purl.org/dc/elements/1.1/> SELECT * WHERE { GRAPH <$dURI> { <$depiction> dc:title ?title . } }";
	
	if($DO_DEBUG) echo htmlentities($q);
		
	if($rows = $store->query($q, 'rows')) { 
		foreach ($rows as $row) {
			$r = $row['title'];						
		}
	}	
	return $r;
}

function addMailToPerson($foafDoc, $personURI, $mail){
	global $store;  
	$q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>  INSERT INTO <$foafDoc> { <$personURI>  foaf:mail \"$mail\" . }";
	$store->query($q);
	//$q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>  INSERT INTO <$foafDoc> { <$personURI>  a foaf:Person . }";
	//$store->query($q);
}

function addMatch($personURI, $flickrURI){
	global $store;  
	global $DEPICTION_SUBGRAPH_URI;
	$dURI = $personURI . $DEPICTION_SUBGRAPH_URI;
	
	$q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> ASK WHERE { GRAPH <$dURI> { <$flickrURI> foaf:depicts  <$personURI> .} }";
	if($DO_DEBUG) echo htmlentities($q);
	$depictionAvailable = $store->query($q, 'rows');
	
	if(!$depictionAvailable){ // add new match
		// add the depiction
		$q = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> INSERT INTO <" . $dURI . "> { <$flickrURI> foaf:depicts  <$personURI> . }";
		if($DO_DEBUG) echo htmlentities($q);
		$store->query($q);	
		// get the RDF from flickr and add it as well:
		$parser = ARC2::getRDFParser();
		$parser->parse($flickrURI);
		$triples = $parser->getTriples();
  	$index = ARC2::getSimpleIndex($triples, false); 
		$store->insert($index, $dURI);			
	}
}



	
?>