import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class HiddenField extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
  }

  render() {
    return (
      <div className="field hidden">
        <input {...this.getInputProps()} />
      </div>
    );
  }

  getInputProps() {
    return {
      className: ['hidden', this.props.extraClass].join(' '),
      id: this.props.id,
      name: this.props.name,
      onChange: this.props.onChange,
      type: 'hidden',
      value: this.props.value,
    };
  }

  handleChange() {
    if (typeof this.props.onChange === 'undefined') {
      return;
    }

    this.props.onChange();
  }
}

HiddenField.propTypes = {
  label: React.PropTypes.string,
  extraClass: React.PropTypes.string,
  name: React.PropTypes.string.isRequired,
  onChange: React.PropTypes.func,
  value: React.PropTypes.any,
};

export default HiddenField;
