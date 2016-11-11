import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import ReactTinyMCE from 'react-tinymce';
import TinyMCEInsertMediaPlugin from './TinyMCEInsertMediaPlugin';

/**
 * NOTE: Paste plugin doesn't not always work
 * https://github.com/tinymce/tinymce/issues/2728
 */

class HtmlEditorField extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
    this.checkLoadedLibrary = this.checkLoadedLibrary.bind(this);
    this.loadLibrary = this.loadLibrary.bind(this);
    this.getEditor = this.getEditor.bind(this);

    this.state = {
      loaded: false,
    };
    if (!window.tinymce) {
      this.loadLibrary();
    } else {
      this.state.loaded = true;
    }
  }

  getEditor() {
    return window.tinymce.EditorManager.get(this.props.id);
  }

  loadLibrary() {
    const id = 'react-tinymce-loader';
    if (!document.getElementById(id)) {
      // lazy loading TinyMCE this way (or easger loading through page template),
      // otherwise bundling TinyMCE causes more problems than it's worth.
      const script = document.createElement('script');
      script.type = 'text/javascript';
      script.id = 'react-tinymce-loader';
      script.async = true;
      script.src = `${this.props.data.config.baseURL}/tinymce.min.js`;
      document.getElementsByTagName('head')[0].appendChild(script);
    }
    this.checkLoadedLibrary();
  }

  checkLoadedLibrary() {
    if (window.tinymce) {
      this.setState({ loaded: true });
    } else {
      setTimeout(this.checkLoadedLibrary, 100);
    }
  }

  render() {
    if (!this.state.loaded) {
      return null;
    }

    return (
      <div className="htmleditor-wrapper">
        <ReactTinyMCE
          id={this.props.id}
          name={this.props.name}
          content={this.props.value}
          config={this.props.data.config}
          onChange={this.handleChange}
          onUndo={this.handleChange}
          onRedo={this.handleChange}
        />
        <TinyMCEInsertMediaPlugin editorId={this.props.id} />
      </div>
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
  extraClass: React.PropTypes.string,
  id: React.PropTypes.string.isRequired,
  name: React.PropTypes.string.isRequired,
  onChange: React.PropTypes.func,
  value: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.number]),
  readOnly: React.PropTypes.bool,
  disabled: React.PropTypes.bool,
  placeholder: React.PropTypes.string,
  type: React.PropTypes.string,
};

HtmlEditorField.defaultProps = {
  // React considers "undefined" as an uncontrolled component.
  value: '',
  extraClass: '',
  className: '',
};

export { HtmlEditorField };

export default fieldHolder(HtmlEditorField);
