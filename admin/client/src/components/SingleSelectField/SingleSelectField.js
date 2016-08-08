import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

class SingleSelectField extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
  }

  render() {
    const labelText = this.props.leftTitle !== null
      ? this.props.leftTitle
      : this.props.title;

    let field = null;
    if (this.props.readOnly) {
      field = this.getReadonlyField;
    } else {
      field = this.getSelectField();
    }

    // The extraClass property is defined on both the holder and element
    // for legacy reasons (same behaviour as PHP rendering)
    const classNames = ['form-group', this.props.extraClass].join(' ');

    return (
      <div className={classNames}>
        {labelText &&
        <label className="form__field-label" htmlFor={`gallery_${this.props.name}`}>
          {labelText}
        </label>
        }
        <div className="form__field-holder">
          {field}
        </div>
      </div>
    );
  }

  /**
   * Builds the select field in readonly mode with current props
   *
   * @returns ReactComponent
   */
  getReadonlyField() {
    let label = this.props.source[this.props.value];

    label = label !== null
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
    const options = this.props.source.map((item) => {
      return Object.assign({},
        item,
        {disabled: this.props.data.disabled.indexOf(item.value) > -1}
      );
    });

    if (this.props.hasEmptyDefault) {
      options.unshift({
        value: '',
        title: this.props.emptyString
      });
    }
    return <select {...this.getInputProps()}>
      { options.map((item) => {
        const key = `${this.props.name}-${item.value || 'null'}`;

        return <option key={key} value={item.value} disabled={item.disabled}>{item.title}</option>;
      }) }
    </select>;
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
      id:        this.props.id,
      name:      this.props.name,
      onChange:  this.handleChange,
      value:     this.props.value,
    };
  }

  /**
   * Handles changes to the select field's value.
   *
   * @param Object event
   */
  handleChange(event) {
    if (typeof this.props.onChange === 'undefined') {
      return;
    }

    this.props.onChange(event, {id: this.props.id, value: event.target.value});
  }
}

SingleSelectField.propTypes = {
  leftTitle:       React.PropTypes.string,
  extraClass:      React.PropTypes.string,
  id:              React.PropTypes.string,
  name:            React.PropTypes.string.isRequired,
  onChange:        React.PropTypes.func,
  value:           React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.number]),
  readOnly:        React.PropTypes.bool,
  source:          React.PropTypes.arrayOf(React.PropTypes.shape({
    value: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.number]),
    title: React.PropTypes.string,
  })).isRequired,
  data:            React.PropTypes.shape({
    disabled: React.PropTypes.arrayOf(
      React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.number])
    ),
  }),
  hasEmptyDefault: React.PropTypes.bool,
  emptyString:     React.PropTypes.string,
};

SingleSelectField.defaultProps = {
  data: {
    disabled: [],
  },
  emptyString: '',
};

export default SingleSelectField;
