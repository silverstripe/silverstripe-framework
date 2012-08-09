# How to create a navigation menu

In this how-to, we'll create a simple menu which
you can use as the primary navigation for your website.

Add the following code to your main template,
most likely the "Page" template in your theme,
located in `themes/<mytheme>/templates/Page.ss`.

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

More details on creating a menu are explained as part of ["Tutorial 1: Building a basic site"](/tutorials/1-building-a-basic-site), as well as ["Page type templates" topic](/topics/page-type-templates).