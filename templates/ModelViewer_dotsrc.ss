digraph g {
	orientation=portrait;
	overlap=false;
	splines=true;

	edge[fontsize=8,len=1.5]; 
	node[fontsize=10,shape=box];

    <% control Modules %>
    <% control Models %>
        $Name [shape=record,label="{$Name|<% control Fields %>$Name\\n<% end_control %>}"];
	<% if ParentModel %>
		$Name -> $ParentModel [style=dotted];
	<% end_if %>
       <% control Relations %>
            $Model.Name -> $RelatedClass [label="$Name\\n$RelationType"];
        <% end_control %>
    <% end_control %>
    <% end_control %>
}
