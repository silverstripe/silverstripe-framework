title: Disable Anchor Rewriting

# Disable Anchor Rewriting

Anchor links are links with a "#" in them. A frequent use-case is to use anchor links to point to different sections of 
the current page.  For example, we might have this in our template:

	:::ss
	<ul>
		<li><a href="#section1">Section 1</a></li>
		<li><a href="#section2">Section 2</a></li>
	</ul>


Things get tricky because of we have set our `<base>` tag to point to the root of the site.  So, when you click the 
first link you will be sent to http://yoursite.com/#section1 instead of http://yoursite.com/my-long-page/#section1

In order to prevent this situation, the SSViewer template renderer will automatically rewrite any anchor link that
doesn't specify a URL before the anchor, prefixing the URL of the current page.  For our example above, the following
would be created in the final HTML

	:::ss
	<ul>
		<li><a href="my-long-page/#section1">Section 1</a></li>
		<li><a href="my-long-page/#section2">Section 2</a></li>
	</ul>


There are cases where this can be unhelpful. HTML anchors created from Ajax responses are the most common. In these
situations, you can disable anchor link rewriting by setting the `SSViewer.rewrite_hash_links` configuration value to 
`false`.

**mysite/_config/app.yml**
SSViewer:
  rewrite_hash_links: false

Or, a better way is to call this just for the rendering phase of this particular file:

	:::php
	public function RenderCustomTemplate() {
		Config::inst()->update('SSViewer', 'rewrite_hash_links', false);
		$html = $this->renderWith('MyCustomTemplate');
		Config::inst()->update('SSViewer', 'rewrite_hash_links', true);

		return $html;
	}