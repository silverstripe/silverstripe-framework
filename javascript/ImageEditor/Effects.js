/**
 * @author Mateusz
 */
var Effects = {
	initialize: function() {
		this.setListeners = Effects.setListeners.bind(this);
		this.rotate = Effects.rotate.bind(this);
		this.setListeners();	
	},
	
	rotate: function() {
		imageTransformation.rotate(90);
	},
	
	setListeners: function() {
		Event.observe('rotateButton','click',this.rotate);
	}	
}