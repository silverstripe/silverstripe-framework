<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.google.com/schemas/sitemap/0.84"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84">
	<% control Items %>
	<url>
		<loc>$AbsoluteLink</loc>
		<lastmod>$LastEdited.Format(c)</lastmod>
		<% if ChangeFreq %><changefreq>$ChangeFreq</changefreq><% end_if %>
		<% if Priority %><priority>$Priority</priority><% end_if %>
	</url>
	<% end_control %>
</urlset>