# Typography

## Introduction
SilverStripe lets you customise the style of content in the CMS.  

## Usage
This is done by setting up a CSS file called (projectname)/css/typography.css.

You also need to create a file called (projectname)/css/editor.css with the following content:

	:::css
	/**	
	 * This support file is used to style the WYSIWYG editor in the CMS
	 */
	
	@import "typography.css";
	
	body.mceContentBody {
		min-height: 200px;
		font-size: 62.5%;
	}
	body.mceContentBody a.broken {
		background-color: #FF7B71;
		border: 1px red solid;
	}



In typography.css you can define styles of any of the tags that will get created by the editor:

* P, BLOCKQUOTE
* H1-6
* UL, OL, LI
* TABLE
* STRONG, EM, U
* A

It's important to realise that this CSS file is included directly into the CMS system, and if you aren't careful, you
can alter the styling of other parts of the interface.  While this is novel, it can be dangerous and is probably not
what you're after.

The way around this is to limit all your styling selectors to elements inside something with `class="typography"`.  The
other half of this is to put `class="typography"` onto any area in your template where you would like the styling to be
applied.

**WRONG**

	:::css
	CSS:
	h1, h2 {
	  color: #F77;
	}
	
	Template:
	<div>
	$Content
	</div>


**RIGHT**

	:::css
	CSS:
	.typography h1, .typography h2 {
	  color: #F77;
	}
	
	Template:
	<div class="typography">
	$Content
	</div>


If you would to include different styles for different sections of your site, you can use class names the same as the
name of the data fields. This example sets up different paragraph styles for 2 HTML editor fields called Content and
OtherContent:

	:::css
	.Content.typography p {
	  font-size: 12px;
	}
	
	.OtherContent.typography p {
	  font-size: 10px;
	}


### Removing the typography class

Sometimes, it's not enough to add a class, you also want to remove the typography class.  You can use the
`[api:HTMLEditorField]` method setCSSClass.

This example sets another CSS class typographybis:

	:::php
	public function getCMSFields() {
	        ...
	        $htmleditor = new HTMLEditorField("ContentBis", "Content Bis");
		$htmleditor->setCSSClass('typographybis');
		$fields->addFieldToTab("Root.Content", $htmleditor);
		...
	        return $fields;
	}


**Note:** This functionality will be available in the version 2.0.2 of the CMS.