title: Themes
summary: What makes up a SilverStripe Theme. How to install one or write your own theme. 

# Themes

Themes can be used to kick start your SilverStripe projects, can be stored outside of your application code and your
application can provide multiple unique themes (i.e a mobile theme).

## Downloading

Head to the [ themes section of the addons site ](http://addons.silverstripe.org/add-ons?search=&type=theme) to check out the range of themes the 
community has built. Each theme has a page with links you can use to preview and download it. Themes are normally published and downloaded using Composer,
but may be available as archive files as well.

## Installation

### Via Composer

If a theme has Composer support you can require it directly through `composer`.

```bash
composer require author/theme_name [version]
```

*Note:* `[version]` should be replaced with a version constraint if you know it, otherwise leave it blank to pull the latest version compatible with your project.

<div class="alert" markdown="1">
As you've added new files to your SilverStripe installation, make sure you clear the SilverStripe cache by appending
`?flush=1` to your website URL (e.g http://yoursite.com/?flush=1).
</div>

After installing the files through either method, update the current theme in SilverStripe. This can be done by 
either altering the `SSViewer.themes` setting in a [config.yml](../configuration) or by changing the current theme in 
the Site Configuration panel (http://yoursite.com/admin/settings)

**mysite/_config/app.yml**

```yaml
SilverStripe\View\SSViewer:
  themes:
    - theme_name
    - '$default'
```

### Manually

Unpack the contents of the zip file you download into the `themes` directory in your SilverStripe installation. The
theme should be accessible at `themes/theme_name`.

## Developing your own theme

A `theme` within SilverStripe is simply a collection of templates and other front end assets such as javascript and CSS located within the `themes` directory. 

![themes:basicfiles.gif](../../_images/basicfiles.gif)

SilverStripe 4 has support for cascading themes, which will allow users to define multiple themes for a project. This means you can have a template defined in any theme, and have it continue to look back through the list of themes until a match it found.

To define extra themes simply add extra entries to the `SilverStripe\View\SSViewer.themes` configuration array. You will probably always want to ensure that you include `'$default'` in your list of themes to ensure that the base templates are used when required.

## Submitting your theme to addons

If you want to submit your theme to the SilverStripe addons directory then check:

* You should ensure your templates are well structured, modular and commented so it's easy for other people to customise 
* Templates should not contain text inside images and all images provided must be open source and not break any 
copyright or license laws. This includes any icons your template uses.
* A theme does not include any PHP files. Only CSS, HTML, images and javascript.
* Your theme contains a `composer.json` file specifying the theme name, author and license, and that it has `"type": "silverstripe-theme"`.

Once you've created your module and set up your Composer configuration, create a new repository and push your theme to a Git host such as [GitHub.com](https://github.com). 

The final step is to [submit your theme to Packagist](https://packagist.org/about#how-to-submit-packages) (the central Composer package repository). Once your theme is listed in Packagist, and has `"type": "silverstripe-theme"` in its configuration, it will automatically be pulled into our addons listing site.

## Links

 * [Themes Listing on silverstripe.org](http://addons.silverstripe.org/add-ons?search=&type=theme)
 * [Themes Forum on silverstripe.org](https://www.silverstripe.org/community/forums/themes-2/)
 * [Themes repositories on github.com](http://github.com/silverstripe-themes)
