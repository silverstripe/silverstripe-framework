import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class LiteralField extends SilverStripeComponent {
  getContent() {
    return { __html: this.props.data.content };
  }

  render() {
    return (
      <div id={this.props.id} dangerouslySetInnerHTML={this.getContent()}></div>
    );
  }
}

LiteralField.propTypes = {
  id: React.PropTypes.string,
  data: React.PropTypes.oneOfType([
    React.PropTypes.array,
    React.PropTypes.shape({
      content: React.PropTypes.string.isRequired,
    }),
  ]).isRequired,
};

export default LiteralField;
