import React, { PropTypes } from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import MessageBox from 'components/MessageBox/MessageBox';

class Form extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleSubmit = this.handleSubmit.bind(this);
  }

  /**
   * Generates a list of messages if any are available
   *
   * @returns {Array|null}
   */
  getMessage() {
    if (this.props.message) {
      return (
        <MessageBox
          className="message-box--panel-top"
          closeLabel="close"
          onDismiss={this.props.onHideMessage}
          {...this.props.message}
        />
      );
    }
    return null;
  }

  render() {
    const valid = this.props.valid !== false;
    const fields = this.props.mapFieldsToComponents(this.props.fields);
    const actions = this.props.mapActionsToComponents(this.props.actions);
    const message = this.getMessage();

    const className = ['form'];
    if (valid === false) {
      className.push('form--invalid');
    }
    if (this.props.attributes && this.props.attributes.className) {
      className.push(this.props.attributes.className);
    }
    const formProps = Object.assign(
      {},
      this.props.attributes,
      {
        onSubmit: this.handleSubmit,
        className: className.join(' '),
      }
    );

    return (
      <form {...formProps}>
        {message}

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
    if (typeof this.props.handleSubmit === 'function') {
      this.props.handleSubmit(event);
    }
  }

}

Form.propTypes = {
  actions: PropTypes.array,
  attributes: PropTypes.shape({
    action: PropTypes.string.isRequired,
    className: PropTypes.string,
    encType: PropTypes.string,
    id: PropTypes.string,
    method: PropTypes.string.isRequired,
  }),
  fields: PropTypes.array.isRequired,
  handleSubmit: PropTypes.func,
  mapActionsToComponents: PropTypes.func.isRequired,
  mapFieldsToComponents: PropTypes.func.isRequired,
  message: PropTypes.shape({
    extraClass: PropTypes.string,
    value: PropTypes.any,
    type: PropTypes.string,
  }),
};

export default Form;
