/**
 * @author Mateusz
 */
var ImageTransformation = {
	initialize: function() {
		this.resize = ImageTransformation.resize.bind(this);
		this.rotate = ImageTransformation.rotate.bind(this);
		this.crop = ImageTransformation.crop.bind(this);	
	},
		
	resize: function(width,height) {
		if(imageHistory.modifiedOriginalImage) {
			fileToResize = $('image').src;
		} else {
			fileToResize = imageEditor.originalImageFile;
		}	
		var options = {
		 	method: 'post',
			postBody: 'command=resize&file=' + fileToResize + '&newImageWidth=' + width + '&newImageHeight=' + height,
			onSuccess: function(transport) {
				imageBox.hideIndicator();
				response = eval('(' + transport.responseText + ')');
				$('image').src = response.fileName;
				imageHistory.add('resize',$('image').src);				
			}			
		 };
		 imageBox.showIndicator();
		 new Ajax.Request('/ajax.php', options);	
	},
	
	rotate: function(angle) {
		var options = {
		 	method: 'post',
			postBody: 'command=rotate&file=' + $('image').src + '&angle=' + angle ,
			onSuccess: function(transport) {
				imageBox.hideIndicator();
				response = eval('(' + transport.responseText + ')');
				$('image').src = response.fileName;
				$('imageContainer').style.width = response.width + 'px';
				$('imageContainer').style.height = response.height + 'px';
				imageHistory.add('rotate',$('image').src);	
				resize.imageContainerResize.placeClickBox();			
			}			
		 };
		 imageBox.showIndicator();
		 new Ajax.Request('/ajax.php', options);		
	},
	
	crop: function(top,left,width,height) {
		var options = {
		 	method: 'post',
			postBody: 'command=crop&file=' + $('image').src + '&top=' + top + '&left=' + left + '&width=' + width + '&height=' + height,
			onSuccess: function(transport) {
				imageBox.hideIndicator();
				response = eval('(' + transport.responseText + ')');
				$('image').src = response.fileName;
				$('imageContainer').style.width = response.width + 'px';
				$('imageContainer').style.height = response.height + 'px';
				imageHistory.add('crop',$('image').src);	
				crop.setVisible(false);
			}			
		 };
		 imageBox.showIndicator();
		 new Ajax.Request('/ajax.php', options);			
	}
}
	
