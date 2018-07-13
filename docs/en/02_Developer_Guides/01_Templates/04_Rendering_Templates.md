title: Rendering data to a template
summary: Call and render SilverStripe templates manually.

# Rendering data to a template

Templates do nothing on their own. Rather, they are used to render a particular object.  All of the `<% if %>`, 
`<% loop %>` and other variables are methods or parameters that are called on the current object in 
[scope](syntax#scope).  All that is necessary is that the object is an instance of [ViewableData](api:SilverStripe\View\ViewableData) (or one of its 
subclasses).

The following will render the given data into a template. Given the template:

**app/templates/Coach_Message.ss**
    
```ss
<strong>$Name</strong> is the $Role on our team.
```

Our application code can render into that view using `renderWith`. This method is called on the [ViewableData](api:SilverStripe\View\ViewableData) 
instance with a template name or an array of templates to render. 

**app/code/Page.php**

```php
$arrayData = new SilverStripe\View\ArrayData([
    'Name' => 'John',
    'Role' => 'Head Coach'
]);

echo $arrayData->renderWith('Coach_Message');

// returns "<strong>John</strong> is the Head Coach on our team."

```

<div class="info" markdown="1">
Most classes in SilverStripe you want in your template extend `ViewableData` and allow you to call `renderWith`. This 
includes [Controller](api:SilverStripe\Control\Controller), [FormField](api:SilverStripe\Forms\FormField) and [DataObject](api:SilverStripe\ORM\DataObject) instances.
</div>

```php
$controller->renderWith(['MyController', 'MyBaseController']);

SilverStripe\Security\Security::getCurrentUser()->renderWith('Member_Profile');

```

`renderWith` can be used to override the default template process. For instance, to provide an ajax version of a 
template.

```php
use SilverStripe\CMS\Controllers\ContentController;

class PageController extends ContentController
{
    private static $allowed_actions = ['iwantmyajax'];

    public function iwantmyajax()
    {
        if (Director::is_ajax()) {
            return $this->renderWith('AjaxTemplate');
        } else {
            return $this->httpError(404);
        }
    }
}

```

Any data you want to render into the template that does not extend `ViewableData` should be wrapped in an object that
does, such as `ArrayData` or `ArrayList`.

```php
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Control\Director;
use SilverStripe\CMS\Controllers\ContentController;

class PageController extends ContentController
{
    // ..
    public function iwantmyajax()
    {
        if (Director::is_ajax()) {
            $experience = new ArrayList();
            $experience->push(new ArrayData([
                'Title' => 'First Job'
            ]));

            return $this->customise(new ArrayData([
                'Name' => 'John',
                'Role' => 'Head Coach',
                'Experience' => $experience
            ]))->renderWith('AjaxTemplate');
        } else {
            return $this->httpError(404);
        }
    }
}

```

## Related Lessons
* [Controller actions/DataObjects as pages](https://www.silverstripe.org/learn/lessons/v4/controller-actions-dataobjects-as-pages-1)
* [AJAX behaviour and ViewableData](https://www.silverstripe.org/learn/lessons/v4/ajax-behaviour-and-viewabledata-1)
* [Dealing with arbitrary template data](https://www.silverstripe.org/learn/lessons/v4/dealing-with-arbitrary-template-data-1)
* [Creating filtered views](https://www.silverstripe.org/learn/lessons/v4/creating-filtered-views-1)