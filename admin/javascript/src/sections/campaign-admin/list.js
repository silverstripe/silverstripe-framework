import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as actions from 'state/records/actions';
import SilverStripeComponent from 'silverstripe-component';
import Accordion from 'components/accordion/index';
import AccordionGroup from 'components/accordion/group';
import AccordionItem from 'components/accordion/item';
import NorthHeader from 'components/north-header/index';
import CampaignItem from './item';
import CampaignPreview from './preview';

/**
 * Represents a campaign list view
 */
class CampaignListContainer extends SilverStripeComponent {

  componentDidMount() {
    const fetchURL = this.props.itemListViewEndpoint.replace(/:id/, this.props.campaignId);
    super.componentDidMount();
    this.props.actions.fetchRecord('ChangeSet', 'get', fetchURL);
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
        </div>
        { previewUrl && <CampaignPreview previewUrl={previewUrl} /> }
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
   * Group items for changeset display
   *
   * @return array
   */
  groupItemsForSet() {
    const groups = {};
    if (!this.props.record || !this.props.record._embedded) {
      return groups;
    }
    const items = this.props.record._embedded.ChangeSetItems;

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

}

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
    actions: bindActionCreators(actions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(CampaignListContainer);
