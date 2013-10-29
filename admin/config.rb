# Require any additional compass plugins here.
require 'compass-colors'

project_type = :stand_alone
# Set this to the root of your project when deployed:
http_path = "/"
css_dir = "css"
sass_dir = "scss"
images_dir = "images"
javascripts_dir = "javascript"
output_style = :compact

# To enable relative paths to assets via compass helper functions. Uncomment:
relative_assets = true

# disable comments in the output. We want admin comments
# to be verbose 
line_comments = false

asset_cache_buster :none

Encoding.default_external = "utf-8"
