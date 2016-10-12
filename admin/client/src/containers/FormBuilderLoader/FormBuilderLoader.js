import React, { PropTypes, Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import fetch from 'isomorphic-fetch';
import { Field as ReduxFormField, reduxForm } from 'redux-form';
import * as schemaActions from 'state/schema/SchemaActions';
import Form from 'components/Form/Form';
import FormBuilder, { basePropTypes, schemaPropType } from 'components/FormBuilder/FormBuilder';

import es6promise from 'es6-promise';
es6promise.polyfill();

class FormBuilderLoader extends Component {

  constructor(props) {
    super(props);

    this.handleSubmitSuccess = this.handleSubmitSuccess.bind(this);
  }

  componentDidMount() {
    this.fetch();
  }

  componentDidUpdate(prevProps) {
    if (this.props.schemaUrl !== prevProps.schemaUrl) {
      this.fetch();
    }
  }

  handleSubmitSuccess(result) {
    this.props.schemaActions.setSchema(result);

    if (this.props.onSubmitSuccess) {
      this.props.onSubmitSuccess(result);
    }
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

    return fetch(this.props.schemaUrl, {
      headers: { 'X-FormSchema-Request': headerValues.join() },
      credentials: 'same-origin',
    })
      .then(response => response.json())
      .then(formSchema => {
        if (typeof formSchema.id !== 'undefined') {
          this.props.schemaActions.setSchema(formSchema);
        }
      });
  }

  render() {
    // If the response from fetching the initial data
    // hasn't come back yet, don't render anything.
    if (!this.props.schema) {
      return null;
    }

    const props = Object.assign({}, this.props, {
      onSubmitSuccess: this.handleSubmitSuccess,
    });
    return <FormBuilder {...props} />;
  }
}

FormBuilderLoader.propTypes = Object.assign({}, basePropTypes, {
  schemaActions: PropTypes.object.isRequired,
  schemaUrl: PropTypes.string.isRequired,
  schema: schemaPropType,
  form: PropTypes.string,
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
    const form = schema ? schema.id : null;
    return { schema, form };
  },
  (dispatch) => ({
    schemaActions: bindActionCreators(schemaActions, dispatch),
  })
)(FormBuilderLoader);
