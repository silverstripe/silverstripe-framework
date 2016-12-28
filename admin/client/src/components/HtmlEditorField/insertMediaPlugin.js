import React, { PropTypes, Component } from 'react';
import ShortCodeParser from 'lib/ShortCodeParser';

export default function insertMediaPlugin (EditorField) {
  let loaded = false;

  class InsertMediaPlugin extends Component {
    constructor(props) {
      super(props);

      this.checkPlugin = this.checkPlugin.bind(this);
      this.initPlugin = this.initPlugin.bind(this);

      this.state = {
        loaded: false,
        show: false,
      };
    }

    componentWillMount() {
      this.checkPlugin();
    }

    checkPlugin() {
      if (!loaded && this.getModal() && window.tinymce) {
        window.tinymce.PluginManager.add('ssmedia', (editor) => this.initPlugin(editor));
        loaded = true;
      }
      if (loaded) {
        this.setState({ loaded: true });
        return;
      }
      this.setState({ tries: this.state.tries + 1 });
      setTimeout(this.checkPlugin, 50);
    }

    getModal() {
      return window.InsertMediaModal.default;
    }

    handleSubmit(...data) {
      this.setState({ show: false });
    }

    render() {
      if (!this.state.loaded) {
        return null;
      }
      const InsertMediaModal = this.getModal();
      const config = Object.assign({},
        this.props.data.config,
        {
          plugins: `ssmedia,${this.props.data.config.plugins || ''}`
        }
      );
      const data = Object.assign({}, this.props.data, { config });

      return <div>
        <InsertMediaModal show={this.state.show} onSubmit={this.handleSubmit} />
        <EditorField {...this.props} data={data} />
      </div>;
    }

    initPlugin(editor) {
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

  InsertMediaPlugin.propTypes = {
    id: PropTypes.string.isRequired,
    data: PropTypes.shape({
      config: PropTypes.object,
    }),
  };

  return InsertMediaPlugin;
}
