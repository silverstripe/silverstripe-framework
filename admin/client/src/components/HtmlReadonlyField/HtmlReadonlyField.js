import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import fieldHolder from 'components/FieldHolder/FieldHolder';

class HtmlReadonlyField extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.getContent = this.getContent.bind(this);
  }

  getContent() {
    return { __html: this.props.value };
  }

  render() {
    return <div><i dangerouslySetInnerHTML={this.getContent()}></i></div>;
  }

}

HtmlReadonlyField.propTypes = {
  value: React.PropTypes.string,
};

export default fieldHolder(HtmlReadonlyField);
