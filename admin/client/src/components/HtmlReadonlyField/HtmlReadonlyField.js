import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class HtmlReadonlyField extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.getContent = this.getContent.bind(this);
  }

  getContent() {
    return { __html: this.props.value };
  }

  render() {
    const labelText = this.props.leftTitle !== null
      ? this.props.leftTitle
      : this.props.title;

    const field = <div><i dangerouslySetInnerHTML={this.getContent()}></i></div>;

    // The extraClass property is defined on both the holder and element
    // for legacy reasons (same behaviour as PHP rendering)
    const classNames = ['form-group', this.props.extraClass].join(' ');

    return (
      <div className={classNames}>
        {labelText &&
        <label className="form__field-label" htmlFor={`${this.props.id}`}>
          {labelText}
        </label>
        }
        <div className="form__field-holder">
          {field}
        </div>
      </div>
    );
  }

}

HtmlReadonlyField.propTypes = {
  leftTitle: React.PropTypes.string,
  title: React.PropTypes.string,
  extraClass: React.PropTypes.string,
  id: React.PropTypes.string,
  value: React.PropTypes.string,
};

export default HtmlReadonlyField;
