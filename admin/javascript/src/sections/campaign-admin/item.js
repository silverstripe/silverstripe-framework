import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

/**
 * Describes an individual campaign item
 */
class CampaignItem extends SilverStripeComponent {
  render() {
    let thumbnail = '';
    let badge = '';
    const item = this.props.item;

    // change badge
    switch (item.ChangeType) {
      case 'created':
        badge = <span className="label label-warning">Draft</span>;
        break;
      case 'modified':
        badge = <span className="label label-warning">Modified</span>;
        break;
      case 'deleted':
        badge = <span className="label label-error">Removed</span>;
        break;
      case 'none':
      default:
        badge = <span className="label label-success item_visible-hovered">Already published</span>;
        break;
    }

    // Linked items
    let links = <span className="list-group-item__linked item_visible-hovered">[lk] 3 links</span>;

    // Thumbnail
    if (item.Thumbnail) {
      thumbnail = <span className="item__thumbnail"><img src={item.Thumbnail} /></span>;
    }


    return (
      <div>
        {thumbnail}
        <h4 className="list-group-item-heading">{item.Title}</h4>
        {links}
        {badge}
      </div>
    );
  }
}
export default CampaignItem;
