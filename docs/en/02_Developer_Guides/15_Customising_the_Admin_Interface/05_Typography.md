---
title: WYSIWYG Styles
summary: Add custom CSS properties to the rich-text editor.
icon: text-width
---
# WYSIWYG Styles

SilverStripe lets you customise the style of content in the CMS. This is done by setting up a CSS file called
`editor.css` in either your theme or in your `mysite` folder. This is set through

```php
	HtmlEditorConfig::get('cms')->setOption('content_css', project() . '/css/editor.css');

```

If using this config option in `mysite/_config.php`, you will have to instead call:

```php
	HtmlEditorConfig::get('cms')->setOption('content_css', project() . '/css/editor.css');

```
add the color 'red' as an option within the `WYSIWYG` add the following to the `editor.css`

```css
	.red {
		color: red;
	}

```
After you have defined the `editor.css` make sure you clear your SilverStripe cache for it to take effect.
[/notice]

## API Documentation

* [api:HtmlEditorConfig]
