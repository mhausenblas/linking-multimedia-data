<?php 

/**************************************************************************************************/
/* flickr.com module                                                                              */

// list photos tagged with 'tag'
function getPhotosTaggedWith($tag){
	global $DO_DEBUG;
	
	if($DO_DEBUG) echo "looking up photos tagged with <b>$tag</b>";
  
	$params = array(
		'api_key'	=> '15f7d2ebd66e51d4868ef31536e61851',
		'method'	=> 'flickr.photos.search',
		'per_page' => '500',
		'tags'	=> $tag,
		'format'	=> 'php_serial',
	);

	$encoded_params = array();
	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	$url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
	$rsp = file_get_contents($url);
	$rsp_obj = unserialize($rsp);
	if ($rsp_obj['stat'] == 'ok'){
		$r = $rsp_obj['photos']['photo'];		
	}
	else{
		echo "flickr API call failed!";
	}
	
	return $r;	
}


// list public photos of user with email
function getPublicPhotosFromUser($email){
	global $DO_DEBUG;
	
	if($DO_DEBUG) echo "looking up user with mail address $email";
  
  $userID = getFlickrUserIDFromEMail($email);
  
	$params = array(
		'api_key'	=> '15f7d2ebd66e51d4868ef31536e61851',
		'method'	=> 'flickr.people.getPublicPhotos',
		'per_page' => '500',
		'user_id'	=> $userID,
		'format'	=> 'php_serial',
	);

	$encoded_params = array();
	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	$url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
	$rsp = file_get_contents($url);
	$rsp_obj = unserialize($rsp);
	if ($rsp_obj['stat'] == 'ok'){
		$r = $rsp_obj['photos']['photo'];		
	}
	else{
		echo "flickr API call failed!";
	}
	
	return $r;	
}

// email -> user ID
function getFlickrUserIDFromEMail($email){
	global $DO_DEBUG;
	if($DO_DEBUG) echo "looking up user with mail address $email";
  
	$params = array(
		'api_key'	=> '15f7d2ebd66e51d4868ef31536e61851',
		'method'	=> 'flickr.people.findByEmail',
		'find_email'	=> $email,
		'format'	=> 'php_serial',
	);

	$encoded_params = array();
	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	$url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
	$rsp = file_get_contents($url);
	$rsp_obj = unserialize($rsp);
	if ($rsp_obj['stat'] == 'ok'){
		$r = $rsp_obj['user']['id'];		
	}
	else{
		echo "flickr API call failed!";
	}
	
	return $r;	
}

// user ID -> user info (user name, real name, location)
function getInfoAboutFlickrUserID($userID){
	global $DO_DEBUG;
	if($DO_DEBUG) echo "looking up info about user with flickr user ID $userID";
  
  $r = array();
  
	$params = array(
		'api_key'	=> '15f7d2ebd66e51d4868ef31536e61851',
		'method'	=> 'flickr.people.getInfo',
		'user_id'	=> $userID,
		'format'	=> 'php_serial',
	);

	$encoded_params = array();
	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	$url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
	$rsp = file_get_contents($url);
	$rsp_obj = unserialize($rsp);
	if ($rsp_obj['stat'] == 'ok'){
		$r["username"] = $rsp_obj['person']['username']['_content'];		
		$r["realname"] = $rsp_obj['person']['realname']['_content'];
		$r["location"] = $rsp_obj['person']['location']['_content'];
	}
	else{
		echo "flickr API call failed!";
	}
	
	return $r;	
}

// reads notes of photo with photoID and returns list of notes that start with an HTTP URI
function extractPersonURIFromFlickrPhoto($photoID){
	global $DO_DEBUG;
	
	$r = array();
	$j = 0;
	
	$notes = getFlickrNote($photoID);
	if($DO_DEBUG) var_dump($notes);
	
	for($i=0;$i<=sizeof($notes);$i++){
		$note = $notes["note"][$i]["_content"];
	
		if(substr($note, 0, 7) == 'http://') { // is a potential HTTP URI
		 if($DO_DEBUG)  echo "found link in photo $photoID that links to $note";
		 $r[$j] = $note;
		 $j++;
		}			  
	}
	return $r;	
}

// returns all notes of a certain photo
function getFlickrNote($photoID) {
  global $DO_DEBUG;
  
  if($DO_DEBUG) echo "looking up $photoID for HTTP URIs";
  
	$params = array(
		'api_key'	=> '15f7d2ebd66e51d4868ef31536e61851',
		'method'	=> 'flickr.photos.getInfo',
		'photo_id'	=> $photoID,
		'format'	=> 'php_serial',
	);

	$encoded_params = array();
	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	$url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
	$rsp = file_get_contents($url);
	$rsp_obj = unserialize($rsp);
	if ($rsp_obj['stat'] == 'ok'){
		$r = $rsp_obj['photo']['notes'];		
	}
	else{
		echo "flickr API call failed!";
	}
	
	return $r;
}

// returns all tags of a certain photo
function getFlickrTagsFromPhoto($photoID) {
  global $DO_DEBUG;
  
  if($DO_DEBUG) echo "looking up $photoID for tags";
  
	$params = array(
		'api_key'	=> '15f7d2ebd66e51d4868ef31536e61851',
		'method'	=> 'flickr.photos.getInfo',
		'photo_id'	=> $photoID,
		'format'	=> 'php_serial',
	);

	$encoded_params = array();
	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	$url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
	$rsp = file_get_contents($url);
	$rsp_obj = unserialize($rsp);
	if ($rsp_obj['stat'] == 'ok'){
		$r = $rsp_obj['photo']['tags'];		
	}
	else{
		echo "flickr API call failed!";
	}
	
	return $r;
}




// photoID -> photo page (container)
function getFlickrPhotoPage($photoID) {
	global $DO_DEBUG;
	
  if($DO_DEBUG) echo "looking up photo page of $photoID";
  
	$params = array(
		'api_key'	=> '15f7d2ebd66e51d4868ef31536e61851',
		'method'	=> 'flickr.photos.getInfo',
		'photo_id'	=> $photoID,
		'format'	=> 'php_serial',
	);

	$encoded_params = array();
	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	$url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
	$rsp = file_get_contents($url);
	$rsp_obj = unserialize($rsp);
	if ($rsp_obj['stat'] == 'ok'){
		$r = $rsp_obj['photo']['urls']['url'][0]['_content'];		
	}
	else{
		echo "flickr API call failed!";
	}
	
	return $r;
}

// photoID- > thumbnail URI of photo
function getFlickrPhotoThumbnail($photoID) {
	global $DO_DEBUG;
	
  if($DO_DEBUG) echo "looking up thumbnail of photo $photoID";
  
	$params = array(
		'api_key'	=> '15f7d2ebd66e51d4868ef31536e61851',
		'method'	=> 'flickr.photos.getSizes',
		'photo_id'	=> $photoID,
		'format'	=> 'php_serial',
	);

	$encoded_params = array();
	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	$url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
	$rsp = file_get_contents($url);
	$rsp_obj = unserialize($rsp);
	if ($rsp_obj['stat'] == 'ok'){
		$r = $rsp_obj['sizes']['size'][1]['source'];		
	}
	else{
		echo "flickr API call failed!";
	}
	
	return $r;
}
	
?>