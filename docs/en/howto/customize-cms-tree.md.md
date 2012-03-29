# How to customize CMS Tree #

## 1. Introduction ##

	A tree node in CMS could be rendered with lot of extra information but a node title,
	such as a link that wraps around the node title, a node's id which is given as id attribute 
	of the node <li> tag, a extra checkbox beside the tree title, tree icon class or extra <span>
	tags showing the node status, etc. SilverStripe tree node will be typically rendered into
	html code like this:

		:::ss
		...
		<ul>
			...
			<li id="record-15" class="class-Page closed jstree-leaf jstree-unchecked" data-id="15">
				<ins class="jstree-icon">&nbsp;</ins>
				<a class="" title="Page type: Page" href="admin/page/edit/show/15">
					<ins class="jstree-checkbox">&nbsp;</ins>
					<ins class="jstree-icon">&nbsp;</ins>
					<span class="text">
						<span class="jstree-pageicon"></span>
						<span class="item" title="Deleted">New Page</span>
						<span class="badge deletedonlive">Deleted</span>
					</span>
				</a>
			</li>
			...
		</ul>
		...
	
	By applying the proper style sheet, the snippet html above could produce the look of:
![Page Node Screenshot](../_images/tree_node.png "Page Node")
	
## 2. How to customize a tree node with publication status ##

	SiteTree is a `[api:DataObject]` which is versioned by `[api:Versioned]` extension. It
	defaulted to have eithe one of the four publication status flags or have no flag in the most
	common case, the four common flags are in the format of mapping. i.e.




