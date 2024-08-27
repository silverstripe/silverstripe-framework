$Header.RAW
$Info.RAW

<% if $Title %>
    <div class="info">
        <h1>$Title</h1>
    </div>
<% end_if %>

<div class="options">
    <% if $Form %>
        <%-- confirmation handler --%>
        $Form
    <% else %>
        <ul>
            <% loop $ArrayLinks %>
                <li class="$EvenOdd">
                    <a href="$Link"><b>/$Path:</b> $Description</a>
                    <% if $Help %>
                        <details class="more-details">
                            <summary>Display additional information</summary>
                            $Help
                        </details>
                    <% end_if %>
                    <% if $Parameters %>
                        <div>Parameters:
                            <% include SilverStripe/Dev/Parameters %>
                        </div>
                    <% end_if %>
                </li>
            <% end_loop %>
        </ul>
    <% end_if %>
</div>

$Footer.RAW

