import React from 'react';
import { connect } from 'react-redux';
import SilverStripeComponent from 'silverstripe-component';
import NorthHeader from '../../components/north-header';
import GridField from '../grid-field';
import GridFieldHeader from '../../components/grid-field-header';
import GridFieldHeaderCell from '../../components/grid-field-header-cell';
import GridFieldRow from '../../components/grid-field-row';
import GridFieldCell from '../../components/grid-field-cell';
import Action from '../../components/action';
import i18n from 'i18n';

class CampaignAdminContainer extends SilverStripeComponent {
    constructor(props) {
        super(props);

        this.addCampaign = this.addCampaign.bind(this);
    }

    render() {
        return (
            <div>
                <NorthHeader />
                <Action 
                    text={i18n._t('Campaigns.ADDCAMPAIGN')} 
                    type={'secondary'} 
                    icon={'plus-circled'} 
                    handleClick={this.addCampaign} />
                <GridField />
            </div>
        );
    }

    addCampaign() {
        //Add campaign
    }
}

CampaignAdminContainer.propTypes = {
    config: React.PropTypes.shape({
        data: React.PropTypes.shape({
            forms: React.PropTypes.shape({
                editForm: React.PropTypes.shape({
                    schemaUrl: React.PropTypes.string
                })
            })
        })
    }),
    sectionConfigKey: React.PropTypes.string.isRequired
};

function mapStateToProps(state, ownProps) {
    return {
        config: state.config.sections[ownProps.sectionConfigKey]
    }
}

export default connect(mapStateToProps)(CampaignAdminContainer);
