import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';

/**
 * Renders the right-hand collapsable change preview panel
 */
class CampaignAdminPreview extends SilverStripeComponent {

  render() {
    return (
      <div className="pages-preview">
        <iframe src={this.props.previewUrl}></iframe>
      </div>
    );
  }
}

export default CampaignAdminPreview;
