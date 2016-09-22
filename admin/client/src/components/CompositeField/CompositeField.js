import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class CompositeField extends SilverStripeComponent {
  getLegend() {
    return (
      this.props.data.tag === 'fieldset' &&
      this.props.data.legend &&
      <legend>{this.props.data.legend}</legend>
    );
  }

  render() {
    const legend = this.getLegend();
    const Tag = this.props.data.tag || 'div';

    return (
      <Tag className={this.props.extraClass}>
        {legend}
        {this.props.children}
      </Tag>
    );
  }
}

CompositeField.propTypes = {
  data: React.PropTypes.shape({
    tag: React.PropTypes.string,
    legend: React.PropTypes.string,
  }),
  extraClass: React.PropTypes.string,
};

export default CompositeField;
