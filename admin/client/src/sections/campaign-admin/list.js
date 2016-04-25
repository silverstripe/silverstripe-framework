import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as recordActions from 'state/records/actions';
import * as campaignActions from 'state/campaign/actions';
import SilverStripeComponent from 'silverstripe-component';
import Accordion from 'components/accordion/index';
import AccordionGroup from 'components/accordion/group';
import AccordionItem from 'components/accordion/item';
import Toolbar from 'components/toolbar/index';
import FormAction from 'components/form-action/index';
import CampaignItem from './item';
import BreadcrumbComponent from 'components/breadcrumb/index';
import CampaignPreview from 'components/preview/index';
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
    const campaign = this.props.record;

    // Trigger different layout when preview is enabled
    const previewUrl = this.previewURLForItem(itemID);
    const itemGroups = this.groupItemsForSet();
    const classNames = previewUrl ? 'cms-content__split cms-content__split--left-sm' : 'cms-content__split cms-content__split--none';

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

        if (item.ChangeType === 'none' || campaign.State === 'published') {
          itemClassName = 'list-group-item--published';
        }

        accordionItems.push(
          <AccordionItem key={item.ID} className={itemClassName}>
            <CampaignItem item={item} campaign={this.props.record} />
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
        <div className="cms-content__left collapse in" aria-expanded="true">
          <Toolbar>
            <BreadcrumbComponent crumbs={this.props.breadcrumbs} multiline />
          </Toolbar>
          <div className="container-fluid campaign-items panel-scrollable--double-toolbar">
            <Accordion>
              {accordionGroups}
            </Accordion>
          </div>
          <div className="toolbar--south">
            {this.renderButtonToolbar()}
          </div>
        </div>
        { previewUrl && <CampaignPreview previewUrl={previewUrl} /> }
      </div>
    );
  }

  renderButtonToolbar() {
    const items = this.getItems();

    // let itemSummaryLabel;
    if (!items) {
      return <div className="btn-toolbar"></div>;
    }

    // let itemSummaryLabel = i18n.sprintf(
    //   items.length === 1
    //     ? i18n._t('Campaigns.ITEM_SUMMARY_SINGULAR')
    //     : i18n._t('Campaigns.ITEM_SUMMARY_PLURAL'),
    //   items.length
    // );

    let actionProps = {};

    if (this.props.record.State === 'open') {
      actionProps = Object.assign(actionProps, {
        label: i18n._t('Campaigns.PUBLISHCAMPAIGN'),
        bootstrapButtonStyle: 'success',
        loading: this.props.campaign.isPublishing,
        handleClick: this.handlePublish,
        icon: 'rocket',
      });
    } else if (this.props.record.State === 'published') {
      // TODO Implement "revert" feature
      actionProps = Object.assign(actionProps, {
        label: i18n._t('Campaigns.REVERTCAMPAIGN'),
        bootstrapButtonStyle: 'default',
        icon: 'back-in-time',
        disabled: true,
      });
    }

    // TODO Fix indicator positioning
    // const itemCountIndicator = (
    //   <span className="text-muted">
    //     <span className="label label-warning label--empty">&nbsp;</span>
    //     &nbsp;{itemSummaryLabel}
    //   </span>
    // );

    return (
      <div className="btn-toolbar">
        <FormAction {...actionProps} />
      </div>
    );
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
  campaign: React.PropTypes.shape({
    isPublishing: React.PropTypes.bool.isRequired,
  }),
  campaignActions: React.PropTypes.object.isRequired,
  publishApi: React.PropTypes.func.isRequired,
  record: React.PropTypes.object.isRequired,
  recordActions: React.PropTypes.object.isRequired,
};

function mapStateToProps(state, ownProps) {
  // Find record specific to this item
  let record = null;
  if (state.records && state.records.ChangeSet && ownProps.campaignId) {
    record = state.records.ChangeSet[parseInt(ownProps.campaignId, 10)];
  }
  return {
    record: record || {},
    campaign: state.campaign,
  };
}

function mapDispatchToProps(dispatch) {
  return {
    recordActions: bindActionCreators(recordActions, dispatch),
    campaignActions: bindActionCreators(campaignActions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(CampaignListContainer);
