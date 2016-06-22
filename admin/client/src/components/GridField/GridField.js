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
import FormConstants from 'components/Form/FormConstants';
import * as actions from 'state/records/RecordsActions';

const NotYetLoaded = {};

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
    // props.records is keyed by record identifiers
    if (this.props.records === NotYetLoaded) {
      // TODO Replace with better loading indicator
      return <div>Loading...</div>;
    }

    if (!Object.getOwnPropertyNames(this.props.records).length) {
      return <div>No campaigns created yet.</div>;
    }

    // Placeholder to align the headers correctly with the content
    const actionPlaceholder = <th key="holder" className={'grid-field__action-placeholder'}></th>;
    const headerCells = this.props.data.columns.map((column) =>
      <GridFieldHeaderCell key={`${column.name}`}>{column.name}</GridFieldHeaderCell>
    );
    const header = <GridFieldHeader>{headerCells.concat(actionPlaceholder)}</GridFieldHeader>;
    const rows = Object.keys(this.props.records).map((key) =>
      this.createRow(this.props.records[key])
    );

    return (
      <GridFieldTable header={header} rows={rows} />
    );
  }

  createRowActions(record) {
    return (
      <GridFieldCell className="grid-field__cell--actions" key="Actions">
        <GridFieldAction
          icon={'cog'}
          handleClick={this.editRecord}
          record={record}
        />
        <GridFieldAction
          icon={'cancel'}
          handleClick={this.deleteRecord}
          record={record}
        />
      </GridFieldCell>
    );
  }

  createCell(record, column) {
    const handleDrillDown = this.props.data.handleDrillDown;
    const cellProps = {
      className: handleDrillDown ? 'grid-field__cell--drillable' : '',
      handleDrillDown: handleDrillDown ? (event) => handleDrillDown(event, record) : null,
      key: `${column.name}`,
      width: column.width,
    };
    const val = column.field.split('.').reduce((a, b) => a[b], record);

    return <GridFieldCell {...cellProps}>{val}</GridFieldCell>;
  }

  /**
   * Create a row components for the passed record.
   */
  createRow(record) {
    const rowProps = {
      className: this.props.data.handleDrillDown ? 'grid-field__row--drillable' : '',
      key: `${record.ID}`,
    };
    const cells = this.props.data.columns.map((column) =>
      this.createCell(record, column)
    );
    const rowActions = this.createRowActions(record);

    return (
      <GridFieldRow {...rowProps}>
        {cells}
        {rowActions}
      </GridFieldRow>
    );
  }

  /**
   * @param object event
   * @param number id
   */
  deleteRecord(event, id) {
    event.preventDefault();
    const headers = {};
    headers[FormConstants.CSRF_HEADER] = this.props.config.SecurityID;

    this.props.actions.deleteRecord(
      this.props.data.recordType,
      id,
      this.props.data.itemDeleteEndpoint.method,
      this.props.data.itemDeleteEndpoint.url,
      headers
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
    config: state.config,
    records: recordType && state.records[recordType] ? state.records[recordType] : NotYetLoaded,
  };
}

function mapDispatchToProps(dispatch) {
  return {
    actions: bindActionCreators(actions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(GridField);
