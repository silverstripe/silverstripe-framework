import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as breadcrumbsActions from 'state/breadcrumbs/BreadcrumbsActions';
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
    this.setBreadcrumbs = this.setBreadcrumbs.bind(this);
  }

  componentDidMount() {
    const fetchURL = this.props.itemListViewEndpoint.replace(/:id/, this.props.campaignId);
    super.componentDidMount();
    this.setBreadcrumbs();

    // Only load record if not already present
    if (!Object.keys(this.props.record).length) {
      this.props.recordActions
        .fetchRecord(this.props.treeClass, 'get', fetchURL)
        .then(this.setBreadcrumbs);
    }
  }

  /**
   * Update breadcrumbs for this view
   */
  setBreadcrumbs() {
    // Setup breadcrumbs if record is loaded
    if (!this.props.record) {
      return;
    }

    // Check that we haven't navigated away from this page once the callback has returned
    const thisLink = this.props.sectionConfig.campaignViewRoute
      .replace(/:type\?/, 'set')
      .replace(/:id\?/, this.props.campaignId)
      .replace(/:view\?/, 'show');
    const applies = window.ss.router.routeAppliesToCurrentLocation(
      window.ss.router.resolveURLToBase(thisLink)
    );
    if (!applies) {
      return;
    }

    // Push breadcrumb
    const breadcrumbs = [{
      text: i18n._t('Campaigns.CAMPAIGN', 'Campaigns'),
      href: this.props.sectionConfig.route,
    }];
    breadcrumbs.push({
      text: this.props.record.Name,
      href: thisLink,
    });

    this.props.breadcrumbsActions.setBreadcrumbs(breadcrumbs);
  }

  /**
   * Renders a list of items in a Campaign.
   *
   * @return object
   */
  render() {
    let itemId = this.props.campaign.changeSetItemId;
    let itemLinks = null;
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
        if (!itemId) {
          itemId = item.ID;
        }
        const selected = (itemId === item.ID);

        // Check links
        if (selected && item._links) {
          itemLinks = item._links;
        }

        // Add extra css class for published items
        const itemClassNames = [];
        if (item.ChangeType === 'none' || campaign.State === 'published') {
          itemClassNames.push('list-group-item--inactive');
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

    // Set body
    const pagesLink = this.props.config.sections.CMSMain.route;
    const body = accordionBlocks.length
      ? (<Accordion>{accordionBlocks}</Accordion>)
      : (
        <div className="alert alert-warning" role="alert">
          <strong>This campaign is empty.</strong> You can add pages by selecting{' '}
          <em>Add to campaign</em> from within the <em>More Options</em> popup on{' '}
          the <a href={pagesLink}>edit page screen</a>.
        </div>
      );
    const bodyClass = [
      'container-fluid', 'campaign-items', 'panel-scrollable', 'panel-scrollable--double-toolbar',
    ];

    return (
      <div className="cms-content__split cms-content__split--left-sm">
        <div className="cms-content__left cms-campaigns collapse in" aria-expanded="true">
          <Toolbar showBackButton handleBackButtonClick={this.props.handleBackButtonClick}>
            <BreadcrumbComponent multiline crumbs={this.props.breadcrumbs} />
          </Toolbar>
          <div className={bodyClass.join(' ')}>
            {body}
          </div>
          <div className="toolbar--south">
            {this.renderButtonToolbar()}
          </div>
        </div>
        <Preview itemLinks={itemLinks} itemId={itemId} />
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
    if (!items || !items.length) {
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
        bootstrapButtonStyle: 'primary',
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
      return this.props.record._embedded.items;
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
      this.props.treeClass,
      this.props.campaignId
    );
  }

}

CampaignAdminList.propTypes = {
  campaign: React.PropTypes.shape({
    isPublishing: React.PropTypes.bool.isRequired,
    changeSetItemId: React.PropTypes.number,
  }),
  breadcrumbsActions: React.PropTypes.object.isRequired,
  campaignActions: React.PropTypes.object.isRequired,
  publishApi: React.PropTypes.func.isRequired,
  record: React.PropTypes.object.isRequired,
  recordActions: React.PropTypes.object.isRequired,
  sectionConfig: React.PropTypes.object.isRequired,
  handleBackButtonClick: React.PropTypes.func,
};

function mapStateToProps(state, ownProps) {
  // Find record specific to this item
  let record = null;
  const treeClass = ownProps.sectionConfig.treeClass;
  if (state.records && state.records[treeClass] && ownProps.campaignId) {
    record = state.records[treeClass][parseInt(ownProps.campaignId, 10)];
  }
  return {
    config: state.config,
    record: record || {},
    campaign: state.campaign,
    treeClass,
    breadcrumbs: state.breadcrumbs,
  };
}

function mapDispatchToProps(dispatch) {
  return {
    breadcrumbsActions: bindActionCreators(breadcrumbsActions, dispatch),
    recordActions: bindActionCreators(recordActions, dispatch),
    campaignActions: bindActionCreators(campaignActions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(CampaignAdminList);
