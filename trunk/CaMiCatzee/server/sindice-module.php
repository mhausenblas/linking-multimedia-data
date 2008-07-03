<?php

/**************************************************************************************************/
/* sindice.com module                                                                             */

// query advanced '<URI> * *' in sindice.com
function lookupURIInSindice($URI){
	global $NAMESPACES;
 	global $DO_DEBUG;
 	$sindiceLookupURIRaw = "http://api.sindice.com/v2/search?q=". urlencode("<" . $URI . "> * *") . "&qt=advanced";
 	$sindiceLookupURI = $sindiceLookupURIRaw . "&format=rdfxml";
 	
 	if($DO_DEBUG) echo "looking up $sindiceLookupURIRaw";
	
	$conf = array('ns' => $NAMESPACES);
 	
	$parser = ARC2::getRDFParser();
	$ser = ARC2::getTurtleSerializer($conf);	
	$parser->parse($sindiceLookupURI);
	
	$triples = $parser->getTriples();
	$index = ARC2::getSimpleIndex($triples, false) ;
		
	$r = "<ul style=\"margin: 50px; text-align:left\">";
	for ($i = 0, $i_max = count($triples); $i < $i_max; $i++) {
  	//$r .= "<li>" . $triples[$sindiceLookupURIRaw]['http://sindice.com/vocab/search#result']['o'] . " [<a href=\"javascript:useAsURI('" . $triples[$i]['o'] . "')\">try this!</a>]</li>";  
  	if($triples[$i]['o'] == 'http://sindice.com/vocab/search#Result' ) {
  		$resultSubject = $triples[$i]['s'];  		
  		$result = $index[$resultSubject]['http://purl.org/dc/elements/1.1/title'][0]['val'];  		
  		$r .= "<li><a href=\"$link \">" . $result . "</a> [<a href=\"javascript:useAsFOAFDoc('" .$result . "')\" title=\"grab this as my FOAF document\">G</a>]</li>";  
  	}
	}
	$r .= "</ul>";
	return $r;
}


// query term in sindice.com
function lookupNameInSindice($name){
	global $NAMESPACES;
 	global $DO_DEBUG;
 	$sindiceLookupURIRaw = "http://api.sindice.com/v2/search?q=". urlencode($name);
 	$sindiceLookupURI = $sindiceLookupURIRaw . "&format=rdfxml";
 	
 	if($DO_DEBUG) echo "looking up $name";
	
	$conf = array('ns' => $NAMESPACES);
 	
	$parser = ARC2::getRDFParser();
	$ser = ARC2::getTurtleSerializer($conf);	
	$parser->parse($sindiceLookupURI);
	
	$triples = $parser->getTriples();
	$index = ARC2::getSimpleIndex($triples, false) ;
		
	$r = "<ul style=\"margin: 50px; text-align:left\">";
	for ($i = 0, $i_max = count($triples); $i < $i_max; $i++) {
  	//$r .= "<li>" . $triples[$sindiceLookupURIRaw]['http://sindice.com/vocab/search#result']['o'] . " [<a href=\"javascript:useAsURI('" . $triples[$i]['o'] . "')\">try this!</a>]</li>";  
  	if($triples[$i]['o'] == 'http://sindice.com/vocab/search#Result' ) {
  		$resultSubject = $triples[$i]['s'];  		
  		$link = $index[$resultSubject]['http://sindice.com/vocab/search#link'][0]['val'];  		
  		$r .= "<li><a href=\"$link \">" . $link . "</a> [<a href=\"javascript:useAsFOAFDoc('" .$link . "')\" title=\"grab this as my FOAF document\">G</a>]</li>";  
  	}
	}
	$r .= "</ul>";
	return $r;
}

// reads tags of photo with photoID and returns list of URIs from sindice.com
function lookupTagsInSindice($photoID){
	global $DO_DEBUG;
	
	$r = array();
	$j = 0;
	
	$tags = getFlickrTagsFromPhoto($photoID);
	if($DO_DEBUG) var_dump($tags);
	
	for($i=0;$i<=sizeof($tags);$i++){
		$tag = $tags["tag"][$i]["_content"];			
		$r[$j] = "<b>$tag</b> (" . lookupTagSeeAlsoFromSindice($tag) . ")";
		$j++;					  
	}
	return $r;	
}


// lists see also links for a tag from sindice.com
function lookupTagSeeAlsoFromSindice($name){
	global $NAMESPACES;
 	global $DO_DEBUG;
 	$sindiceLookupURIRaw = "http://api.sindice.com/v2/search?q=". urlencode($name);
 	$sindiceLookupURI = $sindiceLookupURIRaw . "&format=rdfxml";
 	
 	if($DO_DEBUG) echo "looking up $name";
	
	$conf = array('ns' => $NAMESPACES);
 	
	$parser = ARC2::getRDFParser();
	$ser = ARC2::getTurtleSerializer($conf);	
	$parser->parse($sindiceLookupURI);
	
	$triples = $parser->getTriples();
	$index = ARC2::getSimpleIndex($triples, false) ;
		
	$r = "<span>see also ";
	$j = 0;
	for ($i = 0, $i_max = count($triples); $i < $i_max; $i++) {
  	if($triples[$i]['o'] == 'http://sindice.com/vocab/search#Result' ) {
  		$resultSubject = $triples[$i]['s'];  		
  		$link = $index[$resultSubject]['http://sindice.com/vocab/search#link'][0]['val'];  		
  		$r .= "<a rel=\"rdfs:seeAlso\" href=\"$link\">[" . $j . "]</a>";  
  		$j++;	
  	}
	}
	$r .= "</span>";
	return $r;
}







	
?>