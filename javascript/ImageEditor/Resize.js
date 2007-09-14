/**
 * @author Mateusz
 */
var Resize = {
	
	initialize: function(element) {		
		this.element = element;
		this.leftBoxConstraint =  20;
		this.topBoxConstraint =  100;
		this.getRelativeMousePos = Resize.getRelativeMousePos.bind(this);
		options = {
				resizeStop: Resize.resizeStop.bind(this),
				onDrag: Resize.onDrag.bind(this),
				onResize: Resize.onResize,
				getMousePos: Resize.getMousePos.bind(this)
			};		
		new Positioning.addBehaviour(this.element);
		this.imageContainerResize = new Resizeable.initialize(element,options);
		this.imageContainerResize.setVisible(true);
	},
	
	resizeStop: function(event) {
		if(EventStack.getLastEvent() != null) {
			imageElement = $('image');
			EventStack.clearStack();
			if(this.imageContainerResize.originalWidth != imageElement.width || this.imageContainerResize.originalHeight != imageElement.height) {
				imageTransformation.resize(imageElement.width,imageElement.height);
			}	
		}
	},
	
	onDrag: function()
	{
		if(this.element.getTop() < this.topBoxConstraint) this.element.style.top = this.topBoxConstraint + "px";
		if(this.element.getLeft() < this.leftBoxConstraint) this.element.style.left = this.leftBoxConstraint + "px";
		if(this.element.getTop() + this.element.getHeight() >= this.element.getParentHeight()) this.element.style.top = this.element.getParentHeight() - this.element.getHeight() - 3 + 'px';
		if(this.element.getLeft() + this.element.getWidth() > this.element.getParentWidth()) this.element.style.left = this.element.getParentWidth() - this.element.getWidth() - 3 + 'px';
		imageBox.reCenterIndicator();		
	},
	
 	onResize: function() {
	},
	getMousePos: function(event) {
		relativeMouseX = this.getRelativeMousePos(event).x;
		relativeMouseY = this.getRelativeMousePos(event).y;
		if(relativeMouseX <= this.leftBoxConstraint) x = this.leftBoxConstraint + this.element.getParentLeft(); else x = relativeMouseX + this.element.getParentLeft();
		if(relativeMouseY <= this.topBoxConstraint) y = this.topBoxConstraint + this.element.getParentTop(); else y = relativeMouseY + this.element.getParentTop();
		if(relativeMouseX >= this.element.getParentWidth()) {
			x = this.element.getParentLeft() + this.element.getParentWidth();
		}
		if(relativeMouseY >= this.element.getParentHeight()) y = this.element.getParentTop() + this.element.getParentHeight();
		return {x: x,y: y};				
	},
	
	getRelativeMousePos: function(event) {
		relativeMouseX = Event.pointerX(event) - this.element.getParentLeft();
		relativeMouseY = Event.pointerY(event) - this.element.getParentTop();
		return {x: relativeMouseX,y: relativeMouseY};				
	}
}