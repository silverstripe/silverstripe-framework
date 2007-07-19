// Waits for the condition to be "true"
Selenium.prototype.doWaitForCondition = function(script, timeout) {
    if (isNaN(timeout)) {
    	throw new SeleniumError("Timeout is not a number: " + timeout);
    }
    
    testLoop.waitForCondition = function () {
    		try {
        	return eval(script);
        } catch(er) {
        	alert("Error evaluation condition:" + er.message);
        	throw new SeleniumError("Error evaluation condition:" + er.message);
        }
    };
    
    testLoop.waitForConditionStart = new Date().getTime();
    testLoop.waitForConditionTimeout = timeout;
    testLoop.window = this.browserbot.getCurrentWindow();
    testLoop.firstTime = true;
    
    testLoop.pollUntilConditionIsTrue = function () {
        try {
	        if (!this.firstTime && this.waitForCondition()) {
	            this.waitForCondition = null;
	            this.waitForConditionStart = null;
	            this.waitForConditionTimeout = null;
	            this.continueCommandExecutionWithDelay();
	        } else {
	        	if (this.waitForConditionTimeout != null) {
		        	var now = new Date();
		        	if ((now - this.waitForConditionStart) > this.waitForConditionTimeout) {
		        		throw new SeleniumError("Timed out after " + this.waitForConditionTimeout + "ms");
		        	}
		        }
	          
	          window.setTimeout("testLoop.pollUntilConditionIsTrue()", testLoop.firstTime ? 1000 : 10);
	          testLoop.firstTime = false;
	        }
	    } catch (e) {
	    	var lastResult = new CommandResult();
    		lastResult.failed = true;
    		lastResult.failureMessage = e.message;
	    	this.commandComplete(lastResult);
	    	this.testComplete();
	    }
    };
};

Selenium.prototype.doAjaxWait = function(script, timeout) {
	return this.doWaitForCondition('this.window._AJAX_LOADING == false', 10000);
}


Selenium.prototype.doSleep = function(time) {
  if (isNaN(time)) {
  	throw new SeleniumError("Timeout is not a number: " + time);
  }
  
  window.setTimeout("testLoop.testComplete()", time);
};


Selenium.prototype.doAssertModalPresent = function() {
	if(this.browserbot.recordedModals.length == 0)
		Assert.fail("No modal was present");
 	this.browserbot.recordedModals.shift();
};
Selenium.prototype.doAssertNotModalPresent = function() {
	return (this.browserbot.recordedModals.length > 0)
		Assert.fail("A modal was present when it shouldn't be.");
};


Selenium.prototype.doAnswerOnNextModal = function(answer) {
    this.browserbot.nextModalResult = answer;
};

BrowserBot.prototype.modifyWindowToRecordPopUpDialogs = function(windowToModify, browserBot) {
	
    windowToModify.alert = function(alert) {
        browserBot.recordedAlerts.push(alert);
    };

    windowToModify.confirm = function(message) {
        browserBot.recordedConfirmations.push(message);
        var result = browserBot.nextConfirmResult;
        browserBot.nextConfirmResult = true;
        return result;
    };

    windowToModify.prompt = function(message) {
        browserBot.recordedPrompts.push(message);
        var result = !browserBot.nextConfirmResult ? null : browserBot.nextPromptResult;
        browserBot.nextConfirmResult = true;
        browserBot.nextPromptResult = '';
        return result;
    };
    
    if(!browserBot.recordedModals) browserBot.recordedModals = Array();
    windowToModify.modalDialog = function(url, handlers) {
    	browserBot.recordedModals.push(url);
    	if(browserBot.nextModalResult) handlers[browserBot.nextModalResult]();
    	browserBot.nextModalResult = null;
    };

    // Keep a reference to all popup windows by name
    // note that in IE the "windowName" argument must be a valid javascript identifier, it seems.
    var originalOpen = windowToModify.open;
    windowToModify.open = function(url, windowName, windowFeatures, replaceFlag) {
        var openedWindow = originalOpen(url, windowName, windowFeatures, replaceFlag);
        selenium.browserbot.openedWindows[windowName] = openedWindow;
        return openedWindow;
    };
};
