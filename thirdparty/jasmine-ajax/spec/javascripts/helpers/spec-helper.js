jQuery.noConflict();

beforeEach(function() {
  clearAjaxRequests();

  spyOn(Ajax, "getTransport").andCallFake(function() {
    return new FakeXMLHttpRequest();
  });

  spyOn(jQuery.ajaxSettings, 'xhr').andCallFake(function() {
    var newXhr = new FakeXMLHttpRequest();
    ajaxRequests.push(newXhr);
    return newXhr;
  });
});
