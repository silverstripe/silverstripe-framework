import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class Form extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleSubmit = this.handleSubmit.bind(this);
  }

  componentWillUnmount() {
    if (typeof this.props.componentWillUnmount === 'undefined') {
      return;
    }

    this.props.componentWillUnmount(this.props.formId);
  }

  render() {
    const defaultFormProps = {
      className: 'form',
      onSubmit: this.handleSubmit,
    };
    const formProps = Object.assign({}, this.props.attributes, defaultFormProps);
    const fields = this.props.mapFieldsToComponents(this.props.fields);
    const actions = this.props.mapActionsToComponents(this.props.actions);

    return (
      <form {...formProps}>
        {fields &&
          <fieldset>
            {fields}
          </fieldset>
        }

        {actions &&
          <div className="btn-toolbar" role="group">
            {actions}
          </div>
        }
      </form>
    );
  }

  handleSubmit(event) {
    if (typeof this.props.handleSubmit === 'undefined') {
      return;
    }

    this.props.handleSubmit(event);
  }

}

Form.propTypes = {
  actions: React.PropTypes.array,
  attributes: React.PropTypes.shape({
    action: React.PropTypes.string.isRequired,
    className: React.PropTypes.string,
    encType: React.PropTypes.string,
    id: React.PropTypes.string,
    method: React.PropTypes.string.isRequired,
  }),
  componentWillUnmount: React.PropTypes.func,
  data: React.PropTypes.array,
  fields: React.PropTypes.array.isRequired,
  formId: React.PropTypes.string.isRequired,
  handleSubmit: React.PropTypes.func,
  mapActionsToComponents: React.PropTypes.func.isRequired,
  mapFieldsToComponents: React.PropTypes.func.isRequired,
};

export default Form;
