<?php

namespace Rokanthemes\Themeoption\Model\Config;

class Sliderbg extends \Magento\Config\Model\Config\Backend\Image
{
    
    const UPLOAD_DIR = 'rokanthemes/sliderbg';

    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath($this->_appendScopeInfo(self::UPLOAD_DIR));
    }

    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    protected function _getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'png'];
    }
}
