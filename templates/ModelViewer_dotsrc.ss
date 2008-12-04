digraph g {
    edge[len=3]; 

    <% control Modules %>
    <% control Models %>
        $Name [shape=record,label="{$Name|Field1\\nField2}"];
        <% control Relations %>
            $Model.Name -> $RelatedClass [label="$Name\\n$RelationType"];
        <% end_control %>
    <% end_control %>
    <% end_control %>
}