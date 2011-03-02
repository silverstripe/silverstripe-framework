Test Driving APIs with Jasmine
============
**Update**: Added a mock that can be used with jQuery. The example project is the same one as the Prototype example. If you are interested in using the jQuery mock, be sure to check out `jquery/spec/javascripts/helpers/jquery-mock-ajax.js` and `jquery/spec/javascripts/helpers/SpecHelper.js`.

This shows an example JavaScript app that uses Jasmine to mock Ajax requests/responses and spy on callbacks related with various responses. We are currently using separate mocks for Prototype and jQuery but will soon be adding a single mock that can be used with either.

Interesting Parts
------------
* `spec/javascripts/helpers/mock-ajax.js`: In order to mock out the actual HTTP requests, you'll want to include this file in your project and put it somewhere on Jasmine's helper lookup path. Including this file will do a number of things, including a way for you to define your own responses and tell your requests which one to use, as well as keep a list of Ajax requests for later inspection.
* `spec/javascripts/helpers/test_responses/search.js`: By defining responses with various status codes and content, you can set expectations with Jasmine about what should happen in each of those situations. For example, you might create test responses for status codes of 200, 404, 500, and whatever other responses codes are relevant to the API you are working with. You can then hand these test responses to the Ajax mocks you create, then set expectations on which callbacks should be called in each of those contexts.

Jasmine
------------
http://github.com/pivotal/jasmine

Copyright (c) 2010 Pivotal Labs. This software is licensed under the MIT License.
