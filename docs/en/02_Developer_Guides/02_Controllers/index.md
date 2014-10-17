title: Controllers
summary: Controllers form the backbone of your SilverStripe application. They handle routing URLs to your templates.
introduction: In this guide you will learn how to define a Controller class and how they fit into the SilverStripe response and request cycle.

The [api:Controller] class handles the responsibility of delivering the correct outgoing [api:SS_HTTPResponse] for a 
given incoming [api:SS_HTTPRequest]. A request is along the lines of a user requesting the homepage and contains 
information like the URL, any parameters and where they've come from. The response on the other hand is the actual 
content of the homepage and the HTTP information we want to give back to the user.

Controllers are the main handlers for functionality like interactive forms, rendering the correct templates and 
performing and navigating around the permission checks on the users actions.

[CHILDREN]

## Related Documentation

* [Execution Pipeline](../execution_pipeline)

## API Documentation

* [api:Controller]
* [api:SS_HTTPRequest]
* [api:SS_HTTPResponse]