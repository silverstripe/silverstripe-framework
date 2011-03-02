describe("TwitterApi#search", function(){
  var twitter, request;
  var onSuccess, onFailure, onComplete, onFailWhale;

  beforeEach(function(){
    onSuccess = jasmine.createSpy('onSuccess');
    onFailure = jasmine.createSpy('onFailure');
    onComplete = jasmine.createSpy('onComplete');
    onFailWhale = jasmine.createSpy('onFailWhale');

    twitter = new TwitterApi();

    twitter.search('basketball', {
      onSuccess: onSuccess,
      onFailure: onFailure,
      onComplete: onComplete,
      onFailWhale: onFailWhale
    });

    request = mostRecentAjaxRequest();
  });
  
  it("calls Twitter with the correct url", function(){
     expect(request.url).toEqual("http://search.twitter.com/search.json?q=basketball")
  });
  
  describe("on success", function(){
    beforeEach(function(){
      request.response(TestResponses.search.success);
    });

    it("should call the provided success callback", function() {
      expect(onSuccess).toHaveBeenCalled();
    });

    it("calls onComplete", function(){
      expect(onComplete).toHaveBeenCalled();
    });

    it("does not call onFailure", function(){
      expect(onFailure).not.toHaveBeenCalled();
    });

    it("call convert the server response to Tweet objects", function(){
      var results = onSuccess.mostRecentCall.args[0];

      expect(results.length).toEqual(15);
      expect(results[0]).toEqual(jasmine.any(Tweet));
    });  
  });

  describe('on failure', function(){
     beforeEach(function(){
       request.response(TestResponses.search.failure);
     });

     it("calls onFailure", function() {
       expect(onFailure).toHaveBeenCalled();
     });

     it("call onComplete", function(){
       expect(onComplete).toHaveBeenCalled();
     });

     it("does not call onSuccess", function(){
       expect(onSuccess).not.toHaveBeenCalled();
     });
  });

  describe("on fail whale", function(){
     beforeEach(function(){
       request.response(TestResponses.search.failWhale);
     });

     it("calls onFailWhale", function(){
       expect(onFailWhale).toHaveBeenCalled();
     });

     it("does not call onSuccess", function(){
       expect(onSuccess).not.toHaveBeenCalled();
     });

     it("calls onComplete", function(){
       expect(onComplete).toHaveBeenCalled();
     });
  });
});