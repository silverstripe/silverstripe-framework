import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class HeaderField extends SilverStripeComponent {

  render() {
    const Heading = `h${this.props.data.headingLevel}`;

    return (
      <div className="field">
        <Heading {...this.getInputProps()} >{this.props.data.title}</Heading>
      </div>
    );
  }

  getInputProps() {
    return {
      className: [this.props.extraClass].join(' '),
      id: this.props.id,
    };
  }
}

HeaderField.propTypes = {
  extraClass: React.PropTypes.string,
  id: React.PropTypes.string,
  data: React.PropTypes.oneOfType([
    React.PropTypes.array,
    React.PropTypes.shape({
      headingLevel: React.PropTypes.number.isRequired,
      title: React.PropTypes.string.isRequired,
    }),
  ]).isRequired,
};

export default HeaderField;
