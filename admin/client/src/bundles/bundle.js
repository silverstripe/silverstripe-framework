// Legacy translation handler
require('i18n.js');

// Expose fields (see webpack config for matching "externals" config)
require('expose?SilverStripeComponent!lib/SilverStripeComponent');
require('expose?Backend!lib/Backend');
require('expose?schemaFieldValues!lib/schemaFieldValues');
require('expose?FieldHolder!components/FieldHolder/FieldHolder');
require('expose?Form!components/Form/Form');
require('expose?FormConstants!components/Form/FormConstants');
require('expose?FormAlert!components/FormAlert/FormAlert');
require('expose?FormAction!components/FormAction/FormAction');
require('expose?SchemaActions!state/schema/SchemaActions');
require('expose?FormBuilder!components/FormBuilder/FormBuilder');
require('expose?FormBuilderLoader!containers/FormBuilderLoader/FormBuilderLoader');
require('expose?FormBuilderModal!components/FormBuilderModal/FormBuilderModal');
require('expose?GridField!components/GridField/GridField');
require('expose?GridFieldCell!components/GridField/GridFieldCell');
require('expose?GridFieldHeader!components/GridField/GridFieldHeader');
require('expose?GridFieldHeaderCell!components/GridField/GridFieldHeaderCell');
require('expose?GridFieldRow!components/GridField/GridFieldRow');
require('expose?GridFieldTable!components/GridField/GridFieldTable');
require('expose?HiddenField!components/HiddenField/HiddenField');
require('expose?TextField!components/TextField/TextField');
require('expose?LiteralField!components/LiteralField/LiteralField');
require('expose?Toolbar!components/Toolbar/Toolbar');
require('expose?Breadcrumb!components/Breadcrumb/Breadcrumb');
require('expose?BreadcrumbsActions!state/breadcrumbs/BreadcrumbsActions');
require('expose?Badge!components/Badge/Badge');
require('expose?Config!lib/Config');
require('expose?DataFormat!lib/DataFormat');
require('expose?ReducerRegister!lib/ReducerRegister');
require('expose?ReactRouteRegister!lib/ReactRouteRegister');
require('expose?Injector!lib/Injector');
require('expose?Router!lib/Router');

// Legacy CMS
require('../legacy/sspath.js');
require('../legacy/ssui.core.js');
require('../legacy/LeftAndMain.js');
require('../legacy/LeftAndMain.ActionTabSet.js');
require('../legacy/LeftAndMain.Panel.js');
require('../legacy/LeftAndMain.Tree.js');
require('../legacy/LeftAndMain.Content.js');
require('../legacy/LeftAndMain.EditForm.js');
require('../legacy/LeftAndMain.Menu.js');
require('../legacy/LeftAndMain.Preview.js');
require('../legacy/LeftAndMain.BatchActions.js');
require('../legacy/LeftAndMain.FieldHelp.js');
require('../legacy/LeftAndMain.FieldDescriptionToggle.js');
require('../legacy/LeftAndMain.TreeDropdownField.js');
require('../legacy/AddToCampaignForm.js');
require('../legacy/SecurityAdmin.js');
require('../legacy/ModelAdmin.js');

// Legacy form fields
// Fields used by core legacy UIs, or available to users
// To do: determine better way of using webpack to pull in optional javascript
require('../legacy/AssetUploadField.js');
require('../legacy/ConfirmedPasswordField.js');
require('../legacy/UploadField.js');
require('../legacy/SelectionGroup.js');
require('../legacy/DateField.js');
require('../legacy/ToggleCompositeField.js');
require('../legacy/MemberDatetimeOptionsetField.js');
require('../legacy/TreeDropdownField.js');
require('../legacy/DateField.js');
require('../legacy/HtmlEditorField.js');
require('../legacy/TabSet.js');
require('../legacy/GridField.js');

require('../boot/index.js');
