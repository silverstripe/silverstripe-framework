#How to create a navigation menu

To create a navigation menu, we will create a new template file: Navigation.ss. Put this file inside the templates/Includes folder in your theme.

    :::ss
    <ul> 
    	<% loop Menu(1) %>	  
    		<li>
          <a href="$Link" title="Go to the $Title page" class="$LinkingMode">
           <span>$MenuTitle</span>
          </a>
        </li> 
     	<% end_loop %> 
    </ul>

To include this file in your main template, use the 'include' control code. The include control code will insert a template from the Includes folder into your template. The code for including our navigation menu looks like this:

    :::ss
    <% include Navigation %>

Add this to the templates/Page.ss file where you want the menu to render. The template code in Menu1.ss is rendered as an unordered list in HTML; let's break down this file to see how this works.

The first and last lines of the file are HTML tags to open and close an unordered list. 

    :::ss
    <ul> 
    	<% loop Menu(1) %>	  
    		<li>
                <a href="$Link" title="Go to the $Title page" class="$LinkingMode">
                    <span>$MenuTitle</span>
                </a>
            </li> 
        <% end_loop %> 
    </ul>

Line 2 and 4 use a template code called a loop. A loop iterates over a DataObjectSet; for each DataObject inside the set, everything between the <% loop %> and <% end_loop %> tags are repeated. Here we iterate over the Menu(1) DataObjectSet and this returns the set of all pages at the top level. (For a list of other controls you can use in your templates, see the [templates page](../reference/templates) .)

    :::ss
    <ul> 
     	<% loop Menu(1) %>	  
      		<li>
                <a href="$Link" title="Go to the $Title page" class="$LinkingMode">
                    <span>$MenuTitle</span>
                </a>
            </li> 
       	<% end_loop %> 
    </ul>

Line 3 is where we insert the list item for each menu item. It is sandwiched by the list item opening and closing tags, <li> and </li>. Inside we have a link, using some template codes to fill in the information for each page:

* $Link – the link to the page  
* $Title – the full title of the page (this is a field in the CMS)  
* $MenuTitle – the menu title of the page (this is a field in the CMS)  
* $LinkingMode – which returns one of three things used as a CSS class to style each scenario differently.  
    * current – this is the page that is currently being rendered
    * section – this page is a child of the page currently being rendered
    * link – this page is neither current nor section  


