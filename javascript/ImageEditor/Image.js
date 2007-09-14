/**
 * @author Mateusz
 */
var Image = {	
	initialize: function(imageFile) {
		this.image = $('image');
		this.image.src = imageFile;
		this.reportSize = Image.reportSize.bind(this);
		this.onImageLoad = Image.onImageLoad.bind(this);
		Event.observe('image','load',this.onImageLoad);
		imageHistory.add('initialize',this.image.src);
	},
	
	reportSize: function() {
		$('imageWidth').innerHTML = this.image.width + "px";
		$('imageHeight').innerHTML = this.image.height + "px";	
	},
	
	onImageLoad: function() {
		this.reportSize();
		if(resize.imageContainerResize.originalHeight == 0 && resize.imageContainerResize.originalWidth == 0) {
			imageBox.center();
		}
		$('imageContainer').style.width = this.image.width + 'px';
		$('imageContainer').style.height = this.image.height + 'px';
		resize.imageContainerResize.originalWidth = this.image.width;
		resize.imageContainerResize.originalHeight = this.image.height;

	},
};
