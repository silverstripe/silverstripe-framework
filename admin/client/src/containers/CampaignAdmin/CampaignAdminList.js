import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as recordActions from 'state/records/RecordsActions';
import * as campaignActions from 'state/campaign/CampaignActions';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import Accordion from 'components/Accordion/Accordion';
import AccordionBlock from 'components/Accordion/AccordionBlock';
import ListGroupItem from 'components/ListGroup/ListGroupItem';
import Toolbar from 'components/Toolbar/Toolbar';
import FormAction from 'components/FormAction/FormAction';
import CampaignAdminItem from './CampaignAdminItem';
import BreadcrumbComponent from 'components/Breadcrumb/Breadcrumb';
import Preview from 'components/Preview/Preview';
import i18n from 'i18n';


/**
 * Represents a campaign list view
 */
class CampaignAdminList extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handlePublish = this.handlePublish.bind(this);
    this.handleItemSelected = this.handleItemSelected.bind(this);
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
    let itemID = this.props.campaign.changeSetItemId;
    let previewUrl = null;
    let previewType = null;
    const campaignId = this.props.campaignId;
    const campaign = this.props.record;

    // Trigger different layout when preview is enabled
    const itemGroups = this.groupItemsForSet();

    // Get items in this set
    let accordionBlocks = [];

    Object.keys(itemGroups).forEach(className => {
      const group = itemGroups[className];
      const groupCount = group.items.length;

      let listGroupItems = [];
      let title = `${groupCount} ${groupCount === 1 ? group.singular : group.plural}`;
      let groupid = `Set_${campaignId}_Group_${className}`;

      // Create items for this group
      group.items.forEach(item => {
        // Auto-select first item
        if (!itemID) {
          itemID = item.ID;
        }

        // Find preview url
        const selected = (itemID === item.ID);
        if (selected && item._links.preview) {
          if (item._links.preview.Stage) {
            previewUrl = item._links.preview.Stage.href;
            previewType = item._links.preview.Stage.type;
          } else if (item._links.preview.Live) {
            previewUrl = item._links.preview.Live.href;
            previewType = item._links.preview.Live.type;
          }
        }

        // Add extra css class for published items
        const itemClassNames = [];
        if (item.ChangeType === 'none' || campaign.State === 'published') {
          itemClassNames.push('list-group-item--published');
        }
        if (selected) {
          itemClassNames.push('active');
        }

        listGroupItems.push(
          <ListGroupItem
            key={item.ID}
            className={itemClassNames.join(' ')}
            handleClick={this.handleItemSelected}
            handleClickArg={item.ID}
          >
            <CampaignAdminItem item={item} campaign={this.props.record} />
          </ListGroupItem>
        );
      });

      // Merge into group
      accordionBlocks.push(
        <AccordionBlock key={groupid} groupid={groupid} title={title}>
          {listGroupItems}
        </AccordionBlock>
      );
    });

    // Get preview details
    const classNames = previewUrl
      ? 'cms-content__split cms-content__split--left-sm'
      : 'cms-content__split cms-content__split--none';

    return (
      <div className={classNames}>
        <div className="cms-content__left collapse in" aria-expanded="true">
          <Toolbar>
            <BreadcrumbComponent crumbs={this.props.breadcrumbs} multiline />
          </Toolbar>
          <div className="container-fluid campaign-items panel-scrollable--double-toolbar">
            <Accordion>
              {accordionBlocks}
            </Accordion>
          </div>
          <div className="toolbar--south">
            {this.renderButtonToolbar()}
          </div>
        </div>
        { previewUrl && <Preview previewUrl={previewUrl} previewType={previewType} /> }
      </div>
    );
  }

  /**
   * Callback for items being clicked on
   *
   * @param {object} event
   * @param {number} itemId
   */
  handleItemSelected(event, itemId) {
    this.props.campaignActions.selectChangeSetItem(itemId);
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

CampaignAdminList.propTypes = {
  campaign: React.PropTypes.shape({
    isPublishing: React.PropTypes.bool.isRequired,
    changeSetItemId: React.PropTypes.number,
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

export default connect(mapStateToProps, mapDispatchToProps)(CampaignAdminList);
