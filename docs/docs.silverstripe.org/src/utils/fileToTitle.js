const fileToTitle = (str) => (
    str.replace(/^\d+_/, '').replace(/_/g, ' ')
);

module.exports = fileToTitle;
