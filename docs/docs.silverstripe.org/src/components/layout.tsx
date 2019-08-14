import 'bulma/css/bulma.css';
import '../styles/style.scss';

import React, { StatelessComponent } from "react";
import { useStaticQuery, graphql } from "gatsby";
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

interface LayoutProps {
  children: any[];

}

const Layout: StatelessComponent<LayoutProps> = ({ children }) => {
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
    <Section>
        <Container>
          <Level>
            <LevelLeft>
              <LevelItem>
                <h1 className="subtitle is-5">{siteData.site.siteMetadata.title}</h1>
              </LevelItem>
            </LevelLeft>
            <LevelRight>
              <LevelItem>
                <form>
                  <Field hasAddons>
                    <Control>
                      <Input type="text" placeholder="Search the docs" />
                    </Control>
                    <Control>
                      <Button>Search</Button>
                    </Control>
                  </Field>
                </form>
              </LevelItem>
            </LevelRight>
          </Level>
          <Columns>
            <Column isSize='1/3'>
              <Nav />
            </Column>
            <Column>
              {children}
            </Column>
          </Columns>
        </Container>
    </Section>
  );
};
export default Layout;
