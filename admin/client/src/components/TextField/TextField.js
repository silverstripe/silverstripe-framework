import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class TextField extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
  }

  render() {
    const labelText = this.props.leftTitle !== null
      ? this.props.leftTitle
      : this.props.title;

    return (
      <div className="field text">
        {labelText &&
          <label className="left" htmlFor={`gallery_${this.props.name}`}>
            {labelText}
          </label>
        }
        <div className="middleColumn">
          <input {...this.getInputProps()} />
        </div>
      </div>
    );
  }

  getInputProps() {
    return {
      className: ['text', this.props.extraClass].join(' '),
      id: `gallery_${this.props.name}`,
      name: this.props.name,
      onChange: this.handleChange,
      type: 'text',
      value: this.props.value,
    };
  }

  /**
   * Handles changes to the text field's value.
   *
   * @param object event
   */
  handleChange(event) {
    if (typeof this.props.handleFieldUpdate === 'undefined') {
      return;
    }

    this.props.handleFieldUpdate(event, { id: this.props.id, value: event.target.value });
  }
}

TextField.propTypes = {
  leftTitle: React.PropTypes.string,
  extraClass: React.PropTypes.string,
  name: React.PropTypes.string.isRequired,
  handleFieldUpdate: React.PropTypes.func,
  value: React.PropTypes.string,
};

export default TextField;
