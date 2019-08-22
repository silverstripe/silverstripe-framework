import React, { useState, StatelessComponent, MouseEvent } from 'react';
import { Link } from 'gatsby';
import styled from 'styled-components';
import { MenuItemProps } from '../types';
import sortFiles from '../utils/sortFiles';
import useMeasure from '../hooks/useMeasure';
import usePrevious from '../hooks/usePrevious';
import { useSpring, animated } from 'react-spring';

const Item = styled.li`
    position: relative;
`;

const Toggle = styled.span`
    margin-left: 2rem;
`;

const ToggleableMenuItem: StatelessComponent<MenuItemProps> = ({ item, mapFn, active }) => {
    const [bind, { height: viewHeight }] = useMeasure()  
    const [ isOpen, setOpen ] = useState(active);  
    const previous = usePrevious(isOpen)    
    const { height, opacity } = useSpring({
        from: { height: 0, opacity: 0 },
        to: { height: isOpen ? viewHeight : 0, opacity: isOpen ? 1 : 0 }
      })
    
    const { slug, title } = item.fields;
    const { children } = item;
    const toggle = (e: MouseEvent<HTMLAnchorElement>) => {

        e.stopPropagation();
        e.preventDefault();
        console.log('set open', !isOpen);
        setOpen(!isOpen)
    };
    const deadLink  = item.__typename === 'Directory' && !item.indexFile;
    const onClick = deadLink ? toggle : undefined;
    const to = deadLink ? '#' : slug;
    return (
        <Item>
            <Link activeClassName={`is-active`} to={to} onClick={onClick}>
            <span>{title}</span>
            {!!children.length &&
                <Toggle onClick={toggle}>{isOpen ? '▼' : '▲'}</Toggle>
            }

        </Link>        

            <animated.div style={{ opacity, height: isOpen && previous === isOpen ? 'auto' : height }}>
                <animated.ul className="menu-list" {...bind}>
                {children.filter(n => n.fields.title !== 'index').sort(sortFiles).map(mapFn)}
                </animated.ul>
            </animated.div>        
      </Item>
    );
};

export default ToggleableMenuItem;
