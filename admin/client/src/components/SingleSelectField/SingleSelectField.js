import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import i18n from 'i18n';

class SingleSelectField extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
  }

  render() {
    let field = null;
    if (this.props.readOnly) {
      field = this.getReadonlyField();
    } else {
      field = this.getSelectField();
    }

    return field;
  }

  /**
   * Builds the select field in readonly mode with current props
   *
   * @returns ReactComponent
   */
  getReadonlyField() {
    let label = this.props.source
      && this.props.source.find((item) => item.value === this.props.value);

    label = typeof label === 'string'
      ? label
      : this.props.value;

    return <div><i>{label}</i></div>;
  }

  /**
   * Builds the select field with current props
   *
   * @returns ReactComponent
   */
  getSelectField() {
    const options = this.props.source || [];

    if (this.props.data.hasEmptyDefault && !options.find((item) => !item.value)) {
      options.unshift({
        value: '',
        title: this.props.data.emptyString,
        disabled: false,
      });
    }
    return (
      <select {...this.getInputProps()}>
        { options.map((item, index) => {
          const key = `${this.props.name}-${item.value || `empty${index}`}`;

          return (
            <option key={key} value={item.value} disabled={item.disabled}>
              {item.title}
            </option>
          );
        }) }
      </select>
    );
  }

  /**
   * Fetches the properties for the select field
   *
   * @returns Object properties
   */
  getInputProps() {
    return {
      // The extraClass property is defined on both the holder and element
      // for legacy reasons (same behaviour as PHP rendering)
      className: ['form-control', this.props.extraClass].join(' '),
      id: this.props.id,
      name: this.props.name,
      onChange: this.handleChange,
      value: this.props.value,
    };
  }

  /**
   * Handles changes to the select field's value.
   *
   * @param Object event
   */
  handleChange(event) {
    if (typeof this.props.onChange === 'function') {
      this.props.onChange(event, { id: this.props.id, value: event.target.value });
    }
  }
}

SingleSelectField.propTypes = {
  id: React.PropTypes.string,
  name: React.PropTypes.string.isRequired,
  onChange: React.PropTypes.func,
  value: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.number]),
  readOnly: React.PropTypes.bool,
  source: React.PropTypes.arrayOf(React.PropTypes.shape({
    value: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.number]),
    title: React.PropTypes.any,
    disabled: React.PropTypes.bool,
  })),
  data: React.PropTypes.oneOfType([
    React.PropTypes.array,
    React.PropTypes.shape({
      hasEmptyDefault: React.PropTypes.bool,
      emptyString: React.PropTypes.string,
    }),
  ]),
};

SingleSelectField.defaultProps = {
  source: [],
  data: {
    emptyString: i18n._t('Boolean.ANY', 'Any'),
  },
};

export default fieldHolder(SingleSelectField);
