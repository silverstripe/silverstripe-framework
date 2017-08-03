title: Form Transformations
summary: Provide read-only and disabled views of your Form data.

# Read-only and Disabled Forms

[Form](api:SilverStripe\Forms\Form) and [FormField](api:SilverStripe\Forms\FormField) instances can be turned into a read-only version for things like confirmation pages or 
when certain fields cannot be edited due to permissions. Creating the form is done the same way and markup is similar, 
`readonly` mode converts the `input`, `select` and `textarea` tags to static HTML elements like `span`.

To make an entire [Form](api:SilverStripe\Forms\Form) read-only.


```php

	$form = new Form(..);
	$form->makeReadonly();
```

To make all the fields within a [FieldList](api:SilverStripe\Forms\FieldList) read-only (i.e to make fields read-only but not buttons).


```php

	$fields = new FieldList(..);
	$fields = $fields->makeReadonly();
```

To make a [FormField](api:SilverStripe\Forms\FormField) read-only you need to know the name of the form field or call it direct on the object


```php

	$field = new TextField(..);
	$field = $field->performReadonlyTransformation();

	$fields = new FieldList(
		$field
	);

	// Or,
	$field = new TextField(..);
	$field->setReadonly(true);

	$fields = new FieldList(
		$field
	);
```

## Disabled FormFields

Disabling [FormField](api:SilverStripe\Forms\FormField) instances, sets the `disabled` property on the class. This will use the same HTML markup as 
a normal form, but set the `disabled` attribute on the `input` tag.

```php
	$field = new TextField(..);
	$field->setDisabled(true);

	echo $field->forTemplate();

	// returns '<input type="text" class="text" .. disabled="disabled" />'

```