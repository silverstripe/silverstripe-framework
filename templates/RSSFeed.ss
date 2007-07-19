<?xml version="1.0"?>
<rss version="2.0">
	<channel>
		<title>$Title</title>
		<link>$Link</link>
		<description>$Description</description>

		<% control Entries %>
		<item>
			<title>$Title</title>
			<link>$AbsoluteLink</link>
			<% if Description %><description>$Description.AbsoluteLinks.EscapeXML</description><% end_if %>
			<% if Date %><pubDate>$Date.Rfc822</pubDate>
			<% else %><pubDate>$Created.Rfc822</pubDate><% end_if %>
			<% if Author %><author>$Author.XML</author><% end_if %>
			<guid>$ID</guid>
		</item>		
		<% end_control %>

	</channel>
</rss>