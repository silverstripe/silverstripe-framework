# Introduction

Behaviour-driven JavaScript library, customized for usage in SilverStripe CMS.

Caution: Seriously outdated, consider using [http://github.com/hafriedlander/jquery.entwine](jQuery.entwine) and [http://api.jquery.com/live/](jQuery.live) instead.

# Author

 * Sam Minnee (sam at silverstripe dot com)

# Requirements

 * Custom Prototype 1.4 RC3 (see `framework/thirdparty/prototype/prototype.js`)

# Tutorial

In SilverStripe, I've tried to make Javascript development a lot more well-structured.  It's very easy to write spaghetti, but this ultimately prevents maintainability.  This page is a guide to better Javascript. (hopefully).

## Required includes

To use everything mentioned here, first include jsparty/behaviour.js and jsparty/prototype.js.  This is a lot of overhead, I know, and producing a leaner core file is definitely on the to do list!

## Class creation and inheritance

Classes in Javascript are normally too voodoo to be consistently applied.  I've created some libraries to help this.
  * Class.create() will return a new class.
  * Class.extend(parentClassName) will return a class that extends form the parent class.
  * Class.extend(parentClass1).extend(parentClass2) will perform multiple inheritance.

Once we've created our classes, then what?  You should define the prototype, as indicated below.  The initialize method acts as the constructor.  The parent constructor  is automatically called **before** the child constructor.

If you would like to access a parent class' method within a child class, you can refer to this.ParentClassName.methodName().  This is useful if you've overloaded the method but want to base its functionality on the parent method.

### Automatic instantiation

This is where things get a even more fruity.  We have the power to automatically configure DOM objects - the tags within your HTML - to automatically act as your.  This lets you build "intelligence" into the elements on your page - not only overriding event handlers, but creating methods that can be called by other objects throughout the application.  Although my experience so far is a little limited, it seems as though this is a much cleaner way of coding our javascript.

The thing to remember - the thing which differs from many other Javascript coding styles - is that the DOM object and the "control" object **are one and the same**.

Each class will have an applyTo() method.  This method can be passed either a **CSS selector** or an element.  If you call applyTo() before the page is loaded, the class will be applied in the window.onload() event.

#### Example

here's how you roll it all together in an example.

	Resizable = Class.create();
		onmousedown : function() {
			...
		},
		onmouseup : function() {
			...
		}
	}

	SidePanel = Class.create();
	SidePanel.prototype = {
		initialize : function() {
			...
		},
		onshow : function() {
			...
		},
		onresize : function() {
			...
		},
		ajaxGetPanel : function(onComplete) {
			...
		},
		afterPanelLoaded : function() {
		}
	}

	VersionList = Class.extend('SidePanel').extend('Resizable');
	VersionList.prototype = {
		afterPanelLoaded : function() {
			...
		}
	}

	VersionItem.applyTo('#versions//holder tbody tr');
	VersionAction.applyTo('#versions//holder p.pane//actions');
	VersionList.applyTo('#versions//holder');

CAUTION: If you want to to have true instance-variables, specify them in initialize() instead of making a new object-property.


	Resizable = Class.create();
		staticVar: "the same in each instance",

	   initialize: function() {
	     this.instanceVar = "current dom-id: " + this.id;
	   }
	}
	Resizable.applyTo("div.multipleElements");
