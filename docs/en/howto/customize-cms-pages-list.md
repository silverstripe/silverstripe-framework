# Howto: Customize the Pages List in the CMS

The pages "list" view in the CMS is a powerful alternative to visualizing
your site's content, and can be better suited than a tree for large flat
hierarchies. A good example would be a collection of news articles,
all contained under a "holder" page type, a quite common pattern in SilverStripe.

The "list" view allows you to paginate through a large number of records,
as well as sort and filter them in a way that would be hard to achieve in a tree structure.
But sometimes the default behaviour isn't powerful enough, and you want a more
specific list view for certain page types, for example to sort the list by
a different criteria, or add more columns to filter on. The resulting
form is mainly based around a `[GridField](/reference/grid-field)` instance,
which in turn includes all children in a `[DataList](/topics/datamodel)`.
You can use these two classes as a starting point for your customizations.

Here's a brief example on how to add sorting and a new column for a
hypothetical `NewsPageHolder` type, which contains `NewsPage` children.

	:::php
	// mysite/code/NewsPageHolder.php
	class NewsPageHolder extends Page {
		private static $allowed_children = array('NewsPage');
	}

	// mysite/code/NewsPage.php
	class NewsPage extends Page {
		private static $has_one = array(
			'Author' => 'Member',
		);
	}

We'll now add an `Extension` subclass to `LeftAndMain`, which is the main CMS controller.
This allows us to intercept the list building logic, and alter the `GridField`
before its rendered. In this case, we limit our logic to the desired page type,
although it's just as easy to implement changes which apply to all page types,
or across page types with common characteristics.

	:::php
	// mysite/code/NewsPageHolderCMSMainExtension.php
	class NewsPageHolderCMSMainExtension extends Extension {
		function updateListView($listView) {
			$parentId = $listView->getController()->getRequest()->requestVar('ParentID');
			$parent = ($parentId) ? Page::get()->byId($parentId) : new Page();

			// Only apply logic for this page type
			if($parent && $parent instanceof NewsPageHolder) {
				$gridField = $listView->Fields()->dataFieldByName('Page');
				if($gridField) {
					// Sort by created
					$list = $gridField->getList();
					$gridField->setList($list->sort('Created', 'DESC'));
					// Add author to columns
					$cols = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');
					if($cols) {
						$fields = $cols->getDisplayFields($gridField);
						$fields['Author.Title'] = 'Author';
						$cols->setDisplayFields($fields);
					}
				}
			}
		}
	}

Now you just need to enable the extension in your [configuration file](/topics/configuration).

	// mysite/_config/config.yml
	LeftAndMain:
	  extensions:
	    - NewsPageHolderCMSMainExtension

You're all set! Don't forget to flush the caches by appending `?flush=all` to the URL.