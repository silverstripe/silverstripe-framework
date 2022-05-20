---
title: Import CSV Data through a Controller
summary: Data importing through the frontend
icon: upload
---

# Import CSV Data through a Controller

You can have more customised logic and interface feedback through a custom controller. Let's create a simple upload 
form (which is used for `MyDataObject` instances). You can access it through 
`http://yoursite.com/MyController/?flush=all`.


```php
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\Control\Controller;

class MyController extends Controller 
{

    private static $allowed_actions = [
        'Form'
    ];

    protected $template = "BlankPage";

    public function Link($action = null) 
    {
        return Controller::join_links('MyController', $action);
    }

    public function Form() 
    {
        $form = new Form(
            $this,
            'Form',
            new FieldList(
                new FileField('CsvFile', false)
            ),
            new FieldList(
                new FormAction('doUpload', 'Upload')
            ),
            new RequiredFields()
        );
        return $form;
    }

    public function doUpload($data, $form) 
    {
        $loader = new CsvBulkLoader('MyDataObject');
        $results = $loader->load($_FILES['CsvFile']['tmp_name']);
        $messages = [];

        if($results->CreatedCount()) {
            $messages[] = sprintf('Imported %d items', $results->CreatedCount());
        }

        if($results->UpdatedCount()) {
            $messages[] = sprintf('Updated %d items', $results->UpdatedCount());
        }

        if($results->DeletedCount()) {
            $messages[] = sprintf('Deleted %d items', $results->DeletedCount());
        }

        if(!$messages) {
            $messages[] = 'No changes';
        }

        $form->sessionMessage(implode(', ', $messages), 'good');

        return $this->redirectBack();
    }
}
```

[alert]
This interface is not secured, consider using [Permission::check()](api:SilverStripe\Security\Permission::check()) to limit the controller to users with certain 
access rights.
[/alert]
