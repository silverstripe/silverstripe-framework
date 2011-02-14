# Live Query

Live Query utilizes the power of jQuery selectors by binding events or firing callbacks for matched elements auto-magically, even after the page has been loaded and the DOM updated.

For example you could use the following code to bind a click event to all A tags, even any A tags you might add via AJAX.

    $('a') 
        .livequery('click', function(event) { 
            alert('clicked'); 
            return false; 
        }); 

Once you add new A tags to your document, Live Query will bind the click event and there is nothing else that needs to be called or done.

When an element no longer matches a selector the events Live Query bound to it are unbound. The Live Query can be expired which will no longer bind anymore events and unbind all the events it previously bound.

Live Query can even be used with the more powerful jQuery selectors. The following Live Query will match and bind a click event to all A tags that have a rel attribute with the word "friend" in it. If one of the A tags is modified by removing the word "friend" from the rel attribute, the click event will be unbound since it is no longer matched by the Live Query.

    $('a[rel*=friend]') 
        .livequery('click', function(event) { 
            doSomething(); 
        });

Live Query also has the ability to fire a function (callback) when it matches a new element and another function (callback) for when an element is no longer matched. This provides ultimate flexibility and untold use-cases. For example the following code uses a function based Live Query to implement the jQuery hover helper method and remove it when the element is no longer matched.

    $('li') 
        .livequery(function(){ 
        // use the helper function hover to bind a mouseover and mouseout event 
            $(this) 
                .hover(function() { 
                    $(this).addClass('hover'); 
                }, function() { 
                    $(this).removeClass('hover'); 
                }); 
        }, function() { 
            // unbind the mouseover and mouseout events 
            $(this) 
                .unbind('mouseover') 
                .unbind('mouseout'); 
        }); 

## API

### `livequery` Signatures

The `livequery` method has 3 different signatures or ways to call it.

The first, and most typical usage, is to pass an event type and an event handler:

    // eventType: such as click or submit
    // eventHandler: the function to execute when the event happens
    $(selector).livequery( eventType, eventHandler );

The second and third signature is to pass one or two functions to `livequery`. Doing this, `livequery` will call the first passed function when an element is newly matched and will call the second passed function when an element is removed or no longer matched. The second function is optional. The `this` or context of the first function will be the newly matched element. For the second function it will be the element that is no longer matched.

    // matchedFn: the function to execute when a new element is matched
    $(selector).livequery( matchedFn );

    // matchedFn: the function to execute when a new element is matched
    // unmatchedFn: the function to execute when an element is no longer matched
    $(selector).livequery( matchedFn, unmatchFn );

### `expire` Signatures

The `expire` method has 5 different signatures or ways to call it.

The first way will stop/expire all live queries associated with the selector.

    $(selector).expire();

The second way will stop/expire all live queries associated with the selector and event type.

    // eventType: such as click or submit
    $(selctor).expire( eventType );

The third way will stop/expire all live queries associated with the selector, event type, and event handler reference.

    // eventType: such as click or submit
    // eventHandler: the function to execute when the event happens
    $(selector).expire( eventType, eventHandler );

The fourth way will stop/expire all live queries associated with the selector and matchedFn.

    // matchedFn: the function to execute when a new element is matched
    $(selector).expire( matchedFn );

The fifth way will stop/expire all live queries associated with the selector, matchedFn, and unmatchedFn.

    // matchedFn: the function to execute when a new element is matched
    // unmatchedFn: the function to execute when an element is no longer matched
    $(selector).expire( matchedFn, unmatchFn );

## For Plugin Developers

If your plugin modifies the DOM without using the built-in DOM Modification methods (append, addClass, etc), you can register your plugin with Live Query like this.

    if (jQuery.livequery) 
        jQuery.livequery.registerPlugin("pluginMethodName"); 
        
You can register several plugin methods at once by just passing them as additional arguments to the registerPlugin method.

    if (jQuery.livequery) 
        jQuery.livequery.registerPlugin("method1", "method2", "method3"); 

## License

The Live Query plugin is dual licensed *(just like jQuery)* under the MIT (MIT\_LICENSE.txt) and GPL Version 2 (GPL\_LICENSE.txt) licenses.

Copyright (c) 2010 [Brandon Aaron](http://brandonaaron.net)