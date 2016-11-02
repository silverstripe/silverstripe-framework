import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import { FormGroup, ControlLabel } from 'react-bootstrap-ss';
import castStringToElement from 'lib/castStringToElement';
import FormAlert from 'components/FormAlert/FormAlert';

function fieldHolder(Field) {
  class FieldHolder extends SilverStripeComponent {

    /**
     * Build description
     *
     * @returns {Component}
     */
    renderDescription() {
      if (this.props.description === null) {
        return null;
      }

      return castStringToElement(
        'div',
        this.props.description,
        { className: 'form__field-description' }
      );
    }

    /**
     * Build a FormAlert
     *
     * @returns {Component}
     */
    renderMessage() {
      const meta = this.props.meta;
      const message = (meta) ? meta.error : null;

      if (!message || (meta && !meta.touched)) {
        return null;
      }

      return (
        <FormAlert className="form__field-message" {...message} />
      );
    }

    /**
     * Build title label
     *
     * @returns {Component}
     */
    renderLeftTitle() {
      const labelText = this.props.leftTitle !== null
        ? this.props.leftTitle
        : this.props.title;

      if (!labelText || this.props.hideLabels) {
        return null;
      }

      return castStringToElement(
        ControlLabel,
        labelText,
        { className: 'form__field-label' }
      );
    }

    /**
     * Build title label
     *
     * @returns {Component}
     */
    renderRightTitle() {
      if (!this.props.rightTitle || this.props.hideLabels) {
        return null;
      }

      return castStringToElement(
        ControlLabel,
        this.props.rightTitle,
        { className: 'form__field-label' }
      );
    }

    /**
     * Generates the properties for the field holder
     *
     * @returns {object}
     */
    getHolderProps() {
      // The extraClass property is defined on both the holder and element
      // for legacy reasons (same behaviour as PHP rendering)
      const classNames = [
        'field',
        this.props.extraClass,
      ];
      if (this.props.readOnly) {
        classNames.push('readonly');
      }

      return {
        bsClass: this.props.bsClass,
        bsSize: this.props.bsSize,
        validationState: this.props.validationState,
        className: classNames.join(' '),
        controlId: this.props.id,
        id: this.props.holderId,
      };
    }

    render() {
      return (
        <FormGroup {...this.getHolderProps()}>
          {this.renderLeftTitle()}
          <div className="form__field-holder">
            <Field {...this.props} />
            {this.renderMessage()}
            {this.renderDescription()}
          </div>
          {this.renderRightTitle()}
        </FormGroup>
      );
    }

  }

  FieldHolder.propTypes = {
    leftTitle: React.PropTypes.any,
    rightTitle: React.PropTypes.any,
    title: React.PropTypes.any,
    extraClass: React.PropTypes.string,
    holderId: React.PropTypes.string,
    id: React.PropTypes.string,
    description: React.PropTypes.any,
    hideLabels: React.PropTypes.bool,
    message: React.PropTypes.shape({
      extraClass: React.PropTypes.string,
      value: React.PropTypes.any,
      type: React.PropTypes.string,
    }),
  };

  FieldHolder.defaultProps = {
    className: '',
    extraClass: '',
    leftTitle: null,
    rightTitle: null,
  };

  return FieldHolder;
}

export default fieldHolder;
