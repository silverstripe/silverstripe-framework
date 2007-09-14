/**
 * @author Mateusz
 */
Point = {
	initialize: function(x,y) {
		this.x = x;
		this.y = y;
	}
}

EventStack = {
	lastEvent: null,
	getLastEvent: function(){		
		return EventStack.lastEvent
	},
	
	addEvent: function(event) {
		EventStack.lastEvent = event;
	},
	
	clearStack: function() {
		this.lastEvent = null;
	}
}

Positioning = {
	addBehaviour: function(element) {
		this.element = element;		
		this.element.getTop = Positioning.getTop.bind(this);
		this.element.getLeft = Positioning.getLeft.bind(this);
		this.element.getWidth = Positioning.getWidth.bind(this);
		this.element.getHeight = Positioning.getHeight.bind(this);
		this.element.getParentLeft = Positioning.getParentLeft.bind(this);
		this.element.getParentTop = Positioning.getParentTop.bind(this);
		this.element.getParentHeight = Positioning.getParentHeight.bind(this);
		this.element.getParentWidth = Positioning.getParentWidth.bind(this);
	},
	
	getTop: function() {
		return Position.positionedOffset(this.element)[1];
	},
	
	getLeft: function() {
		return Position.positionedOffset(this.element)[0];
	},
	
	getWidth: function() {
		return Element.getDimensions(this.element).width;
	},
	
	getHeight: function() {
		return Element.getDimensions(this.element).height;
	}, 
	
	getParentLeft: function() {
		parentLeft = Position.cumulativeOffset(Position.offsetParent(this.element))[0];
		return parentLeft;
	},
	
	getParentTop: function() {
		parentTop = Position.cumulativeOffset(Position.offsetParent(this.element))[1];
		return parentTop;
	},
	
	getParentHeight: function() {
		return Element.getDimensions(Position.offsetParent(this.element)).height;
	},
	
	getParentWidth: function() {
		return Element.getDimensions(Position.offsetParent(this.element)).width
	}
}