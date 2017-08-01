# Customising React Forms

Forms that are rendered with React use the [ReduxForm](http://redux-form.com) library and are based on schema definitions that come from the server. To customise these forms, you can apply middleware that updates the schema or applies validation.

## Toggling a form field

Let's have a field hide or show based on the state of another field. We want the field "State" to show if `Country === 'US'`.

First, we need to add a customisation to all form fields that allows them to be toggleable.

_my-module/js/src/HideableComponent.js
```js
import React from 'react';

const HideableComponent = ({Component, ...props}) => (
  props.shouldHide ? null : <Component {...props} />
);

HideableComponent.propTypes = {
  shouldHide: React.PropTypes.boolean
};

HideableComponent.defaultProps = {
  shouldHide: false
};

export default (Component) => (props) => (
  props.shouldHide ? null : <Component {...props} />
);
```

Now, let's apply this through Injector.

_my-module/js/main.js_
```js
Injector.transform(
  'toggle-field',
  (updater) => {
    updater.component('ReduxFormField', HideableComponentCreator);
  }
);
```

Lastly, we need to apply a schema transformation using `updater.form.alterSchema()`.

```js
Injector.transform(
  'my-toggle',
  (updater) => {
    updater.form.alterSchema(
      'AssetAdmin.*',
      (form) =>
        form
          .updateField('State', {
            shouldHide: form.getValue('Country') !== 'US'
          })
          .getState()
    )
  }
);

```

## Conditionally adding a CSS class to a form field

In this example, we'll add the class "danger" to the `Price` field when `TicketsRemaining` is less than 10.

```js
Injector.transform(
  'my-css',
  (updater) => {
    updater.form.alterSchema(
      'AssetAdmin.*',
      (form) =>
        form
          .setFieldClass('Price', 'danger', (form.getValue('TicketsRemaining') < 10))
          .getState()
    );
  }
);
```

## Using a custom component

In this example, we'll replace a plain text field for `PhoneNumber` with one that is broken up into three separate text fields.

First, we need to create the `PrettyPhoneNumberField` component.

_my-module/js/src/PrettyPhoneNumberField.js_
```js
import React from 'react';

export default (props) => {
  const [area, exchange, ext] = props.value.split('-');
  function handleChange (i, e) {
    const parts = props.value.split('-');
    parts[i] = e.target.value;
    const formatted = parts.join('-');
    props.onChange(formatted, e);
  };
  return (
    <div>
      (<input type="text" value={area} onChange={handleChange.bind(null, 0)}/>)
      <input type="text" value={exchange} onChange={handleChange.bind(null, 1)}/> -
      <input type="text" value={ext} onChange={handleChange.bind(null, 2)}/>
    </div>
  );
};
```

Now, we'll need to override the `PhoneNumber` field with custom component.

```js
Injector.transform(
  'my-custom-component',
  (updater) => {
    updater.form.alterSchema(
      'AssetAdmin.*',
      (form) =>
        form
          .setFieldComponent('PhoneNumber', 'PrettyPhoneNumberField')
          .getState()
    );
  }
);
```

## Custom validation

In this example, we'll add a computed validation rule. If `Country` is set to "US", we'll validate the postal code against a length of 5. If not, we'll use a length of 4.

```js
Injector.transform(
  'my-validation',
  (updater) => {
    updater.form.addValidation(
      'AssetAdmin.*',
      (values, errors) => {
        const requiredLength = values.Country === 'US' ? 5 : 4;
        if (!values.Country || !values.PostalCode) {
          return;
        }
        return {
          ...errors,
          PostalCode: values.PostalCode.length !== requiredLength ? 'Invalid postal code' : null,
        };
      }
    )
  }
);
```

## Adding a "confirm" state to a form action

In this example, we'll have a form action expose two new buttons for "confirm" and "cancel" when clicked. This type of behaviour could be useful for a delete action, for instance, as an alternative to throwing `window.confirm()`.

First, we need to create the `ConfirmingFormAction` component.

_my-module/js/src/ConfirmingFormAction.js_
```js
import React from 'react';

export default (FormAction) => {
  class ConfirmingFormAction extends React.Component {
    constructor(props) {
      super(props);
      this.state = { confirming: false };
      this.confirm = this.confirm.bind(this);
      this.cancel = this.cancel.bind(this);
      this.preClick = this.preClick.bind(this);
    }
    
    confirm(e) {
      this.props.handleClick(e, this.props.name || this.props.id);
      this.setState({ confirming: false });
    }
    
    cancel() {
      this.setState({ confirming: false });
    }
    
    preClick(event) {
      event.preventDefault();
      this.setState( {confirming: true });
    }

    render() {
      const { confirmText, cancelText } = this.props;
      const buttonProps = {
        ...this.props,
        extraClass: 'ss-ui-action-constructive',
        attributes: {
          ...this.props.attributes,
          type: 'button'
        },
      };
      delete buttonProps.name;
      delete buttonProps.type;

      const hideStyle = {
        display: this.state.confirming ? null : 'none'
      };

      return (
        <div>
          <FormAction { ...buttonProps } handleClick={this.preClick} />
          <button style={hideStyle} key="confirm" type="submit" name={this.props.name} onClick={this.confirm}>
            {confirmText}
          </button>
          <button style={hideStyle} key="cancel" type="button" onClick={this.cancel}>{cancelText}</button>
        </div>
      );
    }
  }

  return ConfirmingFormAction;
}
```

Now, let's apply this new component to a very specific form action.

```js
Injector.transform(
  'my-confirm',
  (updater) => {
    updater.form.alterSchema(
      'AssetAdmin.*',
      (form) =>
          form
            .updateField('action_delete', {
              confirmText: 'Are you sure you want to delete?',
              cancelText: 'No!! Cancel!!!!'
            })
            .getState()
    )
  }
);
```
