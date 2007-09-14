/**
 * @author Mateusz
 */
Resizeable = {
	
	initialize: function(element,options) {		
		this.resizeStop = options.resizeStop.bind(this);
		this.onDrag = options.onDrag.bind(this);
		this.customOnResize = options.onResize.bind(this);
		this.getMousePos = options.getMousePos.bind(this);
		this.bindAll = Resizeable.bindAll.bind(this);
		this.bindAll();
		this.element = element;
		this.createClickBoxes();
		this.setListeners();
		this.placeClickBoxOnImageLoad();
		this.originalHeight = 0;
		this.originalWidth = 0;
	},
	
	resizeStart: function(event) {
		EventStack.addEvent(event);
		Event.stop(event);
	},
	
	leftUpperDrag: function(event,top,left,height,width,parentTop,parentLeft,relativeMouseX,relativeMouseY) {
		newWidth = left - relativeMouseX + width;
		newHeight = top - relativeMouseY + height;
		if(this.resize(newWidth,newHeight)) { 
			this.element.style.top = top - (newHeight - height) + "px";
			this.element.style.left = left - (newWidth - width) + "px";			
		}		
	},
	
	leftMiddleDrag: function(event,top,left,height,width,parentTop,parentLeft,relativeMouseX,relativeMouseY) {
		newWidth = left - relativeMouseX + width;											 
		if(this.resize(newWidth,-1000)) this.element.style.left = left - (left - relativeMouseX) + "px";	
	},
	
	leftLowerDrag: function(event,top,left,height,width,parentTop,parentLeft,relativeMouseX,relativeMouseY) {
		newWidth = left - relativeMouseX + width;											 
		newHeight = relativeMouseY - (top + height) + height;
		if(this.resize(newWidth,newHeight)) this.element.style.left = left - (left - relativeMouseX) + "px";
	},
	
	rightUpperDrag: function(event,top,left,height,width,parentTop,parentLeft,relativeMouseX,relativeMouseY) {
		newWidth = relativeMouseX - left - width + width;
		newHeight = top - relativeMouseY + height; 
		if(this.resize(newWidth,newHeight)) this.element.style.top = (top - (newHeight - height) ) + 'px';	
	},
	
	rightMiddleDrag: function(event,top,left,height,width,parentTop,parentLeft,relativeMouseX,relativeMouseY) {
		newWidth = relativeMouseX - left; 
		this.resize(newWidth,-1000);	
	},
	
	rightLowerDrag: function(event,top,left,height,width,parentTop,parentLeft,relativeMouseX,relativeMouseY) {
		newWidth = relativeMouseX - left;
		newHeight = relativeMouseY - top;
		this.resize(newWidth,newHeight);	
	},
	
	upperMiddleDrag: function(event,top,left,height,width,parentTop,parentLeft,relativeMouseX,relativeMouseY) {
		newHeight = top - relativeMouseY + height; 
		if(this.resize(-1000,newHeight)) {
			this.element.style.top = (top - (newHeight - height)) + 'px';								
		}
	},
	
	lowerMiddleDrag: function(event,top,left,height,width,parentTop,parentLeft,relativeMouseX,relativeMouseY) {
		newHeight = relativeMouseY - (top + height) + height;						
		this.resize(-1000,newHeight);
	},
	
	onResize: function(event) {		
		if(EventStack.getLastEvent() != null && this.isVisible) {					
			lastEventElement = Event.element(EventStack.getLastEvent());
			var relativeMouseX = this.getMousePos(event).x - this.element.getParentLeft();
			var relativeMouseY = this.getMousePos(event).y - this.element.getParentTop();
			if(Element.hasClassName(lastEventElement,'leftUpperClickBox')) {
				this.leftUpperDrag(event,this.element.getTop(),this.element.getLeft(),this.element.getHeight(),this.element.getWidth(),this.element.getParentTop(),this.element.getParentLeft(),relativeMouseX,relativeMouseY);						
			}
			if(Element.hasClassName(lastEventElement,'leftMiddleClickBox')) {
				this.leftMiddleDrag(event,this.element.getTop(),this.element.getLeft(),this.element.getHeight(),this.element.getWidth(),this.element.getParentTop(),this.element.getParentLeft(),relativeMouseX,relativeMouseY);						
			}
			if(Element.hasClassName(lastEventElement,'leftLowerClickBox')) {
				this.leftLowerDrag(event,this.element.getTop(),this.element.getLeft(),this.element.getHeight(),this.element.getWidth(),this.element.getParentTop(),this.element.getParentLeft(),relativeMouseX,relativeMouseY);						
			}
			if(Element.hasClassName(lastEventElement,'rightUpperClickBox')) {
				this.rightUpperDrag(event,this.element.getTop(),this.element.getLeft(),this.element.getHeight(),this.element.getWidth(),this.element.getParentTop(),this.element.getParentLeft(),relativeMouseX,relativeMouseY);						
			}
			if(Element.hasClassName(lastEventElement,'rightMiddleClickBox')) {
				this.rightMiddleDrag(event,this.element.getTop(),this.element.getLeft(),this.element.getHeight(),this.element.getWidth(),this.element.getParentTop(),this.element.getParentLeft(),relativeMouseX,relativeMouseY);						
			}
			if(Element.hasClassName(lastEventElement,'rightLowerClickBox')) {
				this.rightLowerDrag(event,this.element.getTop(),this.element.getLeft(),this.element.getHeight(),this.element.getWidth(),this.element.getParentTop(),this.element.getParentLeft(),relativeMouseX,relativeMouseY);						
			}
			if(Element.hasClassName(lastEventElement,'upperMiddleClickBox')) {
				this.upperMiddleDrag(event,this.element.getTop(),this.element.getLeft(),this.element.getHeight(),this.element.getWidth(),this.element.getParentTop(),this.element.getParentLeft(),relativeMouseX,relativeMouseY);						
			}
			if(Element.hasClassName(lastEventElement,'lowerMiddleClickBox')) {
				this.lowerMiddleDrag(event,this.element.getTop(),this.element.getLeft(),this.element.getHeight(),this.element.getWidth(),this.element.getParentTop(),this.element.getParentLeft(),relativeMouseX,relativeMouseY);						
			}
			this.placeClickBox();
			imageBox.reCenterIndicator();			
			this.customOnResize();
		}		
	},
	
	resize: function(width,height) {
		if(width < 30 && height == -1000) {
			return false;
		}
		if(height < 30 && width == -1000) {
			return false;
		}
		if((width < 30 || height < 30) && (width != -1000 && height != -1000)) {
			return false;
		}		
		if(width != -1000)	{ 			
			this.element.style.width = width + "px";			
		} else {			
			this.element.style.width = this.originalWidth + "px";
		}
		if(height != -1000) {			
			this.element.style.height = height + "px";
		} else {
			this.element.style.height = this.originalHeight + "px";
		}		
		return true;		
	},
	
	placeClickBoxOnImageLoad: function() {
		Event.observe('image','load',this.placeClickBox);
	},
	
	placeClickBox: function(event) {
		if(event != null) {
			this.originalHeight = Element.getDimensions(this.element).height;
			this.originalWidth = Element.getDimensions(this.element).width;
		}
		width = Element.getDimensions(this.element).width;
		height = Element.getDimensions(this.element).height;
		
		clickBoxHalfWidth =  Math.floor(Element.getDimensions(this.leftUpperClickBox).width/2);
		
		leftUpper = new Point.initialize(-clickBoxHalfWidth,-clickBoxHalfWidth);
		leftMiddle = new Point.initialize(-clickBoxHalfWidth,height/2-clickBoxHalfWidth);
		leftLower = new Point.initialize(-clickBoxHalfWidth,height-clickBoxHalfWidth);
		rightUpper = new Point.initialize(width-clickBoxHalfWidth,-clickBoxHalfWidth);
		rightMiddle = new Point.initialize(width-clickBoxHalfWidth,height/2-clickBoxHalfWidth);
		rightLower = new Point.initialize(width-clickBoxHalfWidth,height-clickBoxHalfWidth);
		upperMiddle = new Point.initialize(width/2-clickBoxHalfWidth,-clickBoxHalfWidth);
		lowerMiddle = new Point.initialize(width/2-clickBoxHalfWidth,height-clickBoxHalfWidth);
		
		this.leftUpperClickBox.style.left = leftUpper.x + 'px';
		this.leftUpperClickBox.style.top = leftUpper.y + 'px';
		this.leftMiddleClickBox.style.left = leftMiddle.x + 'px';
		this.leftMiddleClickBox.style.top = leftMiddle.y + 'px';
		this.leftLowerClickBox.style.left = leftLower.x + 'px';
		this.leftLowerClickBox.style.top = leftLower.y + 'px';		
		
		this.rightUpperClickBox.style.left = rightUpper.x + 'px';
		this.rightUpperClickBox.style.top = rightUpper.y + 'px';
		this.rightMiddleClickBox.style.left = rightMiddle.x + 'px';
		this.rightMiddleClickBox.style.top = rightMiddle.y + 'px';
		this.rightLowerClickBox.style.left = rightLower.x + 'px';
		this.rightLowerClickBox.style.top = rightLower.y + 'px';
		
		this.upperMiddleClickBox.style.left = upperMiddle.x + 'px';
		this.upperMiddleClickBox.style.top = upperMiddle.y + 'px';
		this.lowerMiddleClickBox.style.left = lowerMiddle.x + 'px';
		this.lowerMiddleClickBox.style.top = lowerMiddle.y + 'px';				
	},
	
	createClickBoxes: function() {
		this.leftUpperClickBox = this.createElement('div',Math.random(),["leftUpperClickBox","clickBox"]);
		this.leftMiddleClickBox = this.createElement('div',Math.random(),["leftMiddleClickBox","clickBox"]);
		this.leftLowerClickBox = this.createElement('div',Math.random(),["leftLowerClickBox","clickBox"]);
		this.rightUpperClickBox = this.createElement('div',Math.random(),["rightUpperClickBox","clickBox"]);
		this.rightMiddleClickBox = this.createElement('div',Math.random(),["rightMiddleClickBox","clickBox"]);
		this.rightLowerClickBox = this.createElement('div',Math.random(),["rightLowerClickBox","clickBox"]);
		this.upperMiddleClickBox = this.createElement('div',Math.random(),["upperMiddleClickBox","clickBox"]);
		this.lowerMiddleClickBox = this.createElement('div',Math.random(),["lowerMiddleClickBox","clickBox"]);		
	},
	
	createElement: function(tag,id,classes) {
		newElement = document.createElement(tag);
		newElement.id = id;
		classes.each(function(item) {
				Element.addClassName(newElement,item);		
			}
		);
		this.addListener(newElement);
		this.element.appendChild(newElement);
		return newElement;		
	},
	
	bindAll: function() {
		this.setListeners = Resizeable.setListeners.bind(this);
		this.placeClickBox = Resizeable.placeClickBox.bind(this);
		this.resizeStart = Resizeable.resizeStart.bind(this);	
		this.onResize = Resizeable.onResize.bind(this);
		this.resize = Resizeable.resize.bind(this);
		this.placeClickBoxOnImageLoad = Resizeable.placeClickBoxOnImageLoad.bind(this);
		this.createClickBoxes = Resizeable.createClickBoxes.bind(this);
		this.createElement = Resizeable.createElement.bind(this);
		this.addListener = Resizeable.addListener.bind(this);
		this.addDraging = Resizeable.addDraging.bind(this);
		this.setVisible = Resizeable.setVisible.bind(this);
		this.removeDraging = Resizeable.removeDraging.bind(this);
		
		this.leftUpperDrag = Resizeable.leftUpperDrag.bind(this);
		this.leftMiddleDrag = Resizeable.leftMiddleDrag.bind(this);
		this.leftLowerDrag = Resizeable.leftLowerDrag.bind(this);		
		this.rightUpperDrag = Resizeable.rightUpperDrag.bind(this);
		this.rightMiddleDrag = Resizeable.rightMiddleDrag.bind(this);
		this.rightLowerDrag = Resizeable.rightLowerDrag.bind(this);
		this.upperMiddleDrag = Resizeable.upperMiddleDrag.bind(this);
		this.lowerMiddleDrag = Resizeable.lowerMiddleDrag.bind(this);		
	},
	
	setListeners: function() {
		Event.observe('mainContainer','mousemove',this.onResize);
		Event.observe('mainContainer','mouseup',this.resizeStop);
	},
	
	addListener: function(element) {
		Event.observe(element,'mousedown',this.resizeStart);		
		Event.observe(element,'mousemove',this.onResize);		
		
	},	
	
	addDraging: function() {
		if(this.draggableImage) this.removeDraging();
		var options =  {
			starteffect: function() {},
			endeffect: function() {},
			change: this.onDrag,
		};		
		this.draggableImage = new Draggable(this.element,options);
	},
	
	removeDraging: function() {
		if(this.draggableImage) {
			this.draggableImage.destroy();
			this.draggableImage = null;
		}
	},
	
	setVisible: function(setVisible) {
		this.isVisible = setVisible;
		if(setVisible) {
			Element.show(
				this.leftUpperClickBox,
				this.leftMiddleClickBox,
				this.leftLowerClickBox,
				this.rightUpperClickBox,
				this.rightMiddleClickBox,
				this.rightLowerClickBox,
				this.upperMiddleClickBox,
				this.lowerMiddleClickBox);
			this.addDraging();
			this.placeClickBox();
		} else {
			Element.hide(
				this.leftUpperClickBox,
				this.leftMiddleClickBox,
				this.leftLowerClickBox,
				this.rightUpperClickBox,
				this.rightMiddleClickBox,
				this.rightLowerClickBox,
				this.upperMiddleClickBox,
				this.lowerMiddleClickBox);					
			this.removeDraging();
		}
	}
}