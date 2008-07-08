var menuStatus = "hidden";
var foafPersonURI = "";
var flickrUserMail = "";


/**
 * XML HTTP request to a given URL and calls a given callback with the data.
 *
 * @param url the URL to call
 * @param callback the callback to call with the returned data.
 * @param postData the data to use when posting to the given URL.
 */
function sendRequest(url, callback, callback_data, postData) {
	var req = createXMLHTTPObject();

	if(!req)
		return;
	var method = (postData) ? "POST" : "GET";
	
	req.open(method, url, true);
	req.setRequestHeader('User-Agent','XMLHTTP/1.0');
	
	if(postData)
		req.setRequestHeader('Content-type','application/x-www-form-urlencoded');

	req.onreadystatechange = function() {
			if(req.readyState != 4)
				return;
			if(callback_data)
				callback(req, callback_data);
			else
				callback(req);
		}

	if(req.readyState == 4)
		return;
	
	req.send(postData);
}

/**
 * Creates an XML HTTP Object based on the type of browser that the client is using.
 */
function createXMLHTTPObject() {
   var xmlHttp;

   // get the IE (ActiveX) version of the xml http object
   /*@cc_on
   @if(@_jscript_version >= 5)
   {
      try
      {
         xmlHttp = new ActiveXObject('Msxml2.XMLHTTP');
      }
      catch(e)
      {
         try
         {
            xmlHttp = new ActiveXObject('Microsoft.XMLHTTP');
         }
         catch(e)
         {
            // nothing to do
         }
      }
   }
   @end
   @*/

   // get the non-IE (non-ActiveX) version of the xml http object
   // if the object hasn't been acquired yet
   if(xmlHttp == null && typeof XMLHttpRequest !== 'undefined') {
      xmlHttp = new XMLHttpRequest();
   }

   return xmlHttp;
}


/********************************************************************************/
/* HTTP GET CALLS                                                               */


function checkInSelection(){
	var intype = document.getElementById('intype').value;
		
	if(intype.substring(0, 2) == 'my') { // user wants to use either URI or name as input
		if(intype.substring(0, 5) == 'myURI') { // use URI as input
			document.getElementById('URI').value = 'http://sw-app.org/mic.xhtml#i';
		}
		else { // use name as input
			document.getElementById('URI').value = 'Michael Hausenblas';
		}
		document.getElementById('cmiuc').value = 'Find me a FOAF doc!';
	}	
	else {
		document.getElementById('cmiuc').value = 'Catch Me If U Can!';
		document.getElementById('URI').value = 'http://sw-app.org/mic.xhtml';
	}
	document.getElementById('result').innerHTML = "";
}

function determineUserURI(){
	var intype = document.getElementById('intype').value;
	var URI = document.getElementById('URI').value;
	//alert(intype);
	//alert(URI);
	
	hideMenu();
	
	if(intype.substring(0, 2) == 'my') { // either user URI or name ... gotta lookup in sindice to offer FOAF documents						
		if(intype.substring(0, 5) == 'myURI') { // use input as URI
			document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">looking up URI in sindice.com to find an according FOAF document ...</span></p>";
			sendRequest('wrapper.php?findFOAFfromURI=' + urlencode(URI), processFOAFSuggestions);		
		}
		else { // we assume it is a person's name
			document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">looking up name in sindice.com to find an according FOAF document ...</span></p>";
			//alert(urlencode(URI));
			sendRequest('wrapper.php?findFOAFfromName=' + urlencode(URI), processFOAFSuggestions);		
		}
	}
	else { // its the FOAF document, use directly
		sendRequest('wrapper.php?URI=' + URI, processAddFOAFDoc);			
	}
}

function processAddFOAFDoc(req){
	document.getElementById('result').innerHTML = "<p>Found e-mail address <a href=\"mailto:" + req.responseText + "\">" + req.responseText + "</a>. Looking up flickr with this information? <a href=\"javascript:lookupFlickrUserWithEmail('" + req.responseText + "');\" title=\"ok\">[K]</a> <a href=\"javascript:showMoreInfoAboutFlickrUser();\" title=\"more ...\">[M]</a></p>";
	checkAvailablity();
}


function processFOAFSuggestions(req){
	var info = req.responseText;
	document.getElementById('result').innerHTML = info;	
}


function useAsFOAFDoc(foafDoc) {
	document.getElementById('URI').value = foafDoc;
	document.getElementById('result').innerHTML = "";
	var intype = document.getElementById('intype');
  intype.selectedIndex = 0;
  document.getElementById('cmiuc').value = 'Catch Me If U Can!';
}

function checkAvailablity() {
	document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">looking up information about FOAF document ...</span></p>";
 	var URI = document.getElementById('URI').value;
	sendRequest('wrapper.php?list=' + URI  + getCacheBusterParam(), processAvailable);		
}

function processAvailable(req) {
	var info = req.responseText;
	var tagmode = document.getElementsByName('qtype')[1].checked;
	var foafURI = document.getElementById('URI').value;		
	//alert(info);
	
	if(info.substring(0, 5) == '<p>no') { // no results available
		document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">no depictions found yet; gotta check at flickr ...</span></p>";
		getUserURI();
	}
	else {
		var buffer = "<h2>Results are available</h2><p>" + info + "</p>";
		buffer = buffer + "<p><a href=\"javascript:getUserURI();\">try again!</a> or <a href=\"wrapper.php?report=" + foafURI + "\">view full report</a> </p>";		
		document.getElementById('result').innerHTML = buffer;
	}
	
}

function getUserURI() {	
	var URI = document.getElementById('URI').value;		
	document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">processing FOAF document in order to guess your person URI ...</span></p>"; 	
	sendRequest('wrapper.php?personURIInFOAF=' + URI, processGetUserURI);		
}

function processGetUserURI(req) {	
	var foafURI = document.getElementById('URI').value;
	var userURI = req.responseText;
	//alert(tagmode);
	//alert('FOAF: ' + foafURI);
	//alert('user: ' + userURI);
		
	document.getElementById('URI').value = userURI;
	//document.getElementById('URI').readOnly = true;
	document.getElementById('cmiuc').className = "execDisable";
	document.getElementById('cmiuc').disabled = true;
	document.getElementById('personRef').innerHTML = "My URI is ";
	
	doMatch();
}


function doMatch(){
	var tagmode = document.getElementsByName('qtype')[1].checked;
	var foafURI = document.getElementById('URI').value;
	var tag = document.getElementById('tagvalue').value;
	
	if(tagmode == true) {
		//alert('searching in photos tagged with ' + tag);
		document.getElementById('result').innerHTML = "<p>Searching again in flickr photos tagged with " + tag + "</p>";
		sendRequest('wrapper.php?photos4tag=' + urlencode(tag), processTaggedPhotos);		
	}
	else {
		//alert('searching in photos of ' + foafURI);
		document.getElementById('result').innerHTML = "<p>Searching again in flickr photos of " + foafURI + "</p>";
		sendRequest('wrapper.php?URI=' + foafURI, processLookupEmailOfUser);	
	}
}

function processTaggedPhotos(req) {
  var personURI = document.getElementById('URI').value;
	var tag = document.getElementById('tagvalue').value;
	var buffer = req.responseText;
	var outStr = "<p>Trying to find <a href=\"" + personURI + "\">" + personURI + "</a> in photos tagged with <b>" + tag + "</b> at flickr: </p><div style=\"text-align:justify\">";
	//alert(urlencode(personURI));
		
	var splitArray = buffer.split(",");
	var index = 1;
 	for (var i = 0; i < splitArray.length-2; i++){
	 outStr = outStr + "<span id=\"mc-"+ index + "\">" + splitArray[i] + "<a id=\"mp-"+ index + "\" href=\"javascript:performMatch('" + splitArray[i] + "', '" + personURI + "', " +  index  + ");\" title=\"test\">[T]</a></span> ";
	 index++;
	}	
	outStr = outStr + "</div>";
	document.getElementById('result').innerHTML = outStr;
	performAllMatches();
}


function processLookupEmailOfUser(req) {
	document.getElementById('result').innerHTML = "<p>Found e-mail address <a href=\"mailto:" + req.responseText + "\">" + req.responseText + "</a>. Looking up flickr with this information? <a href=\"javascript:lookupFlickrUserWithEmail('" + req.responseText + "');\" title=\"ok\">[K]</a> <a href=\"javascript:showMoreInfoAboutFlickrUser();\" title=\"more ...\">[M]</a></p>";
}

function lookupFlickrUserWithEmail(mail) {
	flickrUserMail = mail;
	document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">flickr lookup for user with e-mail address " + mail + "</span></p>";
	sendRequest('wrapper.php?user=' + mail, processFlickrUserLookup);	
}

function processFlickrUserLookup(req) {
	//alert(req.responseText);
	document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">found flickr user with ID " + req.responseText + " - retrieving info about user ...</span></p>";
	sendRequest('wrapper.php?aboutuser=' +  req.responseText, processAckFlickrUser);
}

function processAckFlickrUser(req) {
	document.getElementById('result').innerHTML = "<p>" + req.responseText + "</p><p>Assuming this is you at flickr? <a href=\"javascript:listPhotosOfUserAtFlickr();\" title=\"ok\">[K]</a> <a href=\"javascript:getUserURI();\" title=\"cancel\">[C]</a>  </p>";
}

function showMoreInfoAboutFlickrUser() {
	document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">processing FOAF document to show more information about user...</span></p>";
 	var URI = document.getElementById('URI').value;
	sendRequest('wrapper.php?aboutURI=' + URI, processMoreOnFOAF);	
}

function processMoreOnFOAF(req) {
	document.getElementById('result').innerHTML = "<p>" + req.responseText + "<a href=\"javascript:getUserURI();\" title=\"back\">[&lt;]</a></p>";
}


function listPhotosOfUserAtFlickr() {
	document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">flickr lookup for public photos of user with e-mail address " + flickrUserMail+ "</span></p>";
	sendRequest('wrapper.php?photos4user=' + flickrUserMail, processPhotosOfUserAtFlickr);	
}

function processPhotosOfUserAtFlickr(req) {
	var personURI = document.getElementById('URI').value;
	var buffer = req.responseText;
	var outStr = "<p>List of public photos of user at flickr:</p><div style=\"text-align:justify\">";
	//alert(urlencode(personURI));
		
	var splitArray = buffer.split(",");
	var index = 1;
 	for (var i = 0; i < splitArray.length-2; i++){
	 outStr = outStr + "<span id=\"mc-"+ index + "\">" + splitArray[i] + "<a id=\"mp-"+ index + "\" href=\"javascript:performMatch('" + splitArray[i] + "', '" + personURI + "', " +  index  + ");\" title=\"test\">[T]</a></span> ";
	 index++;
	}	
	outStr = outStr + "</div>";
	document.getElementById('result').innerHTML = outStr;
	performAllMatches();
}


function performMatch(photo, person, mID) {
	//alert(urlencode(person));
	sendRequest('wrapper.php?match=' + photo + "&personURI=" + urlencode(person), processMatchDone, mID);	
}

function processMatchDone(req, id) {
	var info = req.responseText;
	var elem = document.getElementById("mc-" + id);
	//alert(info.substring(0, 5));
	
	if(info.substring(0, 5) == '<p>no') {
		elem.innerHTML = "";
	}
	else {
		elem.innerHTML = req.responseText;		
	}
}

	
function performAllMatches() {
	count = 0;
	for(var i = 1; i <= 500; i++)	{
		var id = "mp-" + i;
		var elem = document.getElementById(id);
		if(elem && elem.href) {
			count += 1;
			setTimeout(elem.href, (count * 50));
		}
	}
}

function dumpInfo() {
	document.getElementById('result').innerHTML = "<p><span style=\"font-size: 100%; font-weight: bold; color: #f00\">querying CaMiCatzee for info about people depictions ...</span></p>";
	hideMenu();	
	sendRequest('wrapper.php?dumpInfo', processDumpInfo);		
}

function processDumpInfo(req) {
	document.getElementById('result').innerHTML = "<p>" + req.responseText + "</p>";
}

/* UTIL */
// http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_urlencode/
function urlencode(str) {
	var ret = str;
  ret = ret.toString();  
  ret = ret.replace(/#/g, '%23');
  return ret;
}


// http://mousewhisperer.co.uk/js_page.html
function getCacheBusterParam(){
	return  "&rcb=" + parseInt(Math.random()*99999999); 
}


/* GUI */

function hideMenu() {
	document.getElementById('menu').style.visibility = "hidden";	
}

function isMenuHidden() {
	if(menuStatus == "hidden") return true;
	else return false;
}

function showMenu() {	
	menuStatus = "visible";
	document.getElementById('menu').style.visibility = menuStatus;
}

function hideMenu() {	
	menuStatus = "hidden";	
	document.getElementById('menu').style.visibility = menuStatus;
}

function updateMenu(){
	if(isMenuHidden()) showMenu();
	else hideMenu();	
}

function showWelcome(){	
	
	var buffer =  "<p>\"Catch Me If You Can\" (CaMiCatzee) is a multimedia interlinking concept demonstrator. The main goal is to show how multimedia assets, such as still images, can be interlinked. We use <a href=\"http://flickr.com\">flickr</a> as a base for the User Contributed Interlinking of the still images. This demonstrator is powered by <a href=\"http://www.foaf-project.org\">FOAF</a>, <a href=\"http://arc.semsol.org\">ARC2</a>, and <a href=\"http://sindice.com\">Sindice</a>.</p>";
	buffer = buffer + "<p>For the start you maybe want to have a look at some examples:</p><ul>";
	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://www.w3.org/People/Berners-Lee/card','TimBL, W3C')\">Tim Berners-Lee</a></li>";	
	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://danbri.org/foaf.rdf','asemantics')\">Dan Brickley</a></li>";
 	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://richard.cyganiak.de/foaf.rdf','eswc2008')\">Richard Cyganiak</a></li>";
 	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://kmi.open.ac.uk/people/tom/rdf','eswc2008')\">Tom Heath</a></li>";
 	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://www.siegfried-handschuh.net/metadata/foaf.rdf','eswc2008')\">Sigi Handschuh</a></li>";
 	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://sw-app.org/mic.xhtml','eswc2008')\">Michael Hausenblas</a></li>";
 	buffer = buffer + "</ul>";
	document.getElementById('menu').innerHTML = buffer;
	updateMenu();		
}

function showAbout(){	
	document.getElementById('menu').innerHTML = "<p>\"Catch Me If You Can\" (CaMiCatzee) is a multimedia interlinking concept demonstrator. The main goal is to show how multimedia assets, such as still images, can be interlinked. We use <a href=\"http://flickr.com\">flickr</a> as a base for the User Contributed Interlinking of the still images. This demonstrator is powered by <a href=\"http://www.foaf-project.org\">FOAF</a>, <a href=\"http://arc.semsol.org\">ARC2</a>, and <a href=\"http://sindice.com\">Sindice</a>.<br /><br /><a href=\"about.html\">Read more ...</a></p>";
	updateMenu();
}

function showExamples(){	
	var buffer = "<p>Some examples already using interlinked still images on flickr:</p><ul>";
	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://www.w3.org/People/Berners-Lee/card','TimBL, W3C')\">Tim Berners-Lee</a></li>";	
	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://danbri.org/foaf.rdf','asemantics')\">Dan Brickley</a></li>";
 	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://richard.cyganiak.de/foaf.rdf','eswc2008')\">Richard Cyganiak</a></li>";
 	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://kmi.open.ac.uk/people/tom/rdf','eswc2008')\">Tom Heath</a></li>";
 	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://www.siegfried-handschuh.net/metadata/foaf.rdf','eswc2008')\">Sigi Handschuh</a></li>";
 	buffer = buffer + "<li><a href=\"javascript:setTagExample('http://sw-app.org/mic.xhtml','eswc2008')\">Michael Hausenblas</a></li>";
 	buffer = buffer + "</ul>";
	document.getElementById('menu').innerHTML = buffer;
	updateMenu();	
}

function setTagExample(foafURI, tag){
	document.getElementById('URI').value = foafURI;
	document.getElementById('tagvalue').value = tag;
}
