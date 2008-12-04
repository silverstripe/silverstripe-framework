<html>
    <head>
	<% base_tag %>
        <title>Data Model</title>
    </head>

    <body>
        <h1>Data Model for your project</h1>

		<% control Modules %>
		<h1>Module $Name</h1>
		
		<img src="$Link/graph" />
        
        <% control Models %>
        <h2>$Name <% if ParentModel %> (subclass of $ParentModel)<% end_if %></h2>
        <h4>Fields</h4>
        <ul>
        <% control Fields %>
        <li>$Name - $Type</li>
        <% end_control %>
        </ul>

        <h4>Relations</h4>
        <ul>
        <% control Relations %>
        <li>$Name $RelationType $RelatedClass</li>
        <% end_control %>
        </ul>
        <% end_control %>
		<% end_control %>
    </body>
</html>
    
