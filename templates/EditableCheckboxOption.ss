<li>
	<img class="handle" src="sapphire/images/drag.gif" alt="Drag to rearrange order of options" />
	<input type="radio" name="$Name.Attr[Default]" value="$ID" />
	<input type="text" name="$Name.Attr[Title]" value="$Title.Attr" />
	<% if isReadonly %>
	<a href="#"><img src="cms/images/delete.gif" alt="Remove this option" /></a>
	<% else %>
	<img src="cms/images/locked.gif" alt="These fields cannot be modified" />	
	<% end_if %>
</li>