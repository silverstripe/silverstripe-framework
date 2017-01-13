import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import i18n from 'i18n';

/**
 * Describes an individual campaign item
 */
class CampaignAdminItem extends SilverStripeComponent {

  render() {
    let thumbnail = null;
    const badge = {};
    const item = this.props.item;
    const campaign = this.props.campaign;

    // @todo customise these status messages for already-published changesets

    // Change badge. If the campaign has been published,
    // don't apply a badge at all
    if (campaign.State === 'open') {
      switch (item.ChangeType) {
        case 'created':
          badge.className = 'label label-warning list-group-item__status';
          badge.Title = i18n._t('CampaignItem.DRAFT', 'Draft');
          break;
        case 'modified':
          badge.className = 'label label-warning list-group-item__status';
          badge.Title = i18n._t('CampaignItem.MODIFIED', 'Modified');
          break;
        case 'deleted':
          badge.className = 'label label-error list-group-item__status';
          badge.Title = i18n._t('CampaignItem.REMOVED', 'Removed');
          break;
        case 'none':
        default:
          badge.className = 'label label-success list-group-item__status';
          badge.Title = i18n._t('CampaignItem.NO_CHANGES', 'No changes');
          break;
      }
    }

    // Linked items
    let links = (
      <span
        className="list-group-item__info campaign-admin__item-links--has-links font-icon-link"
      >3 linked items</span>
    );

    // Thumbnail
    if (item.Thumbnail) {
      thumbnail = (
        <span className="list-group-item__thumbnail">
          <img alt={item.Title} src={item.Thumbnail} />
        </span>
      );
    }

    return (
      <div className="fill-height">
        {thumbnail}
        <h4 className="list-group-item-heading">{item.Title}</h4>
        <span
          className="list-group-item__info campaign-admin__item-links--is-linked font-icon-link"
        ></span>
        {links}
        {badge.className && badge.Title &&
          <span className={badge.className}>{badge.Title}</span>
        }
      </div>
    );
  }
}

CampaignAdminItem.propTypes = {
  campaign: React.PropTypes.object.isRequired,
  item: React.PropTypes.object.isRequired,
};

export default CampaignAdminItem;
