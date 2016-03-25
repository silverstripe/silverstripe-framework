import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import $ from 'jQuery';
import * as schemaActions from '../../state/schema/actions';
import SilverStripeComponent from '../../SilverStripeComponent';
import FormComponent from '../form';
import TextField from '../text-field';
import HiddenField from '../hidden-field';
import GridField from '../../components/grid-field';

// Using this to map field types to components until we implement dependency injection.
var fakeInjector = {

    /**
     * Components registered with the fake DI container.
     */
    components: {
        'TextField': TextField,
        'GridField': GridField,
        'HiddenField': HiddenField
    },

    /**
     * Gets the component matching the passed component name.
     * Used when a component type is provided bt the form schema.
     *
     * @param string componentName - The name of the component to get from the injector.
     *
     * @return object|null
     */
    getComponentByName: function (componentName) {
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
    getComponentByDataType: function (dataType) {
        switch (dataType) {
            case 'String':
                return this.components.TextField;
            case 'Hidden':
                return this.components.HiddenField;
            case 'Text':
                // Textarea field (not implemented)
                return null;
            case 'HTML':
                // HTML editor field (not implemented)
                return null;
            case 'Integer':
                // Numeric field (not implemented)
                return null;
            case 'Decimal':
                // Numeric field (not implemented)
                return null;
            case 'MultiSelect':
                // Radio field (not implemented)
                return null;
            case 'SingleSelect':
                // Dropdown field (not implemented)
                return null;
            case 'Date':
                // DateTime field (not implemented)
                return null;
            case 'DateTime':
                // DateTime field (not implemented)
                return null;
            case 'Time':
                // DateTime field (not implemented)
                return null;
            case 'Boolean':
                // Checkbox field (not implemented)
                return null;
            case 'Custom':
                return this.components.GridField;
            default:
                return null;
        }
    }
}

export class FormBuilderComponent extends SilverStripeComponent {

    constructor(props) {
        super(props);

        this.formSchemaPromise = null;
        this.isFetching = false;

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
        var headerValues = [];

        if (this.isFetching === true) {
            return this.formSchemaPromise;
        }

        if (schema === true) {
            headerValues.push('schema');
        }

        if (state === true) {
            headerValues.push('state');
        }

        this.formSchemaPromise = $.ajax({
            method: 'GET',
            headers: { 'X-FormSchema-Request': headerValues.join() },
            url: this.props.schemaUrl
        }).done((data, status, xhr) => {
            this.isFetching = false;
            this.props.actions.setSchema(data);
        });

        this.isFetching = true;

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
        return fields.map((field, i) => {

            const Component = field.component !== null
                ? fakeInjector.getComponentByName(field.component)
                : fakeInjector.getComponentByDataType(field.type);

            if (Component === null) {
                return null;
            }

            // Props which every form field receives.
            let props = {
                attributes: field.attributes,
                data: field.data,
                description: field.description,
                extraClass: field.extraClass,
                fields: field.children,
                id: field.id,
                name: field.name
            };

            // Structural fields (like TabSets) are not posted back to the server and don't receive some props.
            if (field.type !== 'Structural') {
                props.rightTitle = field.rightTitle;
                props.leftTitle = field.leftTitle;
                props.readOnly = field.readOnly;
                props.disabled = field.disabled;
                props.customValidationMessage = field.customValidationMessage;
            }

            // Dropdown and Radio fields need some options...
            if (field.type === 'MultiSelect' || field.type === 'SingleSelect') {
                props.source = field.source;
            }

            return <Component key={i} {...props} />
        });
    }

    render() {
        const schema = this.props.schemas[this.props.schemaUrl];

        // If the response from fetching the initial data
        // hasn't come back yet, don't render anything.
        if (!schema) {
            return null;
        }

        const formProps = {
            actions: schema.schema.actions,
            attributes: schema.schema.attributes,
            data: schema.schema.data,
            fields: schema.schema.fields,
            mapFieldsToComponents: this.mapFieldsToComponents
        };

        return <FormComponent {...formProps} />
    }
}

FormBuilderComponent.propTypes = {
    actions: React.PropTypes.object.isRequired,
    schemaUrl: React.PropTypes.string.isRequired,
    schemas: React.PropTypes.object.isRequired
};

function mapStateToProps(state) {
    return {
        schemas: state.schemas
    }
}

function mapDispatchToProps(dispatch) {
    return {
        actions: bindActionCreators(schemaActions, dispatch)
    }
}

export default connect(mapStateToProps, mapDispatchToProps)(FormBuilderComponent);
