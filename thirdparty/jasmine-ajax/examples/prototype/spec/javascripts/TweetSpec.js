describe("Tweet", function(){
  var tweet;

  beforeEach(function(){
    tweet = new Tweet(eval('(' + Tweets.noAtReply + ')'));
  });

  it("should create a pretty date", function(){
    expect(tweet.postedAt).toEqual("Thu, 29 Jul 2010 02:18:53 +0000");
  });

  it("should store the users messages", function(){
    expect(tweet.text).toEqual("Pres Obama on stage with the Foo fighters, jonas brothers and a whole lot of ppl..nice..");
  });

  it("should store the username", function(){
    expect(tweet.user).toEqual("_wbrodrigues");
  });

  it("stores the users messages", function(){
    expect(tweet.imageUrl).toEqual("http://a2.twimg.com/profile_images/1014111170/06212010155_normal.jpg");
  });

});
