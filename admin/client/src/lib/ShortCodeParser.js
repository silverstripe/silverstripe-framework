
function attrsToString(attrs) {
  return Object.entries(attrs)
    .map((attr) => {
      const [name, value] = attr;
      return (value) ? `${name}="${value}"` : null;
    })
    .filter((attr) => !!attr)
    .join(' ');
}

function stringToAttrs(attrString) {
  const matchRule = /([^\s\/'"=,]+)\s*=\s*(('([^']+)')|("([^"]+)")|([^\s,\]]+))/g;
  const reduceRule = /^([^\s\/'"=,]+)\s*=\s*(?:(?:'([^']+)')|(?:"([^"]+)")|(?:[^\s,\]]+))$/;
  return attrString
  // Split on all attributes, quoted or not
    .match(matchRule)
    .reduce((prev, next) => {
      const match = next.match(reduceRule);
      const key = match[1];
      // single, double, or unquoted match
      const value = match[2] || match[3] || match[4];

      return Object.assign({},
        prev,
        { [key]: value }
      );
    }, {});
}

/**
 * Handles shortcode parsing on the client-side, will need to replicate on server-side.
 *
 * Can use generic call functions `elementToCode(elements, code)` or `codeToHtml(html, code)`
 * or shortcode specific function calls.
 */
const ShortCodeParser = {
  elementToCode(elements, code) {
    switch (code) {
      case 'embed': {
        return this.toEmbedCode(elements);
      }

      case 'image': {
        return this.toImageCode(elements);
      }

      default: {
        return null;
      }
    }
  },

  codeToHtml(html, code) {
    switch (code) {
      case 'embed': {
        return this.toEmbedHtml(html);
      }

      case 'image': {
        return this.toImageHtml(html);
      }

      default: {
        return null;
      }
    }
  },

  toImageHtml(html) {
    let content = html;
    let matches = null;
    const tagRegex = /\[image(.*?)\]/gi;

    matches = tagRegex.exec(content);
    while (matches) {
      const attrs = stringToAttrs(matches[1]);
      const element = document.createElement('img');

      Object.entries(attrs)
        .filter((attr) => attr !== 'id')
        .forEach((attr) => {
          const [name, value] = attr;
          element.setAttribute(name, value);
        });
      element.dataset.id = attrs.id;

      content = content.replace(matches[0], element.outerHTML);
      matches = tagRegex.exec(content);
    }

    return content;
  },

  toEmbedHtml(html) {
    let content = html;
    let matches = null;
    const tagRegex = /\[embed(.*?)\](.+?)\[\/\s*embed\s*\]/gi;

    matches = tagRegex.exec(content);
    while (matches) {
      const attrs = stringToAttrs(matches[1]);
      const element = document.createElement('img');
      attrs.cssclass = attrs.class;

      ['width', 'height', 'class'].forEach((name) => (
        element.setAttribute(name, attrs[name])
      ));
      element.setAttribute('src', attrs.thumbnail);
      element.dataset.url = matches[2];

      element.classList.add('ss-htmleditorfield-file embed');

      Object.entries(attrs).forEach((attr) => {
        const [name, value] = attr;
        element.dataset[name] = value;
      });

      content = content.replace(matches[0], element.outerHTML);
      matches = tagRegex.exec(content);
    }

    return content;
  },

  toImageCode(elements) {
    const tempDiv = document.createElement('div');

    elements.querySelectorAll('img').forEach((element) => {
      const attrs = {
        // Requires server-side preprocessing of HTML+shortcodes in HTMLValue
        src: element.getAttribute('src'),
        id: element.dataset.id,
        width: element.getAttribute('width'),
        height: element.getAttribute('height'),
        class: element.getAttribute('class'),
        // don't save caption, since that's in the containing element
        title: element.getAttribute('title'),
        alt: element.getAttribute('alt'),
      };
      tempDiv.innerHTML = `[image ${attrsToString(attrs)}]`;
      const shortCode = tempDiv.childNodes[0];

      element.parentNode.replaceChild(shortCode, element);
    });

    return elements;
  },

  toEmbedCode(elements) {
    const tempDiv = document.createElement('div');

    elements.querySelectorAll('.ss-htmleditorfield-file.embed').forEach((element) => {
      const attrs = {
        width: element.getAttribute('width'),
        class: element.getAttribute('cssclass'),
        thumbnail: element.dataset.thumbnail,
      };
      const url = element.dataset.url;

      tempDiv.innerHTML = `[embed ${attrsToString(attrs)}]${url}[/embed]`;
      const shortCode = tempDiv.childNodes[0];

      element.parentNode.replaceChild(shortCode, element);
    });

    return elements;
  },
};

export { attrsToString, stringToAttrs };

export default ShortCodeParser;
