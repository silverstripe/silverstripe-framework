/**
 * @author Mateusz
 */
var ImageBox = {
	
	initialize: function() {
		this.showIndicator = ImageBox.showIndicator.bind(this);
		this.hideIndicator = ImageBox.hideIndicator.bind(this);
		this.reCenterIndicator = ImageBox.reCenterIndicator.bind(this);
		this.centerIndicator = ImageBox.centerIndicator.bind(this);					
		this.center = ImageBox.center.bind(this);
		this.imageContainer = $('imageContainer');
		Element.hide(this.imageContainer);
	},
		
	showIndicator: function() {
		this.centerIndicator();
		indicator.style.display = 'inline';	
	},
	
	hideIndicator: function() {
		indicator = $('loadingIndicatorContainer');
		indicator.style.display = 'none';		
	},	
	
	centerIndicator: function() {
		indicator = $('loadingIndicatorContainer');
		indicatorImage = $('loadingIndicator');
		top = this.imageContainer.getTop();
		left = this.imageContainer.getLeft();
		width = this.imageContainer.getWidth();
		height = this.imageContainer.getHeight();
		indicator.style.left = left + width/2 - indicatorImage.width/2 + "px"; 
		indicator.style.top = top + height/2 - indicatorImage.height/2 + "px";		
	},
	
	reCenterIndicator: function() {
		if($('loadingIndicatorContainer').style.display == 'inline') {
			this.centerIndicator();
		}		
	},
	
	center: function() {
		document.title = this.imageContainer.getParentWidth();
		$('imageContainer').style.left = this.imageContainer.getParentWidth()/2 - this.imageContainer.getWidth()/2 + 'px';
		$('imageContainer').style.top = this.imageContainer.getParentHeight()/2 - this.imageContainer.getHeight()/2 + 'px';
		Element.show(this.imageContainer);
	}
	
	
};
