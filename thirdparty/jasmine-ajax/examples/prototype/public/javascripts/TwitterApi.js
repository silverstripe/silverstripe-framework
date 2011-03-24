function TwitterApi () {
  this.baseUrl = "http://search.twitter.com/search.json"
}

TwitterApi.prototype.search = function(query, callbacks) {
  this.currentRequest = new Ajax.Request(this.baseUrl, {
    method: 'get',
    parameters: {
      q: query
    },

    onSuccess: function(response){
      var tweets = [];
      response.responseJSON.results.each(function(result){
        tweets.push(new Tweet(result));
      });

      callbacks.onSuccess(tweets);
    },
    onFailure:  callbacks.onFailure,
    onComplete: callbacks.onComplete,
    on503:      callbacks.onFailWhale
  });
};