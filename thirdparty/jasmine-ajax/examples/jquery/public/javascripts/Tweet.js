function Tweet(tweet){
  this.postedAt = tweet.created_at;
  this.text = tweet.text;
  this.imageUrl = tweet.profile_image_url;
  this.user = tweet.from_user;
}
