# CSS #

## Introduction ##

SilverStripe strives to keep out of a template designer's way as much as possible -
this also extends to how you want to write your CSS.

## Adding CSS to your template ##

You are free to add `<style>` and `<link>` tags to your template (typically `themes/yourtheme/templates/Page.ss`).

SilverStripe provides a management layer for javascript and CSS files on top of that with the [Requirements](/reference/requirements) class,
by adding some simple PHP calls to your controller or template. Some files are automatically included,
depending on what functionality you are using (e.g. SilverStripe forms automatically include `framework/css/Form.css`).

In your controller (e.g. `mysite/code/Page.php`):

	:::php
	class Page_Controller {
		public function init() {
			// either specify the css file manually
			Requirements::css("mymodule/css/my.css", "screen,projection");
			// or mention the css filename and SilverStripe will get the file from the current theme and add it to the template
			Requirements::themedCSS('print', 'print'); 
		}
	}

Or in your template (e.g. `themes/yourtheme/templates/Page.ss`):

	:::ss
	<% require css(mymodule/css/my.css) %>

Management through the `Requirements` class has the advantage that modules can include their own CSS files without modifying
your template. On the other hand, you as a template developer can "block" or change certain CSS files that are included from
thirdparty code.

## WYSIWYG editor: typography.css and editor.css

This stylesheet is used to "namespace" rules which just apply in a rendered site and the WYSIWYG-editor of the CMS. This
is needed to get custom styles in the editor without affecting the remaining CMS-styles.

An example of a good location to use `class="typography"` is the container element to your WYSIWYG-editor field. The
`$Content` WYSIWYG editor field already comes with SilverStripe out-of-the-box:

	:::html
	<!--
	   This is a good way, you're only applying class="typography"
	   to where the WYSIWYG editor is, and not the entire layout.
	-->
	`<div id="Main">`
	   `<div id="LeftContent">`
	      `<p>`We have a lot of content to go here.`</p>`
	   `</div>`
	
	   `<div id="Content" class="typography">`
	      $Content
	   `</div>`
	`</div>`


The `typography.css` file should contain only styling for these elements (related to the WYSIWYG editor):

   * **Headers (h1 - h6)**
   * **Text (p, blockquote, pre)**
   * **Lists (ul, li)**
   * **CSS alignment (img.left, .left, .right etc)**
   * **Tables**
   * **Miscellaneous (hr etc)**

The advantages are that it's fully styled, as a default starting point which you can easily tweak. It also doesn't
affect the CMS styling at all (except for the WYSIWYG), which is what we want.

It's also separated from the rest of the layout. If you wanted to change typography only, for where you usually edit the
content you don't need to go wading through other CSS files related to the actual layout.

To get these styles working in the CMS. Eg you use a font you want in the CMS area you need to create an editor.css
file. Then with this file you can define styles for the CMS or just import the styles from typography. Unlike
`typography.css`, `editor.css` is NOT included in the front end site. So this is  `themes/your_theme/css/editor.css`

	:::css
	/* Import the common styles from typography like link colors. */
	@import 'typography.css';
	
	/* We want the backend editor to have a bigger font as well though */
	.typography * { font-size: 200% }

See [typography](/reference/typography) for more information.

## Related ##

 * [javascript](javascript)
 * ["Compass" module](http://silverstripe.org/compass-module/): Allows writing CSS in SASS/LESS syntax, with better code management through mixins, includes and variables
 * [Reference: CMS Architecture](../reference/cms-architecture)
 * [Howto: Extend the CMS Interface](../howto/extend-cms-interface)
