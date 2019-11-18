---
title: Controllers
summary: Controllers form the backbone of your SilverStripe application. They handle routing URLs to your templates.
introduction: In this guide you will learn how to define a Controller class and how they fit into the SilverStripe response and request cycle.
---

The [Controller](api:SilverStripe\Control\Controller) class handles the responsibility of delivering the correct outgoing [HTTPResponse](api:SilverStripe\Control\HTTPResponse) for a 
given incoming [HTTPRequest](api:SilverStripe\Control\HTTPRequest). A request is along the lines of a user requesting the homepage and contains 
information like the URL, any parameters and where they've come from. The response on the other hand is the actual 
content of the homepage and the HTTP information we want to give back to the user.

Controllers are the main handlers for functionality like interactive forms, rendering the correct templates and 
performing and navigating around the permission checks on the users actions.

[CHILDREN]

## Related Documentation

* [Execution Pipeline](../execution_pipeline)

## API Documentation

* [Controller](api:SilverStripe\Control\Controller)
* [HTTPRequest](api:SilverStripe\Control\HTTPRequest)
* [HTTPResponse](api:SilverStripe\Control\HTTPResponse)
