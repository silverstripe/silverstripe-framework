import React from 'react';
import SilverStripeComponent from 'silverstripe-component';
import GridFieldTable from '../../components/grid-field-table';
import GridFieldHeader from '../../components/grid-field-header';
import GridFieldHeaderCell from '../../components/grid-field-header-cell';
import GridFieldRow from '../../components/grid-field-row';
import GridFieldCell from '../../components/grid-field-cell';

class GridField extends SilverStripeComponent {

	constructor(props) {
        super(props);

        // TODO: This will be an AJAX call and it's response stored in state.
        this.mockData = {
            campaigns: [
                {
                    title: 'SilverStripe 4.0 release',
                    description: 'All the stuff related to the 4.0 announcement',
                    changes: 20
                },
                {
                    title: 'March release',
                    description: 'march release stuff',
                    changes: 2
                },
                {
                    title: 'About us',
                    description: 'The team',
                    changes: 1345
                }
            ]
        };
    }

    render() {
        const columnNames = ['title', 'changes', 'description'];

        const headerCells = columnNames.map((columnName, i) => <GridFieldHeaderCell key={i}>{columnName}</GridFieldHeaderCell>);
        const header = <GridFieldHeader>{headerCells}</GridFieldHeader>;

        const rows = this.mockData.campaigns.map((campaign, i) => {
            const cells = columnNames.map((columnName, i) => {
                return <GridFieldCell key={i}>{campaign[columnName]}</GridFieldCell>
            });
            return <GridFieldRow key={i}>{cells}</GridFieldRow>;
        });

        return (
            <GridFieldTable header={header} rows={rows}></GridFieldTable>
        );
    }

}

export default GridField;
