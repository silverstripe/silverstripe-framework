import React, { PropTypes, Component } from 'react';
import TinyMCEInsertMediaPlugin from './TinyMCEInsertMediaPlugin';

export default function tinymceLoader(EditorField) {
  class TinymcePluginLoader extends Component {
    constructor(props) {
      super(props);

      this.handleLoad = this.handleLoad.bind(this);
      this.handleFail = this.handleFail.bind(this);

      this.state = {
        loadedPlugins: [],
        failedPlugins: [],
      };
    }

    handleLoad(pluginKey) {
      const loadedPlugins = this.state.loadedPlugins
        .filter((plugin) => plugin !== pluginKey)
        .concat([pluginKey]);
      const failedPlugins = this.state.failedPlugins
        .filter((plugin) => plugin !== pluginKey);

      this.setState({
        loadedPlugins,
        failedPlugins,
      });
    }

    handleFail(pluginKey) {
      const loadedPlugins = this.state.loadedPlugins
        .filter((plugin) => plugin !== pluginKey);
      const failedPlugins = this.state.failedPlugins
        .filter((plugin) => plugin !== pluginKey)
        .concat([pluginKey]);

      this.setState({
        loadedPlugins,
        failedPlugins,
      });
    }

    render() {
      const count = this.state.loadedPlugins.length + this.state.failedPlugins.length;
      const showing = count === this.props.plugins.length;
      const pluginsList = this.props.plugins.map((Plugin, index) => (
        <Plugin
          key={index}
          showing={showing}
          id={this.props.id}
          onLoad={this.handleLoad}
          onFail={this.handleFail}
        />
      ));

      const config = Object.assign(
        {},
        this.props.data.config,
        {
          plugins: this.state.loadedPlugins.join(','),
          // TODO remove toolbar's row.replace when sslink has been implemented
          toolbar: this.props.data.config.toolbar.map((row) => (
            row.replace('sslink', 'link')
          )),
        }
      );
      const data = Object.assign({}, this.props.data, { config });

      return (
        <div className="html-editor-field__container">
          {showing &&
          <EditorField {...this.props} data={data} />
          }
          <div className="html-editor-field__plugins-list">
            {pluginsList}
          </div>
        </div>
      );
    }
  }

  TinymcePluginLoader.propTypes = {
    id: PropTypes.string.isRequired,
    data: PropTypes.shape({
      config: PropTypes.object,
    }),
    plugins: PropTypes.array,
  };

  TinymcePluginLoader.defaultProps = {
    plugins: [TinyMCEInsertMediaPlugin],
  };

  return TinymcePluginLoader;
}
