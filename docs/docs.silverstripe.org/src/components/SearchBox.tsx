import React from 'react'
import { StatelessComponent, ReactElement, useState, useEffect } from 'react';
import { navigateTo } from "gatsby-link"

interface SearchBoxProps {
  identifier: string;
}

const autocompleteSelected = (e) => {
    e.stopPropagation()
    // Use an anchor tag to parse the absolute url (from autocomplete.js) into a relative url
    // eslint-disable-next-line no-undef
    const a = document.createElement(`a`)
    a.href = e._args[0].url
    navigateTo(`${a.pathname}${a.hash}`)
};

const SearchBox: StatelessComponent<SearchBoxProps> = ({ identifier }): ReactElement|null => {
    useEffect(() => {
        if (typeof window === 'undefined') return;

        window.addEventListener(
            `autocomplete:selected`,
            autocompleteSelected,
            true
        );
        console.log(process.env.GATSBY_DOCSEARCH_API_KEY)
        if(window.docsearch){
            window.docsearch({ 
              apiKey: process.env.GATSBY_DOCSEARCH_API_KEY, 
              indexName: process.env.GATSBY_DOCSEARCH_INDEX, 
              inputSelector: `#${identifier}`,
              algoliaOptions: {
                hitsPerPage: 5
              },
              debug: true
            });
          }
      
    }, []);

    return (
            <input
                id={identifier}
                type="search"
                placeholder="Search the docs..."
                className="form-control search-input"
            />
      )  
};

export default SearchBox;
