import React, { useState, StatelessComponent } from 'react';
import { MenuList, Icon } from 'bloomer';
import { Link } from 'gatsby';
import styled from 'styled-components';
import { MenuItemProps } from '../types';
import sortFiles from '../utils/sortFiles';

const Item = styled.li`
    a {
        display: flex;
    }
`;

const Toggle = styled.span`
    display: inline-block;
    margin-left: 1rem;

`;

const ToggleableMenuItem: StatelessComponent<MenuItemProps> = ({ item, mapFn, active }) => {
    const [ isOpen, setOpen ] = useState(active);    
    const { slug, title } = item.fields;
    const { children } = item;
    return (
        <Item>
            <Link activeClassName={`is-active`} to={item.indexFile ? slug : '#'}>
            <span>{title}</span>
            {!!children.length &&
                <Toggle onClick={(e) => {e.preventDefault(); setOpen(!isOpen)}}>{isOpen ? '▼' : '▲'}</Toggle>
            }
        </Link>
        {isOpen && !!children.length &&   
            <MenuList>
                {children.filter(n => n.fields.title !== 'index').sort(sortFiles).map(mapFn)}
            </MenuList>
        }
      </Item>
    );
};

export default ToggleableMenuItem;
