<?php

namespace SilverStripe\Assets\Tests\UploadTest;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Dev\TestOnly;

class Validator extends Upload_Validator implements TestOnly
{

    /**
     * Looser check validation that doesn't do is_upload_file()
     * checks as we're faking a POST request that PHP didn't generate
     * itself.
     *
     * @return boolean
     */
    public function validate()
    {
        $pathInfo = pathinfo($this->tmpFile['name']);
        // filesize validation

        if (!$this->isValidSize()) {
            $ext = (isset($pathInfo['extension'])) ? $pathInfo['extension'] : '';
            $arg = File::format_size($this->getAllowedMaxFileSize($ext));
            $this->errors[] = _t(
                'File.TOOLARGE',
                'File size is too large, maximum {size} allowed',
                'Argument 1: File size (e.g. 1MB)',
                array('size' => $arg)
            );
            return false;
        }

        // extension validation
        if (!$this->isValidExtension()) {
            $this->errors[] = _t('File.INVALIDEXTENSIONSHORT', 'Extension is not allowed');
            return false;
        }

        return true;
    }
}
