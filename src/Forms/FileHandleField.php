<?php

namespace SilverStripe\Forms;

interface FileHandleField
{
    public function getAttributes();
    
    public function getFolderName();
    
    public function setAllowedExtensions($rules);
    
    public function getAllowedExtensions();
    
    public function setAllowedFileCategories($category);
    
    public function setFolderName($folderName);
}
