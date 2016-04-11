import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as recordActions from 'state/records/actions';
import * as campaignActions from 'state/campaign/actions';
import SilverStripeComponent from 'silverstripe-component';
import Accordion from 'components/accordion/index';
import AccordionGroup from 'components/accordion/group';
import AccordionItem from 'components/accordion/item';
import NorthHeader from 'components/north-header/index';
import FormAction from 'components/form-action/index';
import CampaignItem from './item';
import CampaignPreview from './preview';
import i18n from 'i18n';

/**
 * Represents a campaign list view
 */
class CampaignListContainer extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handlePublish = this.handlePublish.bind(this);
  }

  componentDidMount() {
    const fetchURL = this.props.itemListViewEndpoint.replace(/:id/, this.props.campaignId);
    super.componentDidMount();
    this.props.recordActions.fetchRecord('ChangeSet', 'get', fetchURL);
  }

  /**
   * Renders a list of items in a Campaign.
   *
   * @return object
   */
  render() {
    const itemID = 1; // todo - hook up to "click" handler for changesetitems
    const campaignId = this.props.campaignId;

    // Trigger different layout when preview is enabled
    const previewUrl = this.previewURLForItem(itemID);
    const itemGroups = this.groupItemsForSet();
    const classNames = previewUrl ? 'cms-middle with-preview' : 'cms-middle no-preview';

    // Get items in this set
    let accordionGroups = [];

    Object.keys(itemGroups).forEach(className => {
      const group = itemGroups[className];
      const groupCount = group.items.length;

      let accordionItems = [];
      let title = `${groupCount} ${groupCount === 1 ? group.singular : group.plural}`;
      let groupid = `Set_${campaignId}_Group_${className}`;

      // Create items for this group
      group.items.forEach(item => {
        // Add extra css class for published items
        let itemClassName = '';

        if (item.ChangeType === 'none') {
          itemClassName = 'list-group-item--published';
        }

        accordionItems.push(
          <AccordionItem key={item.ID} className={itemClassName}>
            <CampaignItem item={item} />
          </AccordionItem>
        );
      });

      // Merge into group
      accordionGroups.push(
        <AccordionGroup key={groupid} groupid={groupid} title={title}>
          {accordionItems}
        </AccordionGroup>
      );
    });

    return (
      <div className={classNames}>
        <div className="cms-campaigns collapse in" aria-expanded="true">
          <NorthHeader />
          <div className="col-md-12 campaign-items">
            <Accordion>
              {accordionGroups}
            </Accordion>
          </div>
          <div className="cms-south-actions">
            {this.renderButtonToolbar()}
          </div>
        </div>
        { previewUrl && <CampaignPreview previewUrl={previewUrl} /> }
      </div>
    );
  }

  renderButtonToolbar() {
    const items = this.getItems(this.props.campaignId);

    let itemSummaryLabel;
    if (items) {
      itemSummaryLabel = i18n.sprintf(
       (items.length === 1) ?
         i18n._t('Campaigns.ITEM_SUMMARY_SINGULAR')
         : i18n._t('Campaigns.ITEM_SUMMARY_PLURAL'),
       items.length
     );

      let button;
      if (this.props.record.State === 'open') {
        button = (
          <FormAction
            label={i18n._t('Campaigns.PUBLISHCAMPAIGN')}
            style={'success'}
            handleClick={this.handlePublish}
          />
        );
      } else if (this.props.record.State === 'published') {
        // TODO Implement "revert" feature
        button = (
          <FormAction
            label={i18n._t('Campaigns.PUBLISHCAMPAIGN')}
            style={'success'}
            disabled
          />
        );
      }

      return (
        <div className="btn-toolbar">
          {button}
          <span className="text-muted">
            <span className="label label-warning label--empty">&nbsp;</span>
            &nbsp;{itemSummaryLabel}
          </span>
        </div>
      );
    }

    return <div className="btn-toolbar"></div>;
  }

  /**
   * Gets preview URL for itemid
   * @param int id
   * @returns string
   */
  previewURLForItem(id) {
    if (!id) {
      return '';
    }

    // hard code in baseurl for any itemid preview url
    return document.getElementsByTagName('base')[0].href;
  }

  /**
   * @return {Array}
   */
  getItems() {
    if (this.props.record && this.props.record._embedded) {
      return this.props.record._embedded.ChangeSetItems;
    }

    return null;
  }

  /**
   * Group items for changeset display
   *
   * @return array
   */
  groupItemsForSet() {
    const groups = {};
    const items = this.getItems();
    if (!items) {
      return groups;
    }

    // group by whatever
    items.forEach(item => {
      // Create new group if needed
      const classname = item.BaseClass;

      if (!groups[classname]) {
        groups[classname] = {
          singular: item.Singular,
          plural: item.Plural,
          items: [],
        };
      }

      // Push items
      groups[classname].items.push(item);
    });

    return groups;
  }

  handlePublish(e) {
    e.preventDefault();
    this.props.campaignActions.publishCampaign(
      this.props.publishApi,
      this.props.campaignId
    );
  }

}

CampaignListContainer.propTypes = {
  publishApi: React.PropTypes.func.isRequired,
};

function mapStateToProps(state, ownProps) {
  // Find record specific to this item
  let record = null;
  if (state.records && state.records.ChangeSet && ownProps.campaignId) {
    record = state.records.ChangeSet.find(
      (nextRecord) => (nextRecord.ID === parseInt(ownProps.campaignId, 10))
    );
  }
  return {
    record: record || [],
  };
}

function mapDispatchToProps(dispatch) {
  return {
    recordActions: bindActionCreators(recordActions, dispatch),
    campaignActions: bindActionCreators(campaignActions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(CampaignListContainer);
