import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

/**
 * Renders the right-hand collapsable change preview panel
 */
class CampaignPreview extends SilverStripeComponent {

  render() {
    return (
      <div className="cms-content__right preview">
        <iframe className="preview__iframe" src={this.props.previewUrl}></iframe>
        <div className="preview__overlay">
          <h3 className="preview__overlay-text">There is no preview available for this item.</h3>
        </div>
        <div className="preview__file-container panel-scrollable">
          <img className="preview__file" src="http://placehold.it/250x150" />
        </div>
        <div className="south-actions">
          <div className="btn-toolbar">
            <button className="btn btn-secondary" type="button">Edit</button>
            <button type="button" data-container="body" className="btn btn-link" data-toggle="popover" title="Page actions" data-placement="top" data-content="<a href=''>Add to campaign</a><a href=''>Remove from campaign</a>">...</button>
          </div>
        </div>
      </div>

    );
  }
}

export default CampaignPreview;
