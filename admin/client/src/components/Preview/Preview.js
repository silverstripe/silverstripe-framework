import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

/**
 * Renders the right-hand collapsable change preview panel
 */
class Preview extends SilverStripeComponent {

  render() {
    return (
      <div className="cms-content__right preview">
        <iframe className="preview__iframe" src={this.props.previewUrl}></iframe>
        <div className="preview__overlay">
          <h3 className="preview__overlay-text">There is no preview available for this item.</h3>
        </div>
        <div className="preview__file-container panel-scrollable">
          <img className="preview__file--fits-space" src="http://placehold.it/250x150" />
        </div>
        <a href="" className="cms-content__back-btn font-icon-left-open-big"></a>
        <div className="toolbar--south">
          <div className="btn-toolbar">
            <button className="btn btn-secondary btn-secondary" type="button"><i className="font-icon-edit"></i> Edit</button>
            {/* More options button and popover for Alpha 2
              <a tabIndex="0" role="button" data-container="body" className="btn btn-link btn--options" title="" data-toggle="popover" data-trigger="focus" data-placement="top"
              data-content="<ul><li><a href=''>Add to campaign</a></li><li><a href=''>Remove from campaign</a></li></ul>">
              <i className="font-icon-dot-3"></i>
            </a> */}
          </div>
        </div>

      </div>

    );
  }
}

export default Preview;
