import React, { StatelessComponent, ReactElement } from 'react';
import { Card, CardHeaderTitle, CardHeader, CardFooter, CardFooterItem } from 'bloomer';
import { CardContent } from 'bloomer/lib/components/Card/CardContent';
import { GenericHierarchyNode, ChildrenOfProps } from '../types';
import { Link } from 'gatsby';
import styled from 'styled-components';

const ChildrenBlock = styled.div`
    display: grid;
    grid-gap: 4rem;
    align-items: stretch;
    grid-template-columns: repeat( auto-fit, minmax(250px, 1fr) );
`;
const StretchContent = styled(CardContent)`
    flex-grow: 1;
`;

const FlexCard = styled(Card)`
    display: flex;
    flex-direction: column;
    align-items: stretch;
`
const createChildren = (children: GenericHierarchyNode[]): ReactElement[] => {
    return children.map(child => {
        const content = child.indexFile && child.indexFile.frontmatter.summary;
        return (
            <FlexCard key={child.fields.slug}>
                <CardHeader>
                    <CardHeaderTitle>
                        {child.fields.title}
                    </CardHeaderTitle>
                </CardHeader>
                <StretchContent>
                    {content || ''}
                </StretchContent>            
                <CardFooter>
                    <CardFooterItem>   
                        <Link to={child.fields.slug}>View</Link>
                    </CardFooterItem>
                </CardFooter>
            </FlexCard>
        );
    })

}
const ChildrenOf: StatelessComponent<ChildrenOfProps> = ({ folderName, exclude, currentNode }) => {
    if (!currentNode) {
        return null;
    }
    let children: ReactElement[] = [];
    if (!folderName && !exclude) {
        children = createChildren((currentNode.siblings || []).filter(c => c.__typename === 'MarkdownRemark'));
    } else if (folderName) {
        const targetFolder = currentNode.children.find(
            child => child.fields.fileTitle === folderName && child.__typename === 'Directory'
        );
        children = targetFolder ? createChildren(targetFolder.children) : [];
    } else if (exclude) {
        children = createChildren(
            currentNode.children.filter(child => child.fields.fileTitle === exclude)
        );
    }

    return <ChildrenBlock>{children}</ChildrenBlock>

};

export default ChildrenOf;