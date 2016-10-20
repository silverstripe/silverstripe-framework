import React, { PropTypes } from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import backend from 'lib/Backend';
import injector from 'lib/Injector';
import merge from 'merge';

class FormBuilder extends SilverStripeComponent {

  constructor(props) {
    super(props);

    const schemaStructure = props.schema.schema;
    this.state = { submittingAction: null };
    this.submitApi = backend.createEndpointFetcher({
      url: schemaStructure.attributes.action,
      method: schemaStructure.attributes.method,
    });
    this.mapActionsToComponents = this.mapActionsToComponents.bind(this);
    this.mapFieldsToComponents = this.mapFieldsToComponents.bind(this);
    this.handleSubmit = this.handleSubmit.bind(this);
    this.handleAction = this.handleAction.bind(this);
    this.findField = this.findField.bind(this);
    this.buildComponent = this.buildComponent.bind(this);
  }

  /**
   * When the action is clicked on, records which action was clicked on
   * This can allow for preventing the submit action, such as a custom action for the button
   *
   * @param {Event} event
   */
  handleAction(event) {
    // Custom handlers
    if (typeof this.props.handleAction === 'function') {
      this.props.handleAction(event, this.getFieldValues());
    }

    const name = event.currentTarget.name;

    // Allow custom handlers to cancel event
    if (!event.isPropagationStopped()) {
      this.setState({ submittingAction: name });
    }
  }

  /**
   * Form submission handler passed to the Form Component as a prop.
   * Provides a hook for controllers to access for state and provide custom functionality.
   *
   * @param {Object} data Processed and validated data from redux-form
   * (originally retrieved through getFieldValues())
   * @return {Promise|null}
   */
  handleSubmit(data) {
    // Add form action data (or default to first action, same as browser behaviour)
    const action = this.state.submittingAction
      ? this.state.submittingAction
      : this.props.schema.schema.actions[0].name;

    const dataWithAction = Object.assign({}, data, {
      [action]: 1,
    });
    const headers = {
      'X-Formschema-Request': 'state,schema',
      'X-Requested-With': 'XMLHttpRequest',
    };

    const resetSubmittingFn = () => {
      this.setState({ submittingAction: null });
    };

    const submitFn = (customData) =>
      this.submitApi(customData || dataWithAction, headers)
        .then(formSchema => {
          resetSubmittingFn();
          return formSchema;
        })
        .catch((reason) => {
          // TODO Generic CMS error reporting
          // TODO Handle validation errors
          resetSubmittingFn();
          return reason;
        });

    if (typeof this.props.handleSubmit === 'function') {
      return this.props.handleSubmit(dataWithAction, action, submitFn);
    }

    return submitFn();
  }

  /**
   * Gets all field values based on the assigned form schema, from prop state.
   *
   * @returns {Object}
   */
  getFieldValues() {
    // using state is more efficient and has the same fields, fallback to nested schema
    const schema = this.props.schema.schema;
    const state = this.props.schema.state;

    if (!state) {
      return {};
    }

    return state.fields
      .reduce((prev, curr) => {
        const match = this.findField(schema.fields, curr.id);

        if (!match) {
          return prev;
        }

        // Skip non-data fields
        if (match.type === 'Structural' || match.readOnly === true) {
          return prev;
        }

        return Object.assign({}, prev, {
          [match.name]: curr.value,
        });
      }, {});
  }

  /**
   * Finds the field with matching id from the schema or state, this is mainly for dealing with
   * schema's deep nesting of fields.
   *
   * @param fields
   * @param id
   * @returns {object|undefined}
   */
  findField(fields, id) {
    let result = null;
    if (!fields) {
      return result;
    }

    result = fields.find(field => field.id === id);

    for (const field of fields) {
      if (result) {
        break;
      }
      result = this.findField(field.children, id);
    }
    return result;
  }

  /**
   * Common functionality for building a Field or Action from schema.
   *
   * @param {Object} props Props which every form field receives. Leave it up to the
   *        schema and component to determine which props are required.
   * @returns {*}
   */
  buildComponent(props) {
    let componentProps = props;
    // 'component' key is renamed to 'schemaComponent' in normalize*() methods
    const SchemaComponent = componentProps.schemaComponent !== null
      ? injector.getComponentByName(componentProps.schemaComponent)
      : injector.getComponentByDataType(componentProps.type);

    if (SchemaComponent === null) {
      return null;
    } else if (componentProps.schemaComponent !== null && SchemaComponent === undefined) {
      throw Error(`Component not found in injector: ${componentProps.schemaComponent}`);
    }

    // if no value, it is better to unset it
    if (componentProps.value === null) {
      delete componentProps.value;
    }

    // Inline `input` props into main field props
    // (each component can pick and choose the props required for it's <input>
    // See http://redux-form.com/6.0.5/docs/api/Field.md/#input-props
    componentProps = Object.assign({}, componentProps, componentProps.input);
    delete componentProps.input;

    // Provides container components a place to hook in
    // and apply customisations to scaffolded components.
    const createFn = this.props.createFn;
    if (typeof createFn === 'function') {
      return createFn(SchemaComponent, componentProps);
    }

    return <SchemaComponent key={componentProps.id} {...componentProps} />;
  }

  /**
   * Maps a list of schema fields to their React Component.
   * Only top level form fields are handled here, composite fields (TabSets etc),
   * are responsible for mapping and rendering their children.
   *
   * @param {Array} fields
   * @return {Array}
   */
  mapFieldsToComponents(fields) {
    const FieldComponent = this.props.baseFieldComponent;
    return fields.map((field) => {
      let props = field;

      if (field.children) {
        props = Object.assign(
          {},
          field,
          { children: this.mapFieldsToComponents(field.children) }
        );
      }

      // Don't wrap structural or readonly fields, since they don't need connected fields.
      // The redux-form connected fields also messed up react-bootstrap's tab handling.
      if (field.type === 'Structural' || field.readOnly === true) {
        return this.buildComponent(props);
      }

      return <FieldComponent key={props.id} {...props} component={this.buildComponent} />;
    });
  }

  /**
   * Maps a list of form actions to their React Component.
   *
   * @param {Array} actions
   * @return {Array}
   */
  mapActionsToComponents(actions) {
    return actions.map((action) => {
      const props = Object.assign({}, action);

      if (action.children) {
        props.children = this.mapActionsToComponents(action.children);
      } else {
        props.handleClick = this.handleAction;

        // Reset through componentWillReceiveProps()
        if (this.state.submittingAction === action.name) {
          props.loading = true;
        }
      }

      return this.buildComponent(props);
    });
  }

  /**
   * Merges the structural and state data of a form field.
   * The structure of the objects being merged should match the structures
   * generated by the SilverStripe FormSchema.
   *
   * @param {object} structure - Structural data for a single field.
   * @param {object} state - State data for a single field.
   * @return {object}
   */
  mergeFieldData(structure, state) {
    // could be a dataless field
    if (typeof state === 'undefined') {
      return structure;
    }
    return merge.recursive(true, structure, {
      data: state.data,
      source: state.source,
      messages: state.messages,
      valid: state.valid,
      value: state.value,
    });
  }


  /**
   * If there is structural and state data available merge those data for each field.
   * Otherwise just use the structural data. Ensure that keys don't conflict
   * with redux-form expectations.
   *
   * @param {array} fields
   * @param {Object} state Optional
   * @return {array}
   */
  normalizeFields(fields, state) {
    return fields.map((field) => {
      const fieldState = (state && state.fields)
        ? state.fields.find((item) => item.id === field.id)
        : {};

      const data = merge.recursive(
        true,
        this.mergeFieldData(field, fieldState),
        // Overlap with redux-form prop handling : createFieldProps filters out the 'component' key
        { schemaComponent: field.component }
      );

      if (field.children) {
        data.children = this.normalizeFields(field.children, state);
      }

      return data;
    });
  }

  /**
   * Ensure that keys don't conflict with redux-form expectations.
   *
   * @param {array} actions
   * @return {array}
   */
  normalizeActions(actions) {
    return actions.map((action) => {
      const data = merge.recursive(
        true,
        action,
        // Overlap with redux-form prop handling : createFieldProps filters out the 'component' key
        { schemaComponent: action.component }
      );

      if (action.children) {
        data.children = this.normalizeActions(action.children);
      }

      return data;
    });
  }

  render() {
    const schema = this.props.schema.schema;
    const state = this.props.schema.state;
    const BaseFormComponent = this.props.baseFormComponent;

    // Map form schema to React component attribute names,
    // which requires renaming some of them (by unsetting the original keys)
    const attributes = Object.assign({}, schema.attributes, {
      className: schema.attributes.class,
      encType: schema.attributes.enctype,
    });
    delete attributes.class;
    delete attributes.enctype;

    const {
      asyncValidate,
      onSubmitFail,
      onSubmitSuccess,
      shouldAsyncValidate,
      touchOnBlur,
      touchOnChange,
      persistentSubmitErrors,
      validate,
      form,
    } = this.props;

    const props = {
      form, // required as redux-form identifier
      fields: this.normalizeFields(schema.fields, state),
      actions: this.normalizeActions(schema.actions),
      attributes,
      data: schema.data,
      initialValues: this.getFieldValues(),
      onSubmit: this.handleSubmit,
      mapActionsToComponents: this.mapActionsToComponents,
      mapFieldsToComponents: this.mapFieldsToComponents,
      asyncValidate,
      onSubmitFail,
      onSubmitSuccess,
      shouldAsyncValidate,
      touchOnBlur,
      touchOnChange,
      persistentSubmitErrors,
      validate,
    };

    return <BaseFormComponent {...props} />;
  }
}

const schemaPropType = PropTypes.shape({
  id: PropTypes.string.isRequired,
  schema: PropTypes.shape({
    attributes: PropTypes.shape({
      class: PropTypes.string,
      enctype: PropTypes.string,
    }),
    fields: PropTypes.array.isRequired,
  }).isRequired,
  state: PropTypes.shape({
    fields: PropTypes.array,
  }),
});

const basePropTypes = {
  createFn: PropTypes.func,
  handleSubmit: PropTypes.func,
  handleAction: PropTypes.func,
  asyncValidate: PropTypes.func,
  onSubmitFail: PropTypes.func,
  onSubmitSuccess: PropTypes.func,
  shouldAsyncValidate: PropTypes.func,
  touchOnBlur: PropTypes.bool,
  touchOnChange: PropTypes.bool,
  persistentSubmitErrors: PropTypes.bool,
  validate: PropTypes.func,
  baseFormComponent: PropTypes.func.isRequired,
  baseFieldComponent: PropTypes.func.isRequired,
};

FormBuilder.propTypes = Object.assign({}, basePropTypes, {
  form: PropTypes.string.isRequired,
  schema: schemaPropType.isRequired,
});

export { basePropTypes, schemaPropType };
export default FormBuilder;
