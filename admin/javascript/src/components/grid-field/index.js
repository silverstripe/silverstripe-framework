import React from 'react';
import SilverStripeComponent from '../../SilverStripeComponent';
import GridFieldTable from './table';
import GridFieldHeader from './header';
import GridFieldHeaderCell from './header-cell';
import GridFieldRow from './row';
import GridFieldCell from './cell';
import GridFieldAction from './action';

class GridField extends SilverStripeComponent {

    constructor(props) {
        super(props);

        this.deleteCampaign = this.deleteCampaign.bind(this);
        this.editCampaign = this.editCampaign.bind(this);

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
        const columns = [
            {
                name: 'title'
            },
            {
                name: 'changes',
                width: 2
            },
            {
                name: 'description',
                width: 6
            }
        ];

        const actions = [
            <GridFieldAction icon={'cog'} handleClick={this.editCampaign} />,
            <GridFieldAction icon={'cancel'} handleClick={this.deleteCampaign} />
        ];

        // Placeholder to align the headers correctly with the content
        const actionPlaceholder = <span key={'actionPlaceholder'} style={{width: actions.length * 36 + 12}} />;

        const headerCells = columns.map((column, i) => <GridFieldHeaderCell key={i} width={column.width}>{column.name}</GridFieldHeaderCell>);
        const header = <GridFieldHeader>{headerCells.concat(actionPlaceholder)}</GridFieldHeader>;

        const rows = this.mockData.campaigns.map((campaign, i) => {
            var cells = columns.map((column, i) => {
                return <GridFieldCell key={i} width={column.width}>{campaign[column.name]}</GridFieldCell>
            });

            var rowActions = actions.map((action, j) => {
                return Object.assign({}, action, {
                    key: `action-${i}-${j}`
                });
            })

            return <GridFieldRow key={i}>{cells.concat(rowActions)}</GridFieldRow>;
        });

        return (
            <GridFieldTable header={header} rows={rows}></GridFieldTable>
        );
    }

    deleteCampaign(event) {
        // delete campaign
    }

    editCampaign(event) {
        // edit campaign
    }

}

export default GridField;
