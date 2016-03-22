import React from 'react';
import { connect } from 'react-redux';
import SilverStripeComponent from 'silverstripe-component';
import NorthHeader from '../../components/north-header';
import GridField from '../grid-field';

class CampaignAdminContainer extends SilverStripeComponent {

    render() {
        return (
            <div>
                <NorthHeader></NorthHeader>
                <GridField></GridField>
            </div>
        );
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
