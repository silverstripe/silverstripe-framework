# Access Control and Page Security

There is a fairly comprehensive security mechanism in place for SilverStripe. If you want to add premium content to your
site you have to figure this stuff out, and it's not entirely obvious. 

## Ways to restrict access

There are a number of ways to restrict access in SilverStripe.  In the security tab in the CMS you can create groups
that have access to certain parts.  The options can be found on the [permissions](/reference/permission) documentation. 

Once you have groups, you can set access for each page for a particular groups.  This can be:
- anyone
- any person who is logged in
- a specific group

It is unclear how this works for data-objects that are not pages.

## The Security Groups in SilverStripe

In the security tab you can make groups for security.  The way this was intended was as follows (this may be a counter
intuitive):

* employees
	* marketing
		* marketing executive

Thus, the further up the hierarchy you go the MORE privileges you can get.  Similarly, you could have:

* members
	* coordinators
		* admins

Where members have some privileges, coordinators slightly more and administrators the most; having each group inheriting
privileges from its parent group.     

## Permission checking is at class level

SilverStripe provides a security mechanism via the *Permission::check* method (see `[api:LeftAndMain]` for examples on how
the admin screens work)

(next step -- go from *Permission::checkMember*...)

### Nuts and bolts -- figuring it out

Here are my notes trying to figure this stuff out. Not really useful unless you're VERY interested in how exactly SS
works.


### Loading the admin page: looking at security

If you go to [your site]/admin *Director.php* maps the 'admin' URL request through a `[api:Director]` rule to the
`[api:CMSMain]` controller (see `[api:CMSMain]`, with no arguments). 

*CMSMain.init()* calls its parent which, of all things is called `[api:LeftAndMain]`. It's in `[api:LeftAndMain]` that the
important security checks are made by calling *Permission::check*. 

`[api:Security::permissionFailure]` is the next utility function you can use to redirect to the login form. 

### Customizing Access Checks in CMS Classes

see `[api:LeftAndMain]`