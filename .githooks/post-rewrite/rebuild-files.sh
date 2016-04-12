if [[ $1 = "rebase" ]]; then
    echo "\nRebuiling compiled files post $1..."

    npm run build

    echo "Adding built files to the last commit"
    git add -u
    git commit --amend --no-edit
fi
