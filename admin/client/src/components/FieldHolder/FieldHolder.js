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
      const classNames = [
        'form-group field',
        this.props.extraClass,
      ];
      if (this.props.readOnly) {
        classNames.push('readonly');
      }

      return (
        <div className={classNames.join(' ')} id={this.props.holder_id}>
          {labelText &&
            <label className="form__field-label" htmlFor={this.props.id}>
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
    leftTitle: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.bool]),
    title: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.bool]),
    extraClass: React.PropTypes.string,
    holder_id: React.PropTypes.string,
    id: React.PropTypes.string,
  };

  return FieldHolder;
}

export default fieldHolder;
