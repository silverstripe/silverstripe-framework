<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<% control Items %>
	<url>
		<loc>$AbsoluteLink</loc>
		<lastmod>$LastEdited.Format(c)</lastmod>
		<% if ChangeFreq %><changefreq>$ChangeFreq</changefreq><% end_if %>
		<% if Priority %><priority>$Priority</priority><% end_if %>
	</url>
	<% end_control %>
</urlset>