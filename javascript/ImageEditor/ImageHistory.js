/**
 * @author Mateusz
 */
var ImageHistory = {
	
	initialize: function() {
		this.history = new Array();
		this.historyPointer = -1;
		this.modifiedOriginalImage = false;		
		this.undo = ImageHistory.undo.bind(this);
		this.redo = ImageHistory.redo.bind(this);
		this.add = ImageHistory.add.bind(this);
		this.addLinsteners = ImageHistory.addLinsteners.bind(this);
		this.addLinsteners();	
		this.operationMade = ImageHistory.operationMade.bind(this);		
		this.onFakeImageLoad = ImageHistory.onFakeImageLoad.bind(this);
		this.enable = ImageHistory.enable.bind(this);
		this.disable = ImageHistory.disable.bind(this);
	},
		
	undo: function() {
		if(this.historyPointer >= 1) {
			image = $('image');
			fakeImage = $('fakeImg');
			operation = this.history[this.historyPointer].operation;
			if(operation == 'rotate' || operation == 'crop') {
				if(this.operationMade(this.historyPointer-1,'rotate') || this.operationMade(this.historyPointer-1,'crop')) 
					this.modifiedOriginalImage = true; else this.modifiedOriginalImage = false;
			}
			image.src = this.history[this.historyPointer-1].fileUrl;
			fakeImage.src = this.history[this.historyPointer-1].fileUrl; 
			Event.observe('fakeImg','load',this.onFakeImageLoad);
			this.historyPointer--;
		} else {
			alert("No more undo");
		}
	},
	
	redo: function() {
		if(this.historyPointer < this.history.length-1) {
			operation = this.history[this.historyPointer+1].operation;
			if(operation == 'rotate' || operation == 'crop') this.modifiedOriginalImage = true;
			$('image').src = this.history[this.historyPointer+1].fileUrl;
			$('fakeImg').src = $('image').src; 
			Event.observe('fakeImg','load',this.onFakeImageLoad);	
			this.historyPointer++;
		} else {
			alert("No more redo");
		}
	},
	
	add: function(operation,url) {
		this.historyPointer++;
		this.history[this.historyPointer] = {'operation': operation,'fileUrl' : url};
		if(operation == 'rotate' || operation == 'crop') this.modifiedOriginalImage = true;
	},
	
	addLinsteners: function() {
		this.undoListener = Event.observe('undoButton','click',this.undo);	
		this.redoListener = Event.observe('redoButton','click',this.redo);
	},
	
	operationMade: function(historyPointer,operation) {
		for(i=historyPointer;i>=0;i--) {
			if(this.history[i].operation == operation) {
				return true;
			}
		}
		return false;
	},
	
	onFakeImageLoad: function() {
		$('imageContainer').style.width = fakeImage.width + 'px';
		$('imageContainer').style.height = fakeImage.height + 'px';
		resize.imageContainerResize.originalWidth = fakeImage.width;			
		resize.imageContainerResize.originalHeight = fakeImage.height;				
		resize.imageContainerResize.placeClickBox();
	},
	
	enable: function() {
		this.addLinsteners();
	},
	
	disable: function() {
		Event.stopObserving($('undoButton'),'click', this.undo);			
		Event.stopObserving($('redoButton'),'click', this.redo);
	},
};