var TwitSearch = function(){

  return {
    displayResults: function(tweets){
      var update_str = "";

      tweets.each(function(tweet) {
        update_str += "<li><img src='" + tweet.imageUrl + "' alt='" + tweet.user + " profile image' />" +
                      "<p>" + tweet.text + "</p>" +
                      "<p class='user'>" + tweet.user + "</p>" +
                      "<p class='timestamp'>" + tweet.postedAt + "</p>";

      });

      $("results").update(update_str);
    },

    searchFailure: function(response){
      $("results").update("<h2>Oops. Something went wrong.</h2>");
    },

    cleanup: function(){},

    rateLimitReached: function(){
      console.log("rate limited");
    },

    failWhale: function(){
      $("results").update("<img src='images/fail-whale.png' />");
    }
  }
}();
