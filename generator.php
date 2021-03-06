<?php
header('Content-Type: application/json');
ini_set('display_errors', false);
error_reporting(E_ALL ^ E_NOTICE);


$supported_extensions = array
(
".com" => array("whois_server" => "whois.verisign-grs.com"),
".net" => array("whois_server" => "whois.verisign-grs.com"),
".org" => array("whois_server" => "whois.publicinterestregistry.net"),
".info" => array("whois_server" => "whois.afilias.info"),
".biz" => array("whois_server" => "whois.biz"),
".co.uk" => array("whois_server" => "whois.nic.uk"),
".ca" => array("whois_server" => "whois.cira.ca"),
".io" => array("whois_server" => "whois.nic.io"),
".co" => array("whois_server" => "whois.nic.co")
);

$extensions_array = array_keys($supported_extensions);

if(isset($_GET['search']) AND $_GET['search'] != "" AND !empty($_GET['search'])){
	$searchTerm = urldecode($_GET['search']);
}

if(isset($_GET['generate']) AND $_GET['generate'] == "random")
{

	// Trim post values and make lower-case.

	foreach($_POST as $key => $value){$_POST[$key] = strtolower(trim($value));}

	// Check submitted values.

	$errors = array();

	// Check domain and extension are present and have values.


	if(isset($searchTerm))
	{

		// Remove spaces.
		$searchTerm = str_replace(" ","",$searchTerm);

		// Check length of domain.
		if(strlen($searchTerm) > 63){$errors[] = "Domain is too long.  Max 63 characters.";}

		// Check domain for acceptable characters.
		if(!preg_match('/^[0-9a-zA-Z-]+$/i',$searchTerm)){$errors[] = "Domain may only contain numbers, letters or hyphens.";}

		// Check domain doesn't begin or end with a hyphen.
		if(substr(stripslashes($searchTerm),0,1) == "-" || substr(stripslashes($searchTerm),-1) == "-"){$errors[] = "Domain may not begin or end with a hyphen.";}

		$domain = $searchTerm;
	}
	else
	{
		$nouns = file("nouns.txt");
		$nouns = $nouns[array_rand($nouns)];

		$verbs = file("verbs.txt");
		$verbs = $verbs[array_rand($verbs)];

		//exit(trim($nouns) . "----" . trim($verbs));
		
		$domain = trim($nouns) . trim($verbs);
	}


	// Check result for no-match phrase.
	$domain_registered_message = "";
	//$domain = $_POST['domain'];

	$domain_message_return = array();


	// loop all extensions
	foreach($supported_extensions as $domainExt=>$domainExtValue)
	{

		if(!count($errors))
		{
			$extension = $domainExt;
			$connection_timeout = 5;

			$whois_servers = array
			(

			"whois.afilias.info" => array("port" => "43","query_begin" => "","query_end" => "\r\n","redirect" => "0","redirect_string" => "","no_match_string" => "NOT FOUND","match_string" => "Domain Name:","encoding" => "UTF-8"),

			"whois.audns.net.au" => array("port" => "43","query_begin" => "","query_end" => "\r\n","redirect" => "0","redirect_string" => "","no_match_string" => "No Data Found","match_string" => "Domain Name:","encoding" => "UTF-8"),

			"whois.biz" =>          array("port" => "43","query_begin" => "","query_end" => "\r\n","redirect" => "0","redirect_string" => "","no_match_string" => "Not found:","match_string" => "Registrant Name:","encoding" => "iso-8859-1"),

			"whois.cira.ca" =>      array("port" => "43","query_begin" => "","query_end" => "\r\n","redirect" => "0","redirect_string" => "","no_match_string" => "Domain status:         available","match_string" => "Domain status:         registered","encoding" => "UTF-8"),

			"whois.nic.uk" =>       array("port" => "43","query_begin" => "","query_end" => "\r\n","redirect" => "0","redirect_string" => "","no_match_string" => "No match for","encoding" => "iso-8859-1"),

			"whois.publicinterestregistry.net" => array("port" => "43","query_begin" => "","query_end" => "\r\n","redirect" => "0","redirect_string" => "","no_match_string" => "NOT FOUND","encoding" => "iso-8859-1"),

			"whois.verisign-grs.com" =>   array("port" => "43","query_begin" => "domain ","query_end" => "\r\n","redirect" => "1","redirect_string" => "Whois Server:","no_match_string" => "No match for domain","encoding" => "iso-8859-1"),

			"whois.nic.io" =>   array("port" => "43","query_begin" => "","query_end" => "\r\n","redirect" => "0","redirect_string" => "","no_match_string" => "is available for purchase","match_string" => "Status :  Live","encoding" => "iso-8859-1"),

			"whois.nic.co" =>   array("port" => "43","query_begin" => "","query_end" => "\r\n","redirect" => "0","redirect_string" => "","no_match_string" => "Not found","match_string" => "Domain ID:","encoding" => "iso-8859-1")


			);

			$whois_server = $supported_extensions[$extension]['whois_server'];
			$port = $whois_servers[$whois_server]['port'];
			$query_begin = $whois_servers[$whois_server]['query_begin'];
			$query_end = $whois_servers[$whois_server]['query_end'];
			$whois_redirect_check = $whois_servers[$whois_server]['redirect'];
			$whois_redirect_string = $whois_servers[$whois_server]['redirect_string'];
			$no_match_string = $whois_servers[$whois_server]['no_match_string'];
			$encoding = $whois_servers[$whois_server]['encoding'];

			$whois_redirect_server = "";
			$response = "";
			$line = "";



			$fp = fsockopen($whois_server,$port,$errno,$errstr,$connection_timeout);





			if(!$fp)
			{
				//print "fsockopen() error when trying to connect to {$whois_server}<br><br>Error number: ".$errno."<br>"."Error message: ".$errstr; exit;
				continue;
			}

			fputs($fp,$query_begin.$domain.$extension.$query_end);

			while(!feof($fp))
			{
				$line = fgets($fp);

				$response .= $line;

				// Check for whois redirect server.

				if($whois_redirect_check && stristr($line,$whois_redirect_string))
				{
					$whois_redirect_server = trim(str_replace($whois_redirect_string,"",$line));
					break;
				}
			}
			fclose($fp);

			// Query redirect server if set.

			if($whois_redirect_server)
			{
				$whois_server = $whois_redirect_server;
				$port = "43";
				$connection_timeout = 5;
				$query_begin = "";
				$query_end = "\r\n";

				$response = "";

				$fp = fsockopen($whois_server,$port,$errno,$errstr,$connection_timeout);
				if(!$fp)
				{
					//print "fsockopen() error when trying to connect to {$whois_server}<br><br>Error number: ".$errno."<br>"."Error message: ".$errstr; exit;
					continue;
				}
				fputs($fp,$query_begin.$domain.$extension.$query_end);

				while(!feof($fp)){$response .= fgets($fp);}
				fclose($fp);
			}

			if(stristr($response,$no_match_string))
			{
				$domain_registered_message .= "<span style=\"color:#009900\"><b><a href=\"http://www.anrdoezrs.net/click-8109747-10650211-1460541091000\">{$domain}{$extension}</a> is not registered</b></span></br>";
				$domain_message_return[] = $domain.$extension . "|yes";
			}
			else
			{
				$domain_registered_message .= "<b>{$domain}{$extension} is registered</b><br>";
				$domain_message_return[] = $domain.$extension . "|no";
			}

		}

	}

}

// This function grabs the definition of a word in XML format.
function grab_xml_definition ($word, $ref, $key)
{
	$uri = "http://www.dictionaryapi.com/api/v1/references/" . urlencode($ref) . "/xml/" . urlencode($word) . "?key=" . urlencode($key);
    return file_get_contents($uri);
};

$xdef = grab_xml_definition("test", "collegiate", "f38770b2-72a1-4753-a067-c10cc73fc245");

// Set a default encoding for the form page.  If a WHOIS server uses a particular encoding it will be set above if the form is posted without errors.

if(!isset($encoding)){$encoding = "UTF-8";}

?>

<?php
// Print any errors.
if(isset($errors) && count($errors))
{
	foreach($errors as $value){print "<span class=\"error_messages\"><b>".stripslashes($value)."</b></span><br>";}
	print "<br>";
}
?>

<?php

$jsonArray = array();
$string = '';

if(isset($domain_registered_message) && !empty($domain_registered_message)){
	//$jsonArray[] = $domain_registered_message;
	$string .= $domain_registered_message .  "<br>";
}

/*
$domain_message_return['meaning'] = grab_xml_definition($domain, "collegiate", "f38770b2-72a1-4753-a067-c10cc73fc245");

echo $domain . "<br>";
echo $domain_message_return['meaning']; exit;
*/

//echo json_encode($jsonArray);
//echo $string;
echo json_encode($domain_message_return);

?>
