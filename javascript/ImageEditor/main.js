/**
 * @author Mateusz
 */
var ImageEditor = {
	initialize: function(imageFile) {
		imageHistory = new ImageHistory.initialize();
		environment = new Environment.initialize(imageFile);		
		imageTransformation = new ImageTransformation.initialize();
		resize = new Resize.initialize($('imageContainer'));
		effects = new Effects.initialize();	
		crop = new Crop.initialize();
		this.originalImageFile = imageFile;		
	}		
}
imageEditor = new ImageEditor.initialize('/img/test.jpg');


	
	