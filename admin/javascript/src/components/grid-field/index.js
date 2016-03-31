import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import SilverStripeComponent from 'silverstripe-component';
import GridFieldTable from './table';
import GridFieldHeader from './header';
import GridFieldHeaderCell from './header-cell';
import GridFieldRow from './row';
import GridFieldCell from './cell';
import GridFieldAction from './action';
import * as actions from 'state/records/actions';

/**
 * The component acts as a container for a grid field,
 * with smarts around data retrieval from external sources.
 *
 * @todo Convert to higher order component which hooks up form schema data to an API backend as a grid data source
 * @todo Replace "dumb" inner components with third party library (e.g. https://griddlegriddle.github.io)
 */
class GridField extends SilverStripeComponent {

    constructor(props) {
        super(props);

        this.deleteRecord = this.deleteRecord.bind(this);
        this.editRecord = this.editRecord.bind(this);
    }

    componentDidMount() {
        super.componentDidMount();

        let data = this.props.data;

        this.props.actions.fetchRecords(data.recordType, data.collectionReadEndpoint.method, data.collectionReadEndpoint.url);
    }

    render() {
        const records = this.props.records;
        if(!records) {
            return <div></div>;
        }

        const columns = this.props.data.columns;

        // Placeholder to align the headers correctly with the content
        const actionPlaceholder = <GridFieldCell key={'actionPlaceholder'} />;
        const headerCells = columns.map((column, i) => <GridFieldHeaderCell key={i} >{column.name}</GridFieldHeaderCell>);
        const header = <GridFieldHeader>{headerCells.concat(actionPlaceholder)}</GridFieldHeader>;

        const rows = records.map((record, i) => {
            var cells = columns.map((column, i) => {
                // Get value by dot notation
                var val = column.field.split('.').reduce((a, b) => a[b], record)
                return <GridFieldCell key={i}>{val}</GridFieldCell>
            });

            var rowActions = <GridFieldCell key={i + '-actions'}>
                <GridFieldAction
                    icon={'cog'}
                    handleClick={this.editRecord.bind(this, record.ID)}
                    key={"action-" + i + "-edit"}
                />
                <GridFieldAction
                    icon={'cancel'}
                    handleClick={this.deleteRecord.bind(this, record.ID)}
                    key={"action-" + i + "-delete"}
                />
            </GridFieldCell>;

            return <GridFieldRow key={i}>{cells.concat(rowActions)}</GridFieldRow>;
        });

        return (
            <GridFieldTable header={header} rows={rows}></GridFieldTable>
        );
    }

    /**
     * @param number int
     * @param event
     */
    deleteRecord(id, event) {
        event.preventDefault();
        this.props.actions.deleteRecord(
            this.props.data.recordType,
            id,
            this.props.data.itemDeleteEndpoint.method,
            this.props.data.itemDeleteEndpoint.url
        );
    }

    editRecord(id, event) {
        event.preventDefault();
        // TODO
    }

}

GridField.propTypes = {
    data: React.PropTypes.shape({
        recordType: React.PropTypes.string.isRequired,
        headerColumns: React.PropTypes.array,
        collectionReadEndpoint: React.PropTypes.object
    })
};

function mapStateToProps(state, ownProps) {
    let recordType = ownProps.data ? ownProps.data.recordType : null;
    return {
        records: (state.records && recordType) ? state.records[recordType] : []
    }
}

function mapDispatchToProps(dispatch) {
    return {
        actions: bindActionCreators(actions, dispatch)
    }
}

export default connect(mapStateToProps, mapDispatchToProps)(GridField);
