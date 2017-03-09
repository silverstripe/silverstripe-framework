Bitmap sprites will be phased out in the CMS UI in favour of web font icons
as well as SVG graphics. During this transition, the sprites are still references,
but there's no way to add new sprites to the system.

*Original Instructions (no longer valid)*

We use sprites to handle various icons and images throughout the CMS. These are automatically generated
by running `yarn run build` and can be found at `/admin/client/src/sprites/dist`. To add new
images to the sprites, simply add the image to the folder matching the image's size in
`/admin/client/sprites` then run `yarn run build` to generate the sprite containing your image.
Along with the new sprite containing your image, there will also be a new variable in
`/admin/client/styles/legacy/_sprites.scss` which you can use in your .scss file by first extending the class matching
the sprite (eg `@extend .icon-sprites-32x32;`), and then including your image using the variable
matching your image (eg `@include sprite($sprites-32x32-my-image);`).
