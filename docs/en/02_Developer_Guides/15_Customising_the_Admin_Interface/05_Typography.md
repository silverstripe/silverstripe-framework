title: WYSIWYG Styles
summary: Add custom CSS properties to the rich-text editor.

# WYSIWYG Styles

SilverStripe lets you customise the style of content in the CMS. This is done by setting up a CSS file called
`editor.css` in either your theme or in your `mysite` folder. This is set through yaml config:

```yaml
---
name: MyCSS
---
SilverStripe\Forms\HTMLEditor\TinyMCEConfig:
  editor_css:
    - 'mysite/css/editor.css'
```

Will load the `mysite/css/editor.css` file.

## Custom style dropdown

The custom style dropdown can be enabled via the `importcss` plugin bundled with admin module.
Use the below code in `mysite/_config.php`:

```php
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;

TinyMCEConfig::get('cms')
    ->addButtonsToLine(1, 'styleselect')
    ->setOption('importcss_append', true);
```

Any CSS classes within this file will be automatically added to the `WYSIWYG` editors 'style' dropdown. For instance, to
add the color 'red' as an option within the `WYSIWYG` add the following to the `editor.css`


```css
.red {
    color: red;
}
```

## API Documentation

* [HtmlEditorConfig](api:SilverStripe\Forms\HTMLEditor\HtmlEditorConfig)
