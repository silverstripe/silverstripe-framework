function TwitterApi () {
  this.baseUrl = "http://search.twitter.com/search.json"
}

TwitterApi.prototype.search = function(query, callbacks) {
  $.ajax({
    url: this.baseUrl,
    data: {
      q: query
    },
    type: "GET",
    success: function(data, status, request) {
      var tweets = [];
      $(data.results).each(function(index, result){
        tweets.push(new Tweet(result));
      });

      callbacks.onSuccess(tweets);
    },
    complete: callbacks.onComplete,
    error: function(request, status, error){
      errorStatus = request.status;

      if (errorStatus == "500") {
        callbacks.onFailure();
      } else if (errorStatus == "503") {
        callbacks.onFailWhale();
      }
    }
  });
}
