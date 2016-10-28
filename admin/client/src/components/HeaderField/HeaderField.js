import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class HeaderField extends SilverStripeComponent {

  render() {
    const Heading = `h${this.props.data.headingLevel || 3}`;

    return (
      <div className="field">
        <Heading {...this.getInputProps()} >{this.props.data.title}</Heading>
      </div>
    );
  }

  /**
   * Fetches the properties for the field
   *
   * @returns {object} properties
   */
  getInputProps() {
    return {
      className: `${this.props.className} ${this.props.extraClass}`,
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
      headingLevel: React.PropTypes.number,
      title: React.PropTypes.string,
    }),
  ]).isRequired,
};

HeaderField.defaultProps = {
  className: '',
  extraClass: '',
};

export default HeaderField;
