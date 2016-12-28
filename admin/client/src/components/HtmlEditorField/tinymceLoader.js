import React, { PropTypes, Component } from 'react';

/**
 * NOTE: Paste plugin doesn't not always work
 * https://github.com/tinymce/tinymce/issues/2728
 */

export default function tinymceLoader (EditorField) {
  let loaded = false;
  let called = false;

  class TinymceLoader extends Component {
    constructor(props) {
      super(props);

      this.checkLoadedLibrary = this.checkLoadedLibrary.bind(this);
      this.loadLibrary = this.loadLibrary.bind(this);

      this.state = {
        loaded: false,
      };
    }

    componentWillMount() {
      const config = this.props.data.config;
      this.loadLibrary(config && config.baseURL);
    }

    checkLoadedLibrary() {
      if (loaded || window.tinymce) {
        loaded = true;
        this.setState({
          loaded: true,
        });
      } else {
        setTimeout(this.checkLoadedLibrary, 100);
      }
    }

    loadLibrary(baseURL) {
      if (called) {
        return;
      }
      called = true;
      const id = 'react-tinymce-loader';
      let script = document.getElementById(id);
      if (!script && baseURL) {
        // lazy loading TinyMCE this way (or easger loading through page template),
        // otherwise bundling TinyMCE causes more problems than it's worth.
        script = document.createElement('script');
        script.type = 'text/javascript';
        script.id = 'react-tinymce-loader';
        script.async = true;
        script.src = `${baseURL}/tinymce.min.js`;
        document.getElementsByTagName('head')[0].appendChild(script);
      }
      this.checkLoadedLibrary();
    }

    render() {
      if (!loaded) {
        return null;
      }

      return <EditorField {...this.props} />;
    }
  }

  return TinymceLoader;
}
