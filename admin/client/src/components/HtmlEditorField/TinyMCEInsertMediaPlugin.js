import React, { PropTypes, Component } from 'react';
import ShortCodeParser from 'lib/ShortCodeParser';

const pluginKey = 'ssmedia';
const MAX_TRIES = 10;
const DELAY_POLL = 100;
const NO_IMAGE_PLACEHOLDER = 'broken-image.png';
let loaded = false;

// This is needed since initPlugin can only be applied to PluginManager once
const cmdRegistry = {};

const registerCommand = (id, show) => {
  cmdRegistry[id] = show;
};

const unregisterCommand = (id) => delete cmdRegistry[id];

const initPlugin = (editor) => {
  const DomParser = new DOMParser();

  editor.addButton(pluginKey, {
    icon: 'image',
    title: 'Insert Media',
    cmd: pluginKey,
  });
  editor.addMenuItem(pluginKey, {
    icon: 'image',
    text: 'Insert Media',
    cmd: pluginKey,
  });

  editor.addCommand(pluginKey, () => {
    const show = cmdRegistry[editor.id];
    if (typeof show === 'function') {
      show();
    }
  });

  // Replace the mceAdvImage and mceImage commands with the ssmedia command
  editor.on('BeforeExecCommand', (event) => {
    const command = event.command;
    const ui = event.ui;
    const value = event.value;
    if (command === 'mceAdvImage' || command === 'mceImage') {
      event.preventDefault();
      editor.execCommand(pluginKey, ui, value);
    }
  });

  editor.on('SaveContent', (event) => {
    let doc = DomParser.parseFromString(event.content, 'text/html');
    // Transform [embed] shortcodes
    doc = ShortCodeParser.elementToCode(doc, 'embed');

    // Transform [image] shortcodes
    doc = ShortCodeParser.elementToCode(doc, 'image');

    /* Insert outerHTML in order to retain all nodes including <script>.
     * Note that <script> tags might be sanitized separately based on editor config.
     */
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
};

const getAttributes = (data) => {
  const attrs = {
    src: data.url || NO_IMAGE_PLACEHOLDER,
    alt: data.AltText,
    title: data.TitleTooltip,
    class: data.Alignment,
    'data-id': data.ID,
  };
  const width = parseInt(data.InsertWidth, 10);
  const height = parseInt(data.InsertHeight, 10);

  if (width) {
    attrs.width = width;
  }
  if (height) {
    attrs.height = height;
  }

  return attrs;
};

const getDefaultAttributes = (node) => {
  if (!node) {
    return {};
  }

  const parent = node.parentElement;
  const caption = parent && parent.querySelector('.caption');

  const attr = {
    url: node.getAttribute('src'),
    AltText: node.getAttribute('alt'),
    InsertWidth: node.getAttribute('width'),
    InsertHeight: node.getAttribute('height'),
    TitleTooltip: node.getAttribute('title'),
    Alignment: node.getAttribute('class'),
    Caption: caption && caption.textContent,
    ID: node.getAttribute('data-id'),
  };

  // parse certain attributes to integer value
  ['InsertWidth', 'InsertHeight', 'ID'].forEach((item) => {
    attr[item] = (typeof attr[item] === 'string') ? parseInt(attr[item], 10) : null;
    if (isNaN(attr[item])) {
      delete attr[item];
    }
  });

  return attr;
};

const insertImage = (data, editor, node) => {
  // Get the attributes & extra data
  const attrs = getAttributes(data);
  const captionText = data && data.Caption;

  let img = null;
  let replacee = null;
  let container = null;
  // Find the element we are replacing - either the img, it's wrapper parent, or nothing to create
  if (node instanceof HTMLImageElement) {
    // set the img and replacee as the selectednode
    replacee = img = node;
    // set the container and replacee as the parent if it has a caption container
    if (img && img.parentElement.classList.contains('captionImage')) {
      replacee = container = img.parentElement;
    }
  } else {
    // Create a new image if not an image
    img = document.createElement('img');
  }

  // set the new attributes
  Object.entries(attrs).forEach(([attr, value]) => img.setAttribute(attr, value));

  // If we've got caption text, we need a wrapping div.captionImage and sibling p.caption
  if (captionText) {
    // create a container if none found
    if (!container) {
      container = document.createElement('div');
    }

    // set container class and styles
    const classList = container.classList;
    classList.add('captionImage');
    classList.add(attrs.class);
    container.style.width = attrs.width;

    // Any existing figure or caption node
    let caption = container.querySelector('.caption');
    if (!caption) {
      caption = document.createElement('p');
      caption.classList.add('caption');

      container.appendChild(caption);
    }
    caption.classList.add(attrs.class).textContent = captionText;

    // If we have a wrapper element, make sure the img is the first child - img might be the
    // replacee, and the wrapper the replacer, and we can't do this until after the replace
    container.insertBefore(img, container.firstChild);
  } else {
    // Otherwise forget they exist
    container = null;
  }

  // The element we are replacing the replacee with
  const replacer = container || img;

  // If we're replacing something, and it's not with itself, do so
  if (replacee && replacee !== replacer) {
    replacee.parentElement.replaceChild(replacer, replacee);
  }

  // If we don't have a replacee, then we need to insert the whole HTML
  if (!replacee) {
    // Otherwise insert the whole HTML content
    // editor.repaint();
    editor.insertContent(replacer.outerHTML, { skip_undo: 1 });
  }
};

class TinyMCEInsertMediaPlugin extends Component {
  constructor(props) {
    super(props);

    this.checkPlugin = this.checkPlugin.bind(this);
    this.getEditor = this.getEditor.bind(this);
    this.handleSubmit = this.handleSubmit.bind(this);
    this.handleHide = this.handleHide.bind(this);
    this.handleShow = this.handleShow.bind(this);

    this.state = {
      loaded: false,
      show: false,
    };
  }

  componentWillMount() {
    registerCommand(this.props.id, this.handleShow);
    this.checkPlugin();
  }

  componentWillReceiveProps(props) {
    // register show for this id, and remove old registry
    if (this.props.id !== props.id) {
      unregisterCommand(this.props.id);
    }
    registerCommand(props.id, this.handleShow);
  }

  componentWillUnmount() {
    unregisterCommand(this.props.id);
  }

  getModal() {
    return window.InsertMediaModal.default;
  }

  getEditor() {
    return window.tinymce.EditorManager.get(this.props.id);
  }

  getSelectedNode() {
    const editor = this.getEditor();
    return editor && editor.selection && editor.selection.getNode();
  }

  checkPlugin() {
    if (!loaded && this.getModal() && window.tinymce) {
      loaded = true;
      window.tinymce.PluginManager.add(pluginKey, initPlugin);
    }
    if (loaded) {
      this.setState({ loaded });
      if (typeof this.props.onLoad === 'function') {
        this.props.onLoad(pluginKey);
      }
      return;
    }
    if (this.state.tries < MAX_TRIES) {
      this.setState({ tries: this.state.tries + 1 });
      setTimeout(this.checkPlugin, DELAY_POLL);
    } else if (typeof this.props.onFail === 'function') {
      this.props.onFail(pluginKey);
    }
  }

  handleSubmit(data, file) {
    const combinedData = Object.assign({}, data, file);
    const editor = this.getEditor();
    const node = this.getSelectedNode();

    switch (file.category) {
      case 'image': {
        insertImage(combinedData, editor, node);
        break;
      }
      default: {
        // no-op
        // insertFile(combinedData, editor, node);
      }
    }

    this.handleHide();
  }

  handleHide() {
    this.setState({ show: false });
  }

  handleShow() {
    this.setState({ show: true });
  }

  render() {
    if (!this.state.loaded) {
      return <div className="insert-media-plugin__placeholder" />;
    }
    const InsertMediaModal = this.getModal();
    const defaultAttrs = getDefaultAttributes(this.getSelectedNode());

    return (
      <InsertMediaModal
        title={false}
        show={this.state.show}
        onInsert={this.handleSubmit}
        onHide={this.handleHide}
        fileAttributes={defaultAttrs}
      />
    );
  }
}

TinyMCEInsertMediaPlugin.propTypes = {
  id: PropTypes.string.isRequired,
  onLoad: PropTypes.func,
  onFail: PropTypes.func,
};

export {
  registerCommand,
  unregisterCommand,
  initPlugin,
  getAttributes,
  getDefaultAttributes,
  insertImage,
};

export default TinyMCEInsertMediaPlugin;
