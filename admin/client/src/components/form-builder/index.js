import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as schemaActions from 'state/schema/actions';
import SilverStripeComponent from 'silverstripe-component';
import FormComponent from 'components/form/index';
import TextField from 'components/text-field/index';
import HiddenField from 'components/hidden-field/index';
import GridField from 'components/grid-field/index';
import fetch from 'isomorphic-fetch';
import deepFreeze from 'deep-freeze';

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
      case 'String':
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
    this.mapFieldsToComponents = this.mapFieldsToComponents.bind(this);
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
  fetch(schema = true, state = false) {
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
        // TODO See "Enable once <CampaignAdmin> ..." below
        this.setState({ isFetching: false });
        this.props.actions.setSchema(json);
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
      const props = deepFreeze(field);

      // Provides container components a place to hook in
      // and apply customisations to scaffolded components.
      if (typeof createFn === 'function') {
        return createFn(Component, props);
      }

      return <Component key={i} {...props} />;
    });
  }

  render() {
    const formSchema = this.props.schemas[this.props.schemaUrl];

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

    const formProps = {
      actions: formSchema.schema.actions,
      attributes,
      data: formSchema.schema.data,
      fields: formSchema.schema.fields,
      mapFieldsToComponents: this.mapFieldsToComponents,
    };

    return <FormComponent {...formProps} />;
  }
}

FormBuilderComponent.propTypes = {
  actions: React.PropTypes.object.isRequired,
  createFn: React.PropTypes.func,
  schemaUrl: React.PropTypes.string.isRequired,
  schemas: React.PropTypes.object.isRequired,
};

function mapStateToProps(state) {
  return {
    schemas: state.schemas,
  };
}

function mapDispatchToProps(dispatch) {
  return {
    actions: bindActionCreators(schemaActions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(FormBuilderComponent);
