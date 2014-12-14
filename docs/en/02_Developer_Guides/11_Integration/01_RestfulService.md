summary: Consume external data through their RESTFul interfaces.

# Restful Service

`[api:RestfulService]` is used to enable connections to remote web services through PHP's `curl` command. It provides an
interface and utility functions for generating a valid request and parsing the response returned from the web service. 

<div class="alert" markdown="1">
RestfulService currently only supports XML. It has no JSON support at this stage.
</div>

## Examples

### Creating a new RestfulObject

`getWellingtonWeather` queries the Yahoo Weather API for an XML file of the latest weather information. We pass a query 
string parameter `q` with the search query and then convert the resulting XML data to an `ArrayList` object to display 
in the template.

**mysite/code/Page.php**

	:::php
	public function getWellingtonWeather() {
		$fetch = new RestfulService(
			'https://query.yahooapis.com/v1/public/yql'
		);
		
		$fetch->setQueryString(array(
			'q' => 'select * from weather.forecast where woeid in (select woeid from geo.places(1) where text="Wellington, NZ")'
		));
		
		// perform the query
		$conn = $fetch->request();

		// parse the XML body
		$msgs = $fetch->getValues($conn->getBody(), "results");

		// generate an object our templates can read
		$output = new ArrayList();

		if($msgs) {
			foreach($msgs as $msg) {
				$output->push(new ArrayData(array(
					'Description' => Convert::xml2raw($msg->channel_item_description)
				)));
			}
		}

		return $output;
	}

## Features

### Basic Authenication

	:::php
	$service = new RestfulService("http://example.harvestapp.com");
	$service->basicAuth('username', 'password');

### Make multiple requests

	:::php
	$service = new RestfulService("http://example.harvestapp.com");

	$peopleXML = $service->request('/people');
	$people = $service->getValues($peopleXML, 'user');

	// ...

	$taskXML = $service->request('/tasks');
	$tasks = $service->getValues($taskXML, 'task');


### Caching

To set the cache interval you can pass it as the 2nd argument to constructor.

	:::php
	$expiry = 60 * 60; // 1 hour;

	$request = new RestfulService("http://example.harvestapp.com", $expiry );


### Getting Values & Attributes

You can traverse through document tree to get the values or attribute of a particular node using XPath. Take for example
the following XML that is returned.

	:::xml
	<entries>
	     <entry id='12'>Sally</entry>
	     <entry id='15'>Ted</entry>
	     <entry id='30'>Matt</entry>
	     <entry id='22'>John</entry>
	</entries>

To extract the id attributes of the entries use:

	:::php
	$this->getAttributes($xml, "entries", "entry");

	// array(array('id' => 12), array('id' => '15'), ..)

To extract the values (the names) of the entries use:

	:::php
	$this->getValues($xml, "entries", "entry");

	// array('Sally', 'Ted', 'Matt', 'John')

### Searching for Values & Attributes

If you don't know the exact position of DOM tree where the node will appear you can use xpath to search for the node. 

<div class="note">
This is the recommended method for retrieving values of name spaced nodes.
</div>

	:::xml
	<media:guide>
	     <media:entry id="2030">video</media:entry>
	</media:guide>

To get the value of entry node with the namespace media, use:

	:::php
	$this->searchValue($response, "//media:guide/media:entry");

	// array('video');


## Best Practices

### Handling Errors

If the web service returned an error (for example, API key not available or inadequate parameters), 
`[api:RestfulService]` can delegate the error handling to it's descendant class. To handle the errors, subclass 
`RestfulService and define a function called errorCatch.

	:::php
	<?php

	class MyRestfulService extends RestfulService {

		public function errorCatch($response) {
			$err_msg = $response;
			
			if(strpos($err_msg, '<') === false) {
				user_error("YouTube Service Error : $err_msg", E_USER_ERROR);
			}

			return $response;
		}
	}

If you want to bypass error handling, define `checkErrors` in the constructor for `RestfulService`

	:::php
	<?php

	class MyRestfulService extends RestfulService {

		public function __construct($expiry = NULL) {
			parent::__construct('http://www.flickr.com/services/rest/', $expiry);
			
			$this->checkErrors = false;
		}
	}

## How to's

* [Embed an RSS Feed](how_to/embed_rss)

## API Documentation

* `[api:RestfulService]`
