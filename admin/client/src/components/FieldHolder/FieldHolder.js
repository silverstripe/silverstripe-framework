import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

function fieldHolder(Field) {
  class FieldHolder extends SilverStripeComponent {

    /**
     * Safely cast string to container element. Supports custom HTML values.
     *
     * See DBField::getSchemaValue()
     *
     * @param {*} value Form schema value
     * @param {String} Container Container type
     * @param {object} props container props
     * @returns {XML}
     */
    castStringToElement(value, Container, props) {
      // HTML value
      if (value && typeof value.html !== 'undefined') {
        const html = { __html: value.html };
        return <Container {...props} dangerouslySetInnerHTML={html} />;
      }

      // Plain value
      let body = null;
      if (value && typeof value.text !== 'undefined') {
        body = value.text;
      } else {
        body = value;
      }

      if (body && typeof body === 'object') {
        throw new Error(`Unsupported string value ${JSON.stringify(body)}`);
      }

      return <Container {...props}>{body}</Container>;
    }

    /**
     * Build description
     *
     * @returns {XML}
     */
    getDescription() {
      return this.castStringToElement(
        this.props.description,
        'div',
        { className: 'form__field-description' }
      );
    }

    /**
     * Build title label
     *
     * @returns {XML}
     */
    getTitle() {
      const labelText = this.props.leftTitle !== null
        ? this.props.leftTitle
        : this.props.title;

      if (!labelText) {
        return null;
      }

      return this.castStringToElement(
        labelText,
        'label',
        { className: 'form__field-label', htmlFor: this.props.id }
      );
    }

    render() {
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
          {this.getTitle()}
          <div className="form__field-holder">
              <Field {...this.props} />
          </div>
          {this.getDescription()}
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
    description: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.object]),
  };

  return FieldHolder;
}

export default fieldHolder;
