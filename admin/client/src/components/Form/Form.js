import React, { PropTypes } from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import FormAlert from 'components/FormAlert/FormAlert';

class Form extends SilverStripeComponent {

  /**
   * Generates a list of messages if any are available
   *
   * @returns {Array|null}
   */
  renderMessages() {
    if (Array.isArray(this.props.messages)) {
      return this.props.messages.map((message, index) => (
        <FormAlert
          key={index}
          className={!index ? 'message-box--panel-top' : ''}
          {...message}
        />
      ));
    }
    return null;
  }

  render() {
    const valid = this.props.valid !== false;
    const fields = this.props.mapFieldsToComponents(this.props.fields);
    const actions = this.props.mapActionsToComponents(this.props.actions);
    const messages = this.renderMessages();

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
        onSubmit: this.props.handleSubmit,
        className: className.join(' '),
      }
    );

    return (
      <form {...formProps}>
        {messages}

        {this.props.afterMessages}
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

}

Form.propTypes = {
  actions: PropTypes.array,
  afterMessages: PropTypes.node,
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
  messages: PropTypes.arrayOf(PropTypes.shape({
    extraClass: PropTypes.string,
    value: PropTypes.any,
    type: PropTypes.string,
  })),
};

export default Form;
