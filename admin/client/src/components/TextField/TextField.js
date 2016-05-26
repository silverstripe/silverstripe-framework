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

    let field = null;
    if (this.props.readOnly) {
      field = <div><i>{this.props.value}</i></div>;
    } else {
      field = <input {...this.getInputProps()} />;
    }

    // The extraClass property is defined on both the holder and element
    // for legacy reasons (same behaviour as PHP rendering)
    const classNames = ['field', 'text', 'form-group', this.props.extraClass].join(' ');

    return (
      <div className={classNames}>
        {labelText &&
          <label className="left" htmlFor={`gallery_${this.props.name}`}>
            {labelText}
          </label>
        }
        {field}
      </div>
    );
  }

  getInputProps() {
    return {
      // The extraClass property is defined on both the holder and element
      // for legacy reasons (same behaviour as PHP rendering)
      className: ['text', 'form-control', this.props.extraClass].join(' '),
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
    if (typeof this.props.onChange === 'undefined') {
      return;
    }

    this.props.onChange(event, { id: this.props.id, value: event.target.value });
  }
}

TextField.propTypes = {
  leftTitle: React.PropTypes.string,
  extraClass: React.PropTypes.string,
  name: React.PropTypes.string.isRequired,
  onChange: React.PropTypes.func,
  value: React.PropTypes.string,
  readOnly: React.PropTypes.bool,
};

export default TextField;
