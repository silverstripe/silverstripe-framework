import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as formsActions from 'state/forms/FormsActions';
import * as schemaActions from 'state/schema/SchemaActions';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import FormComponent from 'components/Form/Form';
import FormActionComponent from 'components/FormAction/FormAction';
import TextField from 'components/TextField/TextField';
import HiddenField from 'components/HiddenField/HiddenField';
import GridField from 'components/GridField/GridField';
import fetch from 'isomorphic-fetch';
import deepFreeze from 'deep-freeze';
import backend from 'lib/Backend';

import es6promise from 'es6-promise';
es6promise.polyfill();

// Using this to map field types to components until we implement dependency injection.
const fakeInjector = {

  /**
   * Components registered with the fake DI container.
   */
  components: {
    TextField,
    GridField,
    HiddenField,
  },

  /**
   * Gets the component matching the passed component name.
   * Used when a component type is provided bt the form schema.
   *
   * @param string componentName - The name of the component to get from the injector.
   *
   * @return object|null
   */
  getComponentByName(componentName) {
    return this.components[componentName];
  },

  /**
   * Default data type to component mappings.
   * Used as a fallback when no component type is provided in the form schema.
   *
   * @param string dataType - The data type provided by the form schema.
   *
   * @return object|null
   */
  getComponentByDataType(dataType) {
    switch (dataType) {
      case 'Text':
        return this.components.TextField;
      case 'Hidden':
        return this.components.HiddenField;
      case 'Custom':
        return this.components.GridField;
      default:
        return null;
    }
  },
};

export class FormBuilderComponent extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.formSchemaPromise = null;
    this.state = { isFetching: false };

    this.mapActionsToComponents = this.mapActionsToComponents.bind(this);
    this.mapFieldsToComponents = this.mapFieldsToComponents.bind(this);
    this.handleFieldUpdate = this.handleFieldUpdate.bind(this);
    this.handleSubmit = this.handleSubmit.bind(this);
    this.removeForm = this.removeForm.bind(this);
  }

  componentDidMount() {
    this.fetch();
  }

  /**
   * Fetches data used to generate a form. This can be form schema and or form state data.
   * When the response comes back the data is saved to state.
   *
   * @param boolean schema - If form schema data should be returned in the response.
   * @param boolean state - If form state data should be returned in the response.
   *
   * @return object - Promise from the AJAX request.
   */
  fetch(schema = true, state = true) {
    const headerValues = [];

    if (this.state.isFetching === true) {
      return this.formSchemaPromise;
    }

    if (schema === true) {
      headerValues.push('schema');
    }

    if (state === true) {
      headerValues.push('state');
    }

    this.formSchemaPromise = fetch(this.props.schemaUrl, {
      headers: { 'X-FormSchema-Request': headerValues.join() },
      credentials: 'same-origin',
    })
      .then(response => response.json())
      .then(json => {
        const formSchema = Object.assign({}, { id: json.id, schema: json.schema });
        const formState = Object.assign({}, json.state);

        // TODO See "Enable once <CampaignAdmin> ..." below
        // this.setState({ isFetching: false });

        if (typeof formSchema.id !== 'undefined') {
          const defaultData = {
            ID: formSchema.schema.id,
            SecurityID: this.props.config.SecurityID,
          };

          if (formSchema.schema.actions.length > 0) {
            defaultData[formSchema.schema.actions[0].name] = 1;
          }

          this.submitApi = backend.createEndpointFetcher({
            url: formSchema.schema.attributes.action,
            method: formSchema.schema.attributes.method,
            defaultData,
          });

          this.props.schemaActions.setSchema(formSchema);
        }

        if (typeof formState.id !== 'undefined') {
          this.props.formsActions.addForm(formState);
        }
      });

    // TODO Enable once <CampaignAdmin> is initialised via page.js route callbacks
    // At the moment, it's running an Entwine onadd() rule which ends up
    // rendering the index view, and only then calling route.start() to
    // match the detail view (admin/campaigns/set/:id/show).
    // This causes the form builder to be unmounted during a fetch() call.
    // this.setState({ isFetching: true });

    return this.formSchemaPromise;
  }

  /**
   * Update handler passed down to each form field as a prop.
   * Form fields call this method when their state changes.
   *
   * You can pass an optional callback as the third param. This can be used to
   * implement custom behaviour. For example you can use `createFn` hook from
   * your controller context like this.
   *
   * controller.js
   * ...
   * detailEditFormCreateFn(Component, props) {
   *   const extendedProps = Object.assign({}, props, {
   *     handleFieldUpdate: (event, updates) => {
   *       props.handleFieldUpdate(event, updates, (formId, updateFieldAction) => {
   *         const customUpdates = Object.assign({}, updates, {
   *           value: someCustomParsing(updates.value),
   *         };
   *
   *         updateFieldAction(formId, customUpdates);
   *       });
   *     },
   *   });
   *
   *   return <Component {...extendedProps} />;
   * }
   * ...
   *
   * @param {object} event - Change event from the form field component.
   * @param {object} updates - Values to set in state.
   * @param {string} updates.id - Field ID. Required to identify the field in the store.
   * @param {function} [fn] - Optional function for custom behaviour. See example in description.
   */
  handleFieldUpdate(event, updates, fn) {
    if (typeof fn !== 'undefined') {
      fn(this.props.formId, this.props.formsActions.updateField);
    } else {
      this.props.formsActions.updateField(this.props.formId, updates);
    }
  }

  /**
   * Form submission handler passed to the Form Component as a prop.
   * Provides a hook for controllers to access for state and provide custom functionality.
   *
   * For example:
   *
   * controller.js
   * ```
   * constructor(props) {
   *   super(props);
   *   this.handleSubmit = this.handleSubmit.bind(this);
   * }
   *
   * handleSubmit(event, fieldValues, submitFn) {
   *   event.preventDefault();
   *
   *   // Apply custom validation.
   *   if (!this.validate(fieldValues)) {
   *     return;
   *   }
   *
   *   submitFn();
   * }
   *
   * render() {
   *   return <FormBuilder handleSubmit={this.handleSubmit} />
   * }
   * ```
   *
   * @param {Object} event
   */
  handleSubmit(event) {
    const schemaFields = this.props.schemas[this.props.schemaUrl].schema.fields;
    const fieldValues = this.props.forms[this.props.formId].fields
      .reduce((prev, curr) => Object.assign({}, prev, {
        [schemaFields.find(schemaField => schemaField.id === curr.id).name]: curr.value,
      }), {});

    const submitFn = () => {
      this.props.formsActions.submitForm(
        this.submitApi,
        this.props.formId,
        fieldValues
      );
    };

    if (typeof this.props.handleSubmit !== 'undefined') {
      this.props.handleSubmit(event, fieldValues, submitFn);
      return;
    }

    event.preventDefault();
    submitFn();
  }

  /**
   * Maps a list of schema fields to their React Component.
   * Only top level form fields are handled here, composite fields (TabSets etc),
   * are responsible for mapping and rendering their children.
   *
   * @param array fields
   *
   * @return array
   */
  mapFieldsToComponents(fields) {
    const createFn = this.props.createFn;
    const handleFieldUpdate = this.handleFieldUpdate;

    return fields.map((field, i) => {
      const Component = field.component !== null
        ? fakeInjector.getComponentByName(field.component)
        : fakeInjector.getComponentByDataType(field.type);

      if (Component === null) {
        return null;
      }

      // Props which every form field receives.
      // Leave it up to the schema and component to determine
      // which props are required.
      const props = deepFreeze(Object.assign({}, field, { handleFieldUpdate }));

      // Provides container components a place to hook in
      // and apply customisations to scaffolded components.
      if (typeof createFn === 'function') {
        return createFn(Component, props);
      }

      return <Component key={i} {...props} />;
    });
  }

  /**
   * Maps a list of form actions to their React Component.
   *
   * @param array actions
   *
   * @return array
   */
  mapActionsToComponents(actions) {
    const createFn = this.props.createFn;

    return actions.map((action, i) => {
      const props = deepFreeze(action);

      if (typeof createFn === 'function') {
        return createFn(FormActionComponent, props);
      }

      return <FormActionComponent key={i} {...props} />;
    });
  }

  /**
   * Merges the structural and state data of a form field.
   * The structure of the objects being merged should match the structures
   * generated by the SilverStripe FormSchema.
   *
   * @param object structure - Structural data for a single field.
   * @param object state - State data for a single field.
   *
   * @return object
   */
  mergeFieldData(structure, state) {
    return Object.assign({}, structure, {
      data: Object.assign({}, structure.data, state.data),
      messages: state.messages,
      valid: state.valid,
      value: state.value,
    });
  }

  /**
   * Cleans up Redux state used by the form when the Form component is unmonuted.
   *
   * @param {string} formId - ID of the form to clean up.
   */
  removeForm(formId) {
    this.props.formsActions.removeForm(formId);
  }

  render() {
    const formSchema = this.props.schemas[this.props.schemaUrl];
    const formState = this.props.forms[this.props.formId];

    // If the response from fetching the initial data
    // hasn't come back yet, don't render anything.
    if (!formSchema) {
      return null;
    }

    // Map form schema to React component attribute names,
    // which requires renaming some of them (by unsetting the original keys)
    const attributes = Object.assign({}, formSchema.schema.attributes, {
      class: null,
      className: formSchema.schema.attributes.class,
      enctype: null,
      encType: formSchema.schema.attributes.enctype,
    });

    // If there is structural and state data availabe merge those data for each field.
    // Otherwise just use the structural data.
    const fieldData = formSchema.schema && formState
      ? formSchema.schema.fields.map((f, i) => this.mergeFieldData(f, formState.fields[i]))
      : formSchema.schema.fields;

    const formProps = {
      actions: formSchema.schema.actions,
      attributes,
      componentWillUnmount: this.removeForm,
      data: formSchema.schema.data,
      fields: fieldData,
      formId: formSchema.id,
      handleSubmit: this.handleSubmit,
      mapActionsToComponents: this.mapActionsToComponents,
      mapFieldsToComponents: this.mapFieldsToComponents,
    };

    return <FormComponent {...formProps} />;
  }
}

FormBuilderComponent.propTypes = {
  config: React.PropTypes.object,
  createFn: React.PropTypes.func,
  forms: React.PropTypes.object.isRequired,
  formsActions: React.PropTypes.object.isRequired,
  formId: React.PropTypes.string.isRequired,
  handleSubmit: React.PropTypes.func,
  schemas: React.PropTypes.object.isRequired,
  schemaActions: React.PropTypes.object.isRequired,
  schemaUrl: React.PropTypes.string.isRequired,
};

function mapStateToProps(state) {
  return {
    config: state.config,
    forms: state.forms,
    schemas: state.schemas,
  };
}

function mapDispatchToProps(dispatch) {
  return {
    formsActions: bindActionCreators(formsActions, dispatch),
    schemaActions: bindActionCreators(schemaActions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(FormBuilderComponent);
