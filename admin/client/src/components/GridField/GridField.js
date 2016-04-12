import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import GridFieldTable from './GridFieldTable';
import GridFieldHeader from './GridFieldHeader';
import GridFieldHeaderCell from './GridFieldHeaderCell';
import GridFieldRow from './GridFieldRow';
import GridFieldCell from './GridFieldCell';
import GridFieldAction from './GridFieldAction';
import * as actions from 'state/records/RecordsActions';

/**
 * The component acts as a container for a grid field,
 * with smarts around data retrieval from external sources.
 *
 * @todo Convert to higher order component which hooks up form
 * schema data to an API backend as a grid data source
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

    const data = this.props.data;

    this.props.actions.fetchRecords(
      data.recordType,
      data.collectionReadEndpoint.method,
      data.collectionReadEndpoint.url
    );
  }

  render() {
    const records = this.props.records;
    const handleDrillDown = this.props.data.handleDrillDown;

    if (!records) {
      return <div></div>;
    }

    const columns = this.props.data.columns;

    // Placeholder to align the headers correctly with the content
    const actionPlaceholder = <th className={'grid-field__action-placeholder'} ></th>;
    const headerCells = columns.map((column, i) =>
      <GridFieldHeaderCell key={i}>{column.name}</GridFieldHeaderCell>
    );
    const header = <GridFieldHeader>{headerCells.concat(actionPlaceholder)}</GridFieldHeader>;

    const rows = Object.keys(records).map((i) => {
      const record = records[i];
      // Build cells
      const cells = columns.map((column, j) => {
        // Get value by dot notation
        const val = column.field.split('.').reduce((a, b) => a[b], record);
        const cellProps = {
          handleDrillDown: handleDrillDown ? (event) => handleDrillDown(event, record) : null,
          className: handleDrillDown ? 'grid-field__cell--drillable' : '',
          key: j,
          width: column.width,
        };
        return (
          <GridFieldCell {...cellProps}>
            {val}
          </GridFieldCell>
        );
      });

      const rowActions = (
        <GridFieldCell key={`${i}-actions`}>
          <GridFieldAction
            icon={'cog'}
            handleClick={this.editRecord}
            key={`action-${i}-edit`}
            record={record}
          />
          <GridFieldAction
            icon={'cancel'}
            handleClick={this.deleteRecord}
            key={`action-${i}-delete`}
            record={record}
          />
        </GridFieldCell>
      );

      const rowProps = {
        key: i,
        className: handleDrillDown ? 'grid-field__row--drillable' : '',
      };

      return (
        <GridFieldRow {...rowProps}>
          {cells.concat(rowActions)}
        </GridFieldRow>
      );
    });

    return (
      <GridFieldTable header={header} rows={rows} />
    );
  }

  /**
   * @param object event
   * @param number id
   */
  deleteRecord(event, id) {
    event.preventDefault();
    this.props.actions.deleteRecord(
      this.props.data.recordType,
      id,
      this.props.data.itemDeleteEndpoint.method,
      this.props.data.itemDeleteEndpoint.url
    );
  }

  /**
   * @param object event
   * @param number id
   */
  editRecord(event, id) {
    event.preventDefault();

    if (typeof this.props.data === 'undefined' ||
      typeof this.props.data.handleEditRecord === 'undefined') {
      return;
    }

    this.props.data.handleEditRecord(event, id);
  }

}

GridField.propTypes = {
  data: React.PropTypes.shape({
    recordType: React.PropTypes.string.isRequired,
    headerColumns: React.PropTypes.array,
    collectionReadEndpoint: React.PropTypes.object,
    handleDrillDown: React.PropTypes.func,
    handleEditRecord: React.PropTypes.func,
  }),
};

function mapStateToProps(state, ownProps) {
  const recordType = ownProps.data ? ownProps.data.recordType : null;
  return {
    records: (state.records && recordType) ? state.records[recordType] : {},
  };
}

function mapDispatchToProps(dispatch) {
  return {
    actions: bindActionCreators(actions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(GridField);
