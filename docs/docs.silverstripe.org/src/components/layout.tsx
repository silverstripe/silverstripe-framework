import 'bulma/css/bulma.css';
import '../styles/style.scss';
import styled from 'styled-components';

import React, { StatelessComponent } from "react";
import { useStaticQuery, graphql, Link } from "gatsby";
import {
  Section,
  Container,
  Level,
  LevelItem,
  LevelLeft,
  LevelRight,
  Field,
  Control,
  Button,
  Input,
  Columns,
  Column 
} from 'bloomer';
import  Nav from './Nav';
import SearchBox from './SearchBox';

const TopLevel = styled(Level)`
  padding: 2rem;
  background: #005b94;
  position: sticky;
  top: 0;
  z-index: 1000;
  .subtitle {
    a {
      color: #fff;
    }
  }
`;

interface LayoutProps {
  children: any[];

}

const Layout: StatelessComponent<LayoutProps> = ({ children}) => {
  const siteData = useStaticQuery(graphql`
    query SiteTitleQuery {
      site {
        siteMetadata {
          title
        }
      }
    }
  `);
  return (
        <>
          <TopLevel>
            <LevelLeft>
              <LevelItem>
                <h1 className="subtitle is-5"><Link to="/">{siteData.site.siteMetadata.title}</Link></h1>
              </LevelItem>
            </LevelLeft>
            <LevelRight>
              <LevelItem>
                <form>
                  <Field hasAddons>
                    <Control>
                      {process.env.GATSBY_DOCSEARCH_API_KEY && process.env.GATSBY_DOCSEARCH_INDEX && (
                      <SearchBox />
                      )}
                    </Control>
                    <Control>
                      <Button>Search</Button>
                    </Control>
                  </Field>
                </form>
              </LevelItem>
            </LevelRight>
          </TopLevel>
          <Container isFluid>
          <Section>      
          <Columns>
            <Column isSize="1/4">
              <Nav />
            </Column>
            <Column isSize="3/4">
              {children}
            </Column>
          </Columns>
          </Section>
          </Container>          
      </>
    
  );
};
export default Layout;
