import React, { PropTypes, Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import fetch from 'isomorphic-fetch';
import {
  Field as ReduxFormField,
  reduxForm,
  SubmissionError,
  destroy as reduxDestroyForm,
} from 'redux-form';
import * as schemaActions from 'state/schema/SchemaActions';
import Form from 'components/Form/Form';
import FormBuilder, { basePropTypes, schemaPropType } from 'components/FormBuilder/FormBuilder';

class FormBuilderLoader extends Component {

  constructor(props) {
    super(props);

    this.handleSubmit = this.handleSubmit.bind(this);

    this.state = {
      fetching: false,
    };
  }

  componentDidMount() {
    this.fetch();
  }

  componentDidUpdate(prevProps) {
    if (this.props.schemaUrl !== prevProps.schemaUrl) {
      this.fetch();
    }
  }

  componentWillUnmount() {
    // we will reload the schema any when we mount again, this is here so that redux-form doesn't
    // preload previous data mistakenly. (since it only accepts initialised values)
    reduxDestroyForm(this.props.form);
    if (this.props.form) {
      this.props.schemaActions.destroySchema(this.props.form);
    }
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

  /**
   * Handles updating the schema after response is received and gathering server-side validation
   * messages.
   *
   * @param dataWithAction
   * @param action
   * @param submitFn
   * @returns {Promise}
   */
  handleSubmit(data, action, submitFn) {
    let promise = null;
    if (typeof this.props.handleSubmit === 'function') {
      promise = this.props.handleSubmit(data, action, submitFn);
    } else {
      promise = submitFn();
    }

    if (promise) {
      promise
        .then(formSchema => {
          this.props.schemaActions.setSchema(formSchema);
          return formSchema;
        })
        // TODO Suggest storing messages in a separate redux store rather than throw an error
        // ref: https://github.com/erikras/redux-form/issues/94#issuecomment-143398399
        .then(formSchema => {
          if (!formSchema.state) {
            return formSchema;
          }
          const messages = this.getMessages(formSchema.state);

          if (Object.keys(messages).length) {
            throw new SubmissionError(messages);
          }
          return formSchema;
        });
    } else {
      throw new Error('Promise was not returned for submitting');
    }

    return promise;
  }

  /**
   * Fetches data used to generate a form. This can be form schema and or form state data.
   * When the response comes back the data is saved to state.
   *
   * @param {Boolean} schema If form schema data should be returned in the response.
   * @param {Boolean} state If form state data should be returned in the response.
   * @return {Object} Promise from the AJAX request.
   */
  fetch(schema = true, state = true) {
    const headerValues = [];

    if (schema === true) {
      headerValues.push('schema');
    }

    if (state === true) {
      headerValues.push('state');
    }

    this.setState({ fetching: true });

    return fetch(this.props.schemaUrl, {
      headers: { 'X-FormSchema-Request': headerValues.join() },
      credentials: 'same-origin',
    })
      .then(response => response.json())
      .then(formSchema => {
        if (typeof formSchema.id !== 'undefined') {
          this.props.schemaActions.setSchema(formSchema);
        }
        this.setState({ fetching: false });
        return formSchema;
      });
  }

  render() {
    // If the response from fetching the initial data
    // hasn't come back yet, don't render anything.
    if (!this.props.schema || this.state.fetching) {
      return null;
    }

    const props = Object.assign({}, this.props, {
      onSubmitSuccess: this.props.onSubmitSuccess,
      handleSubmit: this.handleSubmit,
    });
    return <FormBuilder {...props} />;
  }
}

FormBuilderLoader.propTypes = Object.assign({}, basePropTypes, {
  schemaActions: PropTypes.object.isRequired,
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

export default connect(
  (state, ownProps) => {
    const schema = state.schemas[ownProps.schemaUrl];
    const form = schema && schema.id;
    const reduxFormState = state.form
      && state.form[ownProps.schemaUrl];
    const submitting = reduxFormState
      && reduxFormState.submitting;
    const values = reduxFormState
      && reduxFormState.values;

    return { schema, form, submitting, values };
  },
  (dispatch) => ({
    schemaActions: bindActionCreators(schemaActions, dispatch),
  })
)(FormBuilderLoader);
