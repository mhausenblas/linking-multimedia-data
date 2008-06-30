<?php
include_once("C:/Program Files/Apache Software Foundation/Apache2.2/htdocs/arc2/ARC2.php");

$DO_DEBUG = false;
$DEPICTION_SUBGRAPH_URI = "/flickr-depiction";


/* ARC RDF store config */
$config = array(
  'db_host' => 'localhost',
	'db_name' => 'arcdb',
	'db_user' => 'arc',
	'db_pwd' => '',
	'store_name' => 'camicatzee',
	'sem_html_formats' => 'adr-foaf dc erdf hcard-foaf openid rdfa rel-tag-skos xfn',
);

/* global store init (one shot)*/	
$store = ARC2::getStore($config);
if (!$store->isSetUp()) {
	$store->setUp();
}


$NAMESPACES = array(
		'xsd' => 'http://www.w3.org/2001/XMLSchema#',
  	'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
  	'rdfa' => 'http://www.w3.org/1999/xhtml/vocab#',
  	'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
		'owl' => 'http://www.w3.org/2002/07/owl#',
  	'foaf' => 'http://xmlns.com/foaf/0.1/',
  	'dc' => 'http://purl.org/dc/elements/1.1/', 
  	'dcterms' => 'http://purl.org/dc/terms/',
  	'skos' => 'http://www.w3.org/2004/02/skos/core#',
  	'sioc' => 'http://rdfs.org/sioc/ns#',
  	'sioct' => 'http://rdfs.org/sioc/types#',
  	'xfn' => 'http://gmpg.org/xfn/11#',
  	'twitter' => 'http://twitter.com/'   	
);


/* low level operations */



// list all graphs
function dumpAllData(){
	global $store;
	global $DO_DEBUG;
	global $DEPICTION_SUBGRAPH_URI;
		
	// list all triples per graph
	$q  = "SELECT DISTINCT ?g WHERE { GRAPH ?g { ?s ?p ?o . } } ORDER BY ?g";
	
	if($DO_DEBUG) echo htmlentities($q);
	
	$r .= "<div style=\"margin: 50px; text-align:left\">I have currently data about:";			
	if($rows = $store->query($q, 'rows')) { 
		foreach ($rows as $row) {			
			$g = $row['g'];			
			if($g != null) {								
				if(!endsWith($g, $DEPICTION_SUBGRAPH_URI)) {
					$r .= "<div style=\"margin-top:20px\">&lt;<a href=\"$g\">$g</a>&gt;";							
					$depictions = getDepictionInfo($g);				  					
					if($depictions != null) {
						$r .= "<ul>";
						for($i=0;$i<=sizeof($depictions)-1;$i++){
							$r .= "<li><a href=\"$depictions[$i]\">" . $depictions[$i] . "</a></li>";
						}
						$r .= "</ul>";
					}
					else $r .= " - no depictions found, yet";					  					  					
					$r .= "</div>";					
				}				
			}
		}
	}
	else {
		$r .= "I have no data currently, sorry. People should really start using me ;)";
	}
	$r .= "</div>";			
	return $r;
}





// list all triples per graph
function dumpAllDataWithTriples(){
	global $store;
	global $DO_DEBUG;
		
	// list all triples per graph
	$q  = "SELECT ?g ?s ?p ?o WHERE { GRAPH ?g { ?s ?p ?o . } } ORDER BY ?g";
	
	if($DO_DEBUG) echo htmlentities($q);
	
	$r .= "<div style=\"margin: 50px; text-align:left\">";			
	if($rows = $store->query($q, 'rows')) { 
		foreach ($rows as $row) {			
			$g = $row['g'];			
			if($g != null) {
				$s = $row['s'];	$p = $row['p'];	$o = $row['o'];
				if($oldG == $g)	$r .= "<div style=\"padding-left:50px\">$s $p $o</div>";
				else {
					$r .= "<div style=\"margin-top:20px\">In &lt;$g&gt;</div>";			
					$r .= "<div style=\"padding-left:50px\">$s $p $o</div>";
				}	
				$oldG = $g;
			}
		}
	}
	else {
		$r .= "I have no data currently, sorry. People should really start using me ;)";
	}
	$r .= "</div>";			
	return $r;
}

// determine MIME type of resource
// http://nadeausoftware.com/articles/2007/06/php_tip_how_get_web_page_content_type
function getMIMETypeOfResourceWithURI($URI){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URI);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_exec($ch);
	$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
 
	/* Get the MIME type and character set */
	preg_match( '@([\w/+]+)(;\s+charset=(\S+))?@i', $content_type, $matches );
	if (isset($matches[1])) $mimeType = $matches[1];
	else $mimeType = "UNKNOWN";
	if (isset($matches[3])) $charset = $matches[3];

  return $mimeType;
}


/* utils */
/*
function getFOAFGraphFromDepcitionGraph($depictionGraph){
	global $DEPICTION_SUBGRAPH_URI;
	return substr($depictionGraph, 0, strlen($DEPICTION_SUBGRAPH_URI));	
}
*/

function startsWith($str, $sub) {
    return (strncmp($str, $sub, strlen($sub)) === 0);
}

function endsWith($str, $sub) {
   return (substr($str, strlen($str) - strlen($sub)) === $sub);
}

	
?>