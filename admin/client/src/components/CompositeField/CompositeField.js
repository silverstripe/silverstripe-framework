import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import castStringToElement from 'lib/castStringToElement';

class CompositeField extends SilverStripeComponent {
  /**
   * Builds the legend for a fieldset if it is defined
   *
   * @returns {Component}
   */
  getLegend() {
    if (this.props.data.tag === 'fieldset' && this.props.data.legend) {
      return castStringToElement(
        'legend',
        this.props.data.legend
      );
    }
    return null;
  }

  getClassName() {
    return `${this.props.className} ${this.props.extraClass}`;
  }

  render() {
    const legend = this.getLegend();
    const Tag = this.props.data.tag || 'div';
    const className = this.getClassName();

    return (
      <Tag className={className}>
        {legend}
        {this.props.children}
      </Tag>
    );
  }
}

CompositeField.propTypes = {
  data: React.PropTypes.oneOfType([
    React.PropTypes.array,
    React.PropTypes.shape({
      tag: React.PropTypes.string,
      legend: React.PropTypes.string,
    }),
  ]),
  extraClass: React.PropTypes.string,
};

CompositeField.defaultProps = {
  className: '',
  extraClass: '',
};

export default CompositeField;
