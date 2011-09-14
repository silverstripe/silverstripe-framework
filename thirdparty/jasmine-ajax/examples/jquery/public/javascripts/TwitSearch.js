var TwitSearch = function(){

  return {
    displayResults: function(tweets){
      var updateStr = "";

      $(tweets).each(function(index, tweet) {
        updateStr += "<li><img src='" + tweet.imageUrl + "' alt='" + tweet.user + " profile image' />" +
                      "<p>" + tweet.text + "</p>" +
                      "<p class='user'>" + tweet.user + "</p>" +
                      "<p class='timestamp'>" + tweet.postedAt + "</p>";

      });

      $("#results").html(updateStr);
    },

    searchFailure: function(response){
      $("#results").html("<h2>Oops. Something went wrong.</h2>");
    },

    cleanup: function(){},

    rateLimitReached: function(){
      console.log("rate limited");
    },

    failWhale: function(){
      $("#results").html("<img src='images/fail-whale.png' />");
    }
  }
}();
