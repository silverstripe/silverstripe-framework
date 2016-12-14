import React, { PropTypes, Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import fetch from 'isomorphic-fetch';
import deepFreeze from 'deep-freeze-strict';
import {
  Field as ReduxFormField,
  reduxForm,
  SubmissionError,
  destroy as reduxDestroyForm,
  autofill,
} from 'redux-form';
import * as schemaActions from 'state/schema/SchemaActions';
import merge from 'merge';
import Form from 'components/Form/Form';
import FormBuilder, { basePropTypes, schemaPropType } from 'components/FormBuilder/FormBuilder';

class FormBuilderLoader extends Component {

  constructor(props) {
    super(props);

    this.handleSubmit = this.handleSubmit.bind(this);
    this.clearSchema = this.clearSchema.bind(this);
    this.reduceSchemaErrors = this.reduceSchemaErrors.bind(this);
    this.handleAutofill = this.handleAutofill.bind(this);
  }

  componentDidMount() {
    this.fetch();
  }

  componentDidUpdate(prevProps) {
    if (this.props.schemaUrl !== prevProps.schemaUrl) {
      this.clearSchema(prevProps.schemaUrl);
      this.fetch();
    }
  }

  componentWillUnmount() {
    this.clearSchema(this.props.schemaUrl);
  }

  /**
   * Get server-side validation messages returned and display them on the form.
   *
   * @param state
   * @returns {object}
   */
  getMessages(state) {
    const messages = {};

    // only error messages are collected
    // TODO define message type as standard "success", "info", "warning" and "danger"
    if (state && state.fields) {
      state.fields.forEach((field) => {
        if (field.message) {
          messages[field.name] = field.message;
        }
      });
    }
    return messages;
  }

  clearSchema(schemaUrl) {
    if (schemaUrl) {
      // we will reload the schema anyway when we mount again, this is here so that redux-form
      // doesn't preload previous data mistakenly. (since it only accepts initialised values)
      reduxDestroyForm(schemaUrl);
      this.props.actions.schema.setSchema(schemaUrl, null);
    }
  }

  /**
   * Handles updating the schema after response is received and gathering server-side validation
   * messages.
   *
   * @param {object} data
   * @param {string} action
   * @param {function} submitFn
   * @returns {Promise}
   */
  handleSubmit(data, action, submitFn) {
    let promise = null;
    if (typeof this.props.handleSubmit === 'function') {
      promise = this.props.handleSubmit(data, action, submitFn);
    } else {
      promise = submitFn();
    }

    if (!promise) {
      throw new Error('Promise was not returned for submitting');
    }

    return promise
      .then(formSchema => {
        let schema = formSchema;
        if (schema) {
          // Strip errors out of schema response in preparation for setSchema and SubmissionError
          schema = this.reduceSchemaErrors(schema);
          this.props.actions.schema.setSchema(this.props.schemaUrl, schema);
        }
        return schema;
      })
      // TODO Suggest storing messages in a separate redux store rather than throw an error
      // ref: https://github.com/erikras/redux-form/issues/94#issuecomment-143398399
      .then(formSchema => {
        if (!formSchema || !formSchema.state) {
          return formSchema;
        }
        const messages = this.getMessages(formSchema.state);

        if (Object.keys(messages).length) {
          throw new SubmissionError(messages);
        }
        return formSchema;
      });
  }

  /**
   * Given a submitted schema, ensure that any errors property is merged safely into
   * the state.
   *
   * @param {Object} schema - New schema result
   * @return {Object}
   */
  reduceSchemaErrors(schema) {
    // Skip if there are no errors
    if (!schema.errors) {
      return schema;
    }

    // Inherit state from current schema if not being assigned in this request
    let reduced = Object.assign({}, schema);
    if (!reduced.state) {
      reduced = Object.assign({}, reduced, { state: this.props.schema.state });
    }

    // Modify state.fields and replace state.messages
    reduced = Object.assign({}, reduced, {
      state: Object.assign({}, reduced.state, {
        // Replace message property for each field
        fields: reduced.state.fields.map((field) => Object.assign({}, field, {
          message: schema.errors.find((error) => error.field === field.name),
        })),
        // Non-field messages
        messages: schema.errors.filter((error) => !error.field),
      }),
    });

    // Can be safely discarded
    delete reduced.errors;
    return deepFreeze(reduced);
  }

  /**
   * Checks for any state override data provided, which will take precendence over the state
   * received through fetch.
   *
   * This is important for editing a WYSIWYG item which needs the form schema and only parts of
   * the form state.
   *
   * @param {object} state
   * @returns {object}
   */
  overrideStateData(state) {
    if (!this.props.stateOverrides || !state) {
      return state;
    }
    const fieldOverrides = this.props.stateOverrides.fields;
    let fields = state.fields;
    if (fieldOverrides && fields) {
      fields = fields.map((field) => {
        const fieldOverride = fieldOverrides.find((override) => override.name === field.name);
        // need to be recursive for the unknown-sized "data" properly
        return (fieldOverride) ? merge.recursive(true, field, fieldOverride) : field;
      });
    }

    return Object.assign({},
      state,
      this.props.stateOverrides,
      { fields }
    );
  }

  /**
   * Call to make the fetching happen
   *
   * @param headerValues
   * @returns {*}
   */
  callFetch(headerValues) {
    return fetch(this.props.schemaUrl, {
      headers: { 'X-FormSchema-Request': headerValues.join(',') },
      credentials: 'same-origin',
    })
      .then(response => response.json());
  }

  /**
   * Fetches data used to generate a form. This can be form schema and/or form state data.
   * When the response comes back the data is saved to state.
   *
   * @param {Boolean} schema If form schema data should be returned in the response.
   * @param {Boolean} state If form state data should be returned in the response.
   * @return {Object} Promise from the AJAX request.
   */
  fetch(schema = true, state = true) {
    // Note: `errors` is only valid for submissions, not schema requests, so omitted here
    const headerValues = [];

    if (schema) {
      headerValues.push('schema');
    }

    if (state) {
      headerValues.push('state');
    }

    if (this.props.loading) {
      return Promise.resolve({});
    }

    // using `this.state.fetching` caused race-condition issues.
    this.props.actions.schema.setSchemaLoading(this.props.schemaUrl, true);

    return this.callFetch(headerValues)
      .then(formSchema => {
        this.props.actions.schema.setSchemaLoading(this.props.schemaUrl, false);

        if (typeof formSchema.id !== 'undefined') {
          const overriddenSchema = Object.assign({},
            formSchema,
            { state: this.overrideStateData(formSchema.state) }
          );
          this.props.actions.schema.setSchema(this.props.schemaUrl, overriddenSchema);

          return overriddenSchema;
        }
        return formSchema;
      });
  }

  /**
   * Sets the value of a field based on actions within other fields, this is a more semantic way to
   * change a field's value than calling onChange() for the target field.
   *
   * By virtue of redux-form, it also flags the field as "meta.autofilled"
   *
   * @param field
   * @param value
   */
  handleAutofill(field, value) {
    this.props.actions.reduxForm.autofill(this.props.schemaUrl, field, value);
  }

  render() {
    // If the response from fetching the initial data
    // hasn't come back yet, don't render anything.
    if (!this.props.schema || !this.props.schema.schema || this.props.loading) {
      return null;
    }

    const props = Object.assign({}, this.props, {
      form: this.props.schemaUrl,
      onSubmitSuccess: this.props.onSubmitSuccess,
      handleSubmit: this.handleSubmit,
      onAutofill: this.handleAutofill,
    });
    return <FormBuilder {...props} />;
  }
}

FormBuilderLoader.propTypes = Object.assign({}, basePropTypes, {
  actions: PropTypes.shape({
    schema: PropTypes.object,
    reduxFrom: PropTypes.object,
  }),
  schemaUrl: PropTypes.string.isRequired,
  schema: schemaPropType,
  form: PropTypes.string,
  submitting: PropTypes.bool,
});

FormBuilderLoader.defaultProps = {
  // Perform this *outside* of render() to avoid re-rendering of the whole DOM structure
  // every time render() is triggered.
  baseFormComponent: reduxForm()(Form),
  baseFieldComponent: ReduxFormField,
};

function mapStateToProps(state, ownProps) {
  const schema = state.schemas[ownProps.schemaUrl];

  const reduxFormState = state.form && state.form[ownProps.schemaUrl];
  const submitting = reduxFormState && reduxFormState.submitting;
  const values = reduxFormState && reduxFormState.values;

  const stateOverrides = schema && schema.stateOverride;
  const loading = schema && schema.metadata && schema.metadata.loading;

  return { schema, submitting, values, stateOverrides, loading };
}

function mapDispatchToProps(dispatch) {
  return {
    actions: {
      schema: bindActionCreators(schemaActions, dispatch),
      reduxForm: bindActionCreators({ autofill }, dispatch),
    },
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(FormBuilderLoader);
