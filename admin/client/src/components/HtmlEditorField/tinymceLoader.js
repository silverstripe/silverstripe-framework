import React, { PropTypes, Component } from 'react';

/**
 * NOTE: Paste plugin doesn't not always work
 * https://github.com/tinymce/tinymce/issues/2728
 */

export default function tinymceLoader(EditorField) {
  let loaded = false;
  let called = false;

  class TinymceLoader extends Component {
    constructor(props) {
      super(props);

      this.checkLoadedLibrary = this.checkLoadedLibrary.bind(this);
      this.loadLibrary = this.loadLibrary.bind(this);

      this.state = {
        loaded,
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
          loaded,
        });
      } else {
        setTimeout(this.checkLoadedLibrary, 100);
      }
    }

    loadLibrary(baseURL) {
      const id = 'react-tinymce-loader';
      let script = document.getElementById(id);
      if (!script && !called && baseURL) {
        called = true;
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
      if (!this.state.loaded) {
        return null;
      }

      return <EditorField {...this.props} />;
    }
  }

  TinymceLoader.propTypes = {
    data: PropTypes.shape({
      config: PropTypes.shape({
        baseURL: PropTypes.string,
      }),
    }),
  };

  return TinymceLoader;
}
