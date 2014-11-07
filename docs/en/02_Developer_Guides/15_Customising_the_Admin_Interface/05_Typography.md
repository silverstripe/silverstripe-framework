title: WYSIWYG Styles
summary: Add custom CSS properties to the rich-text editor.

# WYSIWYG Styles

SilverStripe lets you customize the style of content in the CMS. This is done by setting up a CSS file called
`editor.css` in either your theme or in your `mysite` folder. This is set through

	:::php
	HtmlEditorConfig::get('cms')->setOption('ContentCSS', project() . '/css/editor.css');

Will load the `mysite/css/editor.css` file.

Any CSS classes within this file will be automatically added to the `WYSIWYG` editors 'style' dropdown. For instance, to
add the color 'red' as an option within the `WYSIWYG` add the following to the `editor.css`

	:::css
	.red {
		color: red;
	}

<div class="notice" markdown="1">
After you have defined the `editor.css` make sure you clear your SilverStripe cache for it to take effect.
</div>

## API Documentation

* [api:HtmlEditorConfig]
