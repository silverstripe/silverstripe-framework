import React, { PropTypes, Component } from 'react';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import ReactTinyMCE from 'react-tinymce';
import tinymceLoader from './tinymceLoader';
import insertMediaPlugin from './insertMediaPlugin';

class HtmlEditorField extends Component {

  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
    this.getEditor = this.getEditor.bind(this);
  }

  getEditor() {
    return window.tinymce.EditorManager.get(this.props.id);
  }

  render() {
    return (
      <ReactTinyMCE
        id={this.props.id}
        name={this.props.name}
        content={this.props.value}
        config={this.props.data.config}
        onChange={this.handleChange}
        onUndo={this.handleChange}
        onRedo={this.handleChange}
      />
    );
  }

  /**
   * Handles changes to the text field's value.
   *
   * @param {Event} event
   */
  handleChange(event) {
    const value = event.target.getContent({ format: 'raw' });
    if (typeof this.props.onChange === 'function') {
      this.props.onChange(value);
    }
  }
}

HtmlEditorField.propTypes = {
  extraClass: PropTypes.string,
  id: PropTypes.string.isRequired,
  name: PropTypes.string.isRequired,
  onChange: PropTypes.func,
  value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  readOnly: PropTypes.bool,
  disabled: PropTypes.bool,
  placeholder: PropTypes.string,
  data: PropTypes.shape({
    config: PropTypes.object,
  }),
};

HtmlEditorField.defaultProps = {
  // React considers "undefined" as an uncontrolled component.
  value: '',
  extraClass: '',
  className: '',
};

export { HtmlEditorField, insertMediaPlugin, tinymceLoader };

export default fieldHolder(tinymceLoader(insertMediaPlugin(HtmlEditorField)));
