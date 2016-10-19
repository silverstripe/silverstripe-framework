import React from 'react';
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
  getMessages() {
    if (Array.isArray(this.props.messages)) {
      return this.props.messages.map((message, index) => (
        <MessageBox
          key={index}
          className={!index ? 'message-box--panel-top' : ''}
          closeLabel="close"
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
    const messages = this.getMessages();

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
        {messages}

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
  actions: React.PropTypes.array,
  attributes: React.PropTypes.shape({
    action: React.PropTypes.string.isRequired,
    className: React.PropTypes.string,
    encType: React.PropTypes.string,
    id: React.PropTypes.string,
    method: React.PropTypes.string.isRequired,
  }),
  fields: React.PropTypes.array.isRequired,
  handleSubmit: React.PropTypes.func,
  mapActionsToComponents: React.PropTypes.func.isRequired,
  mapFieldsToComponents: React.PropTypes.func.isRequired,
};

export default Form;
