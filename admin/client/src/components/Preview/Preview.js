import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

/**
 * Renders the right-hand collapsable change preview panel
 */
class Preview extends SilverStripeComponent {

  render() {
    // @todo - Multiple preview views with toggle slider
    let body = null;
    if (this.props.previewUrl) {
      body = <iframe className="preview__iframe" src={this.props.previewUrl}></iframe>;
    } else {
      body = (
        <div className="preview__overlay">
          <h3 className="preview__overlay-text">There is no preview available for this item.</h3>
        </div>
      );
    }
    return (
      <div className="cms-content__right preview">
        {body}
        <div className="preview__file-container panel-scrollable">
          <img alt="placeholder" className="preview__file--fits-space" src="http://placehold.it/250x150" />
        </div>
        <a href="" className="cms-content__back-btn font-icon-left-open-big" />
        <div className="toolbar--south">
          <div className="btn-toolbar">
            <button className="btn btn-secondary btn-secondary" type="button">
              <i className="font-icon-edit" /> Edit
            </button>
            <button type="button" data-container="body" className="btn btn-link btn--options"
              data-toggle="popover" title="Page actions" data-placement="top"
              data-content="<a href=''>Add to campaign</a><a href=''>Remove from campaign</a>"
            ><i className="font-icon-dot-3" /></button>
          </div>
        </div>

      </div>

    );
  }
}

export default Preview;
