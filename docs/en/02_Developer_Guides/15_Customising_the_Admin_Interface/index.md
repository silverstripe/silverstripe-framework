title: Customising the Admin Interface
summary: Extend the admin view to provide custom behavior or new features for CMS and admin users.
introduction: The Admin interface can be extended to provide additional functionality to users and custom interfaces for managing data.

The Admin interface is bundled within the SilverStripe Framework but is most commonly used in conjunction with the `cms`
module. The main class for displaying the interface is a specialized [Controller](api:SilverStripe\Control\Controller) called [LeftAndMain](api:SilverStripe\Admin\LeftAndMain), named
as it is designed around a left hand navigation and a main edit form.

Starting with SilverStripe 4, the user interface logic is transitioned from
jQuery and [jQuery.entwine](https://github.com/hafriedlander/jquery.entwine),
which is replaced with [ReactJS](http://reactjs.com/). The transition is
done iteratively, starting with `AssetAdmin` and `CampaignAdmin`.

[CHILDREN]

## How to's

[CHILDREN Folder="How_Tos"]
