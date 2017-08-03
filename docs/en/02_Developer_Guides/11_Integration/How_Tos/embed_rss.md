title: Embed an RSS Feed

# Embed an RSS Feed

[RestfulService](api:RestfulService) can be used to easily embed an RSS feed from a site. In this How to we'll embed the latest 
weather information from the Yahoo Weather API.

First, we write the code to query the API feed.

**mysite/code/Page.php**


```php
	public function getWellingtonWeather() 
	{
		$fetch = new RestfulService(
			'https://query.yahooapis.com/v1/public/yql'
		);
		
		$fetch->setQueryString([
			'q' => 'select * from weather.forecast where woeid in (select woeid from geo.places(1) where text="Wellington, NZ")'
		]);
		
		// perform the query
		$conn = $fetch->request();

		// parse the XML body
		$msgs = $fetch->getValues($conn->getBody(), "results");

		// generate an object our templates can read
		$output = new ArrayList();

		if($msgs) {
			foreach($msgs as $msg) {
				$output->push(new ArrayData([
					'Description' => Convert::xml2raw($msg->channel_item_description)
				]));
			}
		}

		return $output;
	}

```

This will provide our `Page` template with a new `WellingtonWeather` variable (an [ArrayList](api:SilverStripe\ORM\ArrayList)). Each item has a 
single field `Description`.

**mysite/templates/Page.ss**


```ss

	<% if WellingtonWeather %>
	<% loop WellingtonWeather %>
		$Description
	<% end_loop %>
	<% end_if %>
```

## Related

* [RestfulService Documentation](../restfulservice)
* [RestfulService](api:RestfulService)
