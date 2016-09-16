import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as formActions from 'state/form/FormActions';
import * as schemaActions from 'state/schema/SchemaActions';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import Form from 'components/Form/Form';
import fetch from 'isomorphic-fetch';
import backend from 'lib/Backend';
import injector from 'lib/Injector';
import merge from 'merge';

import es6promise from 'es6-promise';
es6promise.polyfill();

export class FormBuilderComponent extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.formSchemaPromise = null;
    this.state = { isFetching: false };

    this.mapActionsToComponents = this.mapActionsToComponents.bind(this);
    this.mapFieldsToComponents = this.mapFieldsToComponents.bind(this);
    this.handleFieldUpdate = this.handleFieldUpdate.bind(this);
    this.handleSubmit = this.handleSubmit.bind(this);
    this.handleAction = this.handleAction.bind(this);
    this.removeForm = this.removeForm.bind(this);
    this.getFormId = this.getFormId.bind(this);
    this.getFormSchema = this.getFormSchema.bind(this);
    this.findField = this.findField.bind(this);
  }

  /**
   * Get the schema for this form
   *
   * @returns {array}
   */
  getFormSchema() {
    return this.props.schemas[this.props.schemaUrl];
  }

  /**
   * Gets the ID for this form
   *
   * @returns {String}
   */
  getFormId() {
    const schema = this.getFormSchema();
    if (schema) {
      return schema.id;
    }
    return null;
  }

  componentDidMount() {
    this.fetch();
  }

  componentDidUpdate(prevProps) {
    if (this.props.schemaUrl !== prevProps.schemaUrl) {
      this.fetch();
    }
  }

  /**
   * Fetches data used to generate a form. This can be form schema and or form state data.
   * When the response comes back the data is saved to state.
   *
   * @param {Boolean} schema If form schema data should be returned in the response.
   * @param {Boolean} state If form state data should be returned in the response.
   *
   * @return {Object} Promise from the AJAX request.
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
          this.props.formActions.addForm(formState);
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
      fn(this.getFormId(), this.props.formActions.updateField);
    } else {
      this.props.formActions.updateField(this.getFormId(), updates);
    }
  }

  /**
   * When the action is clicked on, records which action was clicked on
   * This can allow for preventing the submit action, such as a custom action for the button
   *
   * @param event
   * @param submitAction
   */
  handleAction(event, submitAction) {
    this.props.formActions.setSubmitAction(this.getFormId(), submitAction);
    if (typeof this.props.handleAction === 'function') {
      this.props.handleAction(event, submitAction, this.getFieldValues());
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
   * @return {Promise|null}
   */
  handleSubmit(event) {
    const fieldValues = this.getFieldValues();

    const submitFn = () => this.props.formActions.submitForm(
      this.submitApi,
      this.getFormId(),
      fieldValues
    );

    if (typeof this.props.handleSubmit !== 'undefined') {
      return this.props.handleSubmit(event, fieldValues, submitFn);
    }

    event.preventDefault();
    return submitFn();
  }

  /**
   * Gets all field values based on the assigned form schema, from prop state.
   *
   * @returns {Object}
   */
  getFieldValues() {
    const schema = this.props.schemas[this.props.schemaUrl];
    // using state is more efficient and has the same fields, fallback to nested schema
    const fields = (schema.state)
      ? schema.state.fields
      : schema.schema.fields;

    return this.props.form[this.getFormId()].fields
      .reduce((prev, curr) => {
        const match = this.findField(fields, curr.id);
        if (!match) {
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
   * @param field
   * @param extraProps
   * @returns {*}
   */
  buildComponent(field, extraProps = {}) {
    const Component = field.component !== null
      ? injector.getComponentByName(field.component)
      : injector.getComponentByDataType(field.type);

    if (Component === null) {
      return null;
    } else if (field.component !== null && Component === undefined) {
      throw Error(`Component not found in injector: ${field.component}`);
    }

    // Props which every form field receives.
    // Leave it up to the schema and component to determine
    // which props are required.
    const props = Object.assign({}, field, extraProps);

    // Provides container components a place to hook in
    // and apply customisations to scaffolded components.
    const createFn = this.props.createFn;
    if (typeof createFn === 'function') {
      return createFn(Component, props);
    }

    return <Component key={props.id} {...props} />;
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
    return fields.map((field) => {
      // Events
      const extraProps = { onChange: this.handleFieldUpdate };

      // Build child nodes
      if (field.children) {
        extraProps.children = this.mapFieldsToComponents(field.children);
      }

      return this.buildComponent(field, extraProps);
    });
  }

  /**
   * Maps a list of form actions to their React Component.
   *
   * @param {Array} actions
   * @return {Array}
   */
  mapActionsToComponents(actions) {
    const form = this.props.form[this.getFormId()];

    return actions.map((action) => {
      const loading = (form && form.submitting && form.submitAction === action.name);
      // Events
      const extraProps = {
        handleClick: this.handleAction,
        loading,
        disabled: loading || action.disabled,
      };

      // Build child nodes
      if (action.children) {
        extraProps.children = this.mapActionsToComponents(action.children);
      }

      return this.buildComponent(action, extraProps);
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
   * Cleans up Redux state used by the form when the Form component is unmonuted.
   *
   * @param {string} formId - ID of the form to clean up.
   */
  removeForm(formId) {
    this.props.formActions.removeForm(formId);
  }

  /**
   * If there is structural and state data availabe merge those data for each field.
   * Otherwise just use the structural data.
   */
  getFieldData(formFields, formState) {
    if (!formFields || !formState || !formState.fields) {
      return formFields;
    }

    return formFields.map((field) => {
      const state = formState.fields.find((item) => item.id === field.id);
      const data = this.mergeFieldData(field, state);

      if (field.children) {
        data.children = this.getFieldData(field.children, formState);
      }

      return data;
    });
  }

  render() {
    const formId = this.getFormId();
    if (!formId) {
      return null;
    }
    const formSchema = this.getFormSchema();
    const formState = this.props.form[formId];

    // If the response from fetching the initial data
    // hasn't come back yet, don't render anything.
    if (!formSchema || !formSchema.schema) {
      return null;
    }

    // Map form schema to React component attribute names,
    // which requires renaming some of them (by unsetting the original keys)
    const attributes = Object.assign({}, formSchema.schema.attributes, {
      className: formSchema.schema.attributes.class,
      encType: formSchema.schema.attributes.enctype,
    });
    // these two still cause silent errors
    delete attributes.class;
    delete attributes.enctype;

    const fieldData = this.getFieldData(formSchema.schema.fields, formState);

    const formProps = {
      actions: formSchema.schema.actions,
      attributes,
      componentWillUnmount: this.removeForm,
      data: formSchema.schema.data,
      fields: fieldData,
      formId,
      handleSubmit: this.handleSubmit,
      mapActionsToComponents: this.mapActionsToComponents,
      mapFieldsToComponents: this.mapFieldsToComponents,
    };

    return <Form {...formProps} />;
  }
}

FormBuilderComponent.propTypes = {
  config: React.PropTypes.object,
  createFn: React.PropTypes.func,
  form: React.PropTypes.object.isRequired,
  formActions: React.PropTypes.object.isRequired,
  handleSubmit: React.PropTypes.func,
  handleAction: React.PropTypes.func,
  schemas: React.PropTypes.object.isRequired,
  schemaActions: React.PropTypes.object.isRequired,
  schemaUrl: React.PropTypes.string.isRequired,
};

function mapStateToProps(state) {
  return {
    config: state.config,
    form: state.form,
    schemas: state.schemas,
  };
}

function mapDispatchToProps(dispatch) {
  return {
    formActions: bindActionCreators(formActions, dispatch),
    schemaActions: bindActionCreators(schemaActions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(FormBuilderComponent);
