const sortFiles = (a, b) => {
    if (a.__typename === b.__typename) {
        return a.fields.fileTitle > b.fields.fileTitle ? 1 : -1;
    }
    return a.__typename < b.__typename ? 1 : -1;
};

export default sortFiles;