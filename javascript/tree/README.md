# JavaScript Tree Control

## Maintainers

 * Sam Minnee (sam at silverstripe dot com)

## Features

 * Build trees using semantic HTML and unobtrusive JavaScript.
 * Style the tree to suit your application you with CSS.
 * Demo: http://www.silverstripe.org/assets/tree/demo.html

## Usage

The first thing to do is include the appropriate JavaScript and CSS files:

	<code html>
	<link rel="stylesheet" type="text/css" media="all" href="tree.css" />
	<script type="text/javascript" src="tree.js"></script> 	 	 	
	</code>

Then, create the HTML for you tree. This is basically a nested set of bullet pointed links. The "tree" class at the top is what the script will look for. Note that you can make a tree node closed to begin with by adding `class="closed"`.

Here's the HTML code that I inserted to create the demo tree above.

	<code html>
	<ul class="tree">
	  <li><a href="#">item 1</a>
	    <ul>
	      <li><a href="#">item 1.1</a></li>
	      <li class="closed"><a href="#">item 1.2</a>
	        <ul>
	          <li><a href="#">item 1.2.1</a></li>
	          <li><a href="#">item 1.2.2</a></li>
	          <li><a href="#">item 1.2.3</a></li>
	        </ul>
	      </li>
	      <li><a href="#">item 1.3</a></li>
	    </ul>
	  </li>
	  <li><a href="#">item 2</a>
	    <ul>
	      <li><a href="#">item 2.1</a></li>
	      <li><a href="#">item 2.2</a></li>
	      <li><a href="#">item 2.3</a></li>
	    </ul>	
	  </li>
	</ul> 	 	 	
	</code>

Your tree is now complete!

## How it works

Obviously, this isn't a complete detail of everything that's going on, but it gives you an insight into the overall process.

### Starting the script

In simple situations, creating an auto-loading script is a simple matter of setting window.onload to a function. But what if there's more than one script? To this end, we created an appendLoader() function that will execute multiple loader functions, including a previously defined loader function

### Finding the tree content

Rather than write a piece of script to define where your tree is, we've tried to make the script as automatic as possible - it finds all ULs with a class name containing "tree".

### Augmenting the HTML

Unfortunately, an LI containing an A isn't sufficient for doing all of the necessary tree styling. Rather than force people to put non-semantic HTML into their file, the script generates extra `<span>` tags.

So, the following HTML:

	<code html>
	<li>
	  <a href="#">My item</a>
	</li> 
	</code>

Is turned into the more ungainly, and yet more easily styled:

	<code html>
	<li>
	  <span class="a"><span class="b"><span class="c">
	        <a href="#">My item</a>
	  </span></span></span>
	</li> 
	</code>

Additionally, some helper classes are applied to the `<li>` and `<span class="a">` elements:

 * `"last"` is applied to the last node of any subtree.
 * `"children"` is applied to any node that has children. 

### Styling it up

Why the heck do we need 5 styling elements? Basically, because there are 5 background-images to apply:

  * li: A repeating vertical line is shown. Nested <li> tags give us the multiple vertical lines that we need.
  * span.a: We overlay the vertical line with 'L' and 'T' elements as needed.
  * span.b: We overlay '+' or '-' signs on nodes with children.
  * span.c: This is needed to fix up the vertical line.
  * a: Finally, we apply the page icon.

### Opening / closing nodes

Having come this far, the "dynamic" aspect of the tree control is very trivial. We set a "closed" class on the `<li>` and `<span class="a">` elements, and our CSS takes care of hiding the children, changing the - to a + and changing the folder icon.