import React, { PropTypes } from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import ShortCodeParser from 'lib/ShortCodeParser';

class TinyMCEInsertMediaPlugin extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.checkPlugin = this.checkPlugin.bind(this);
    this.initPlugin = this.initPlugin.bind(this);
    this.getEditor = this.getEditor.bind(this);

    this.checkPlugin();

    this.state = {
      loaded: false,
      show: false,
    };
  }

  checkPlugin() {

  }

  getModal() {
    return window.InsertMediaModal;
  }

  getEditor() {
    return window.tinymce.EditorManager.get(this.props.editorId);
  }

  handleSubmit(...data) {
    this.setState({ show: false });

    if (typeof this.props.onInsert === 'function') {
      this.props.onInsert(...data);
    }
  }

  render() {
    const InsertMediaModal = this.getModal();
    if (!InsertMediaModal || !this.state.loaded) {
      return null;
    }
    return <InsertMediaModal show={this.state.show} onSubmit={this.handleSubmit} />;
  }

  initPlugin() {
    const editor = this.getEditor();
    const DomParser = new DOMParser();

    editor.addButton('ssmedia', {
      icon: 'image',
      title: 'Insert Media',
      cmd: 'ssmedia',
    });
    editor.addMenuItem('ssmedia', {
      icon: 'image',
      text: 'Insert Media',
      cmd: 'ssmedia',
    });

    editor.addCommand('ssmedia', () => {
      this.setState({ show: true });
    });

    // Replace the mceAdvImage and mceImage commands with the ssmedia command
    editor.on('BeforeExecCommand', (event) => {
      const command = event.command;
      const ui = event.ui;
      const value = event.value;
      if (command === 'mceAdvImage' || command === 'mceImage') {
        event.preventDefault();
        editor.execCommand('ssmedia', ui, value);
      }
    });

    editor.on('SaveContent', (event) => {
      let doc = DomParser.parseFromString(event.content, 'text/html');
      // Transform [embed] shortcodes
      doc = ShortCodeParser.elementToCode(doc, 'embed');

      // Transform [image] shortcodes
      doc = ShortCodeParser.elementToCode(doc, 'image');

      // Insert outerHTML in order to retain all nodes including <script>.
      // Note that <script> tags might be sanitized separately based on editor config.
      let content = '';
      doc.body.childNodes.forEach((element) => {
        if (element.outerHTML !== undefined) {
          content += element.outerHTML;
        }
      });

      // eslint-disable-next-line no-param-reassign
      event.content = content;
    });

    editor.on('BeforeSetContent', (event) => {
      let html = event.content;

      // Transform [embed] shortcodes
      html = ShortCodeParser.codeToHtml(html, 'embed');

      // Transform [image] shortcodes
      html = ShortCodeParser.codeToHtml(html, 'image');

      // eslint-disable-next-line no-param-reassign
      event.content = html;
    });
  }
}

TinyMCEInsertMediaPlugin.propTypes = {
  onInsert: PropTypes.func.isRequired,
  editorId: PropTypes.string.isRequired,
};

export default TinyMCEInsertMediaPlugin;
