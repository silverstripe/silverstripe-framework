# Restful Service

## Introduction

`[api:RestfulService]` enables connecting to remote web services which supports REST interface and consume those web services
(for example [Flickr](http://www.flickr.com/services/api/), [Youtube](http://code.google.com/apis/youtube/overview.html), Amazon and etc). `[api:RestfulService]` can parse the XML response (sorry no JSON support)
returned from the web service. Further it supports caching of the response, and you can customize the cache interval. 

To gain the functionality you can either create a new `[api:RestfulService]` object or create a class extending the
RestfulService (see [flickrservice](http://silverstripe.org/flickr-module/) and
[youtubeservice](http://silverstripe.org/youtube-gallery-module/) modules).


## Examples

### Creating a new RestfulObject

	:::php
	 //example for using RestfulService to connect and retrive latest twitter status of an user.
	$twitter = new RestfulService("http://twitter.com/statuses/user_timeline/user.xml", $cache_expiry );
			$params = array('count' => 1);
			$twitter->setQueryString($params);
			$conn = $twitter->connect();
			$msgs = $twitter->getValues($conn, "status");


### Extending to a new class

	:::php
	//example for extending RestfulService
	class FlickrService extends RestfulService {
		
		public function __construct($expiry=NULL){
			parent::__construct('http://www.flickr.com/services/rest/', $expiry);
			$this->checkErrors = true;
		}
	......


### Multiple requests by using the $subURL argument on connect()

	:::php
	// Set up REST service
	$service = new RestfulService("http://example.harvestapp.com");
	$service->basicAuth('username', 'password');
	$service->httpHeader('Accept: application/xml');
	$service->httpHeader('Content-Type: application/xml');
	
	$peopleXML = $service->connect('/people');
	$people = $service->getValues($peopleXML, 'user');
	
	...
	
	$taskXML = $service->connect('/tasks');
	$tasks = $service->getValues($taskXML, 'task');



## Features

### Caching 

To set the cache interval you can pass it as the 2nd argument to constructor.

	:::php
	new RestfulService("http://twitter.com/statuses/user_timeline/user.xml", 3600 );


### Getting Values & Attributes

You can traverse throught document tree to get the values or attribute of a particular node.
for example you can traverse 

	:::xml
	  <entries>
	     <entry id='12'>Sally</entry>
	     <entry id='15'>Ted</entry>
	     <entry id='30'>Matt</entry>
	     <entry id='22'>John</entry>
	  <entries>

to extract the id attributes of the entries use:

	:::php
	$this->getAttributes($xml, "entries", "entry") //will return all attributes of each entry node


to extract the values (the names) of the entries use: 

	:::php
	$this->getValues($xml, "entries", "entry") //will return all values of each entry node


### Searching for Values & Attributes

If you don't know the exact position of dom tree where the node will appear you can use xpath to search for the
node.Recommended for retrieving values of namespaced nodes.

	:::xml
	  <media:guide>
	     <media:entry id="2030">video</media:entry>
	  </media:guide>

to get the value of entry node with the namespace media, use:

	:::php
	  $this->searchValue($response, "//media:guide/media:entry")



## Best Practices


### Handling Errors

If the web service returned an error (for example, API key not available or inadequate parameters) `[api:RestfulService]`
could delgate the error handling to it's descendant class. To handle the errors define a function called errorCatch

	:::php
	        /*
		This will raise Youtube API specific error messages (if any).
	
		*/
		public function errorCatch($response){
			$err_msg = $response;
		 if(strpos($err_msg, '<') === false)
			//user_error("YouTube Service Error : $err_msg", E_USER_ERROR);
		 	user_error("YouTube Service Error : $err_msg", E_USER_ERROR);
		 else
		 	return $response;
		}



If you want to bypass error handling on your sub-classes you could define that in the constructor.

	:::php
	  public function __construct($expiry=NULL){
			parent::__construct('http://www.flickr.com/services/rest/', $expiry);
			$this->checkErrors = false; //Set checkErrors to false to bypass error checking
		}


## Other Uses

### How to use `[api:RestfulService]` to easily embed an RSS feed

`[api:RestfulService]` can be used to easily embed an RSS feed (since it's also an xml response) from a site
such as del.icio.us

Put something like this code in mysite/code/Page.php inside class Page_Controller

	:::php
		// Accepts an RSS feed URL and outputs a list of links from it
		public function RestfulLinks($url){
			$delicious = new RestfulService($url);
			
			$conn = $delicious->connect();
			$result = $delicious->getValues($conn, "item");
			$output = '';
			foreach ($result as $key => $value) {
				// Fix quote encoding
				$description = str_replace('&amp;quot;', '&quot;', $value->description);
				$output .=  '<li><a href="'.$value->link.'">'.$value->title.'</a><br />'.$description.'</li>';
			}
			return $output;
		}


Put something like this code in `themes/<your-theme>/templates/Layout/HomePage.ss`:

	:::ss
	<h3>My Latest Del.icio.us Links</h3>
	<ul>
		$RestfulLinks(http://del.icio.us/rss/elijahlofgren) 
	</ul>


## API Documentation
`[api:RestfulService]`