import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

function fieldHolder(Field) {
  class FieldHolder extends SilverStripeComponent {

    render() {
      const labelText = this.props.leftTitle !== null
        ? this.props.leftTitle
        : this.props.title;

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
              <Field {...this.props} />
          </div>
        </div>
      );
    }

  }

  FieldHolder.propTypes = {
    leftTitle: React.PropTypes.string,
    title: React.PropTypes.string,
    extraClass: React.PropTypes.string,
    id: React.PropTypes.string,
  };

  return FieldHolder;
}

export default fieldHolder;
