# Themes

## Introduction

Themes can be used to kick start your SilverStripe projects, and generally make you look good.

## Downloading

Head to the [ Themes ](http://www.silverstripe.org/themes) area of the website to check out the wide range of themes
the community has built. Each theme has a page with links you can use to preview and download it. The theme is provided
as a .tar.gz file.

## Installing

1.  Unpack the contents of the zip file into the `themes` directory in your SilverStripe installation.
2.  Change the site to the theme. You can do this either by:
	- putting the following line in your ./mysite/_config.php: `SSViewer::set_theme("themename");`
	- changing the theme in the Site Configuration panel in the CMS
3. Visit your homepage with ?flush=all appended to the URL. `http://yoursite.com?flush=all`

## Developing your own theme

See [Developing Themes](theme-development) to get an idea of how themes actually work and how you can develop your own. 

## Submitting your theme to SilverStripe

If you want to submit your theme to the SilverStripe directory then check

* You should ensure your templates are well structured, modular and commented so it's easy for other people to 
 customise them.
*  Templates should not contain text inside images and all images provided must be open source and not break any copyright or license laws. 
 This includes any icons your template uses in the frontend or the backend CMS.
*  A theme does not include any PHP files. Only CSS, HTML, Images and Javascript.

Your theme file must be in a .tar.gz format. A useful tool for this is - [7 Zip](http://www.7-zip.org/). Using 7Zip you
must select the your_theme folder and Add to archive, select TAR and create. Then after you have the TAR file right
click it -> Add to Archive (again) -> Then use the archive format GZIP.

## Links

 * [Themes Listing on silverstripe.org](http://silverstripe.org/themes)
 * [Themes Forum on silverstripe.org](http://www.silverstripe.org/themes-2/)
 * [Themes repository on github.com](http://github.com/silverstripe-themes)
