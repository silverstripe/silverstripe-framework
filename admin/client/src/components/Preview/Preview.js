import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

/**
 * Renders the right-hand collapsable change preview panel
 */
class Preview extends SilverStripeComponent {

  render() {
    // @todo - Multiple preview views with toggle slider
    let body = null;
    if (!this.props.previewUrl) {
      body = (
        <div className="preview__overlay">
          <h3 className="preview__overlay-text">There is no preview available for this item.</h3>
        </div>
      );
    } else if (this.props.previewType.indexOf('image/') === 0) {
      body = (
        <div className="preview__file-container panel-scrollable">
          <img alt={this.props.previewUrl} className="preview__file--fits-space" src={this.props.previewUrl} />
        </div>
      );
    } else {
      body = <iframe className="preview__iframe" src={this.props.previewUrl}></iframe>;
    }
    return (
      <div className="cms-content__right preview">
        {body}
        <a href="" className="cms-content__back-btn font-icon-left-open-big" />
        <div className="toolbar--south">
          <div className="btn-toolbar">
            <button className="btn btn-secondary btn-secondary font-icon-edit" type="button">
            </button>
          </div>
        </div>
      </div>
    );
  }
}

Preview.propTypes = {
  previewUrl: React.PropTypes.string.isRequired,
  previewType: React.PropTypes.string, // Mime type
};

export default Preview;
