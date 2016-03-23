import React from 'react';
import SilverStripeComponent from '../../SilverStripeComponent';
import FormActionComponent from '../form-action';

class FormComponent extends SilverStripeComponent {

    /**
     * Gets the components responsible for perfoming actions on the form.
     * For example form submission.
     *
     * @return array|null
     */
    getFormActionComponents() {
        return this.props.actions.map((action) => {
            return <FormActionComponent {...action} />;
        });
    }

    render() {
        const attr = this.props.attributes;
        const fields = this.props.mapFieldsToComponents(this.props.fields);
        const actions = this.getFormActionComponents();

        return (
            <form id={attr.id} className={attr.className} encType={attr.enctype} method={attr.method} action={attr.action}>
                {fields &&
                    <fieldset className='form-group'>
                        {fields}
                    </fieldset>
                }

                {actions &&
                    <div className='actions-fix-btm'>
                        <div className='btn-group' role='group'>
                            {actions}
                        </div>
                    </div>
                }
            </form>
        );
    }

}

FormComponent.propTypes = {
    actions: React.PropTypes.array,
    attributes: React.PropTypes.shape({
        action: React.PropTypes.string.isRequired,
        'class': React.PropTypes.string.isRequired,
        enctype: React.PropTypes.string.isRequired,
        id: React.PropTypes.string.isRequired,
        method: React.PropTypes.string.isRequired
    }),
    data: React.PropTypes.array,
    fields: React.PropTypes.array.isRequired,
    mapFieldsToComponents: React.PropTypes.func.isRequired
};

export default FormComponent;
