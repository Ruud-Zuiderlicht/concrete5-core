<?php

namespace Concrete\Core\Board\Template;

use Concrete\Core\Entity\Board\Template;
use Concrete\Core\Filesystem\FileLocator;
use Concrete\Core\Page\Page;

/**
 * Responsible for locating and rendering summary templates.
 */
class TemplateLocator
{

    /**
     * @var FileLocator 
     */
    protected $fileLocator;

    /**
     * @var FileLocator\ThemeLocation
     */
    protected $themeLocation;
    
    public function __construct(FileLocator $fileLocator, FileLocator\ThemeLocation $themeLocation)
    {
        $this->fileLocator = $fileLocator;
        $this->themeLocation = $themeLocation;
    }

    /**
     * @param Page $page
     * @param Template $template
     * @return string file
     */
    public function getFileToRender(Page $page, Template $template)
    {
        $theme = $page->getCollectionThemeObject();
        if ($theme) {
            $handle = $template->getHandle();
            if ($handle) {
                $filename = DIRNAME_ELEMENTS . '/' . DIRNAME_BOARDS . '/' . $handle . '.php';
                $this->themeLocation->setTheme($theme);
                $this->fileLocator->addLocation($this->themeLocation);
                $record = $this->fileLocator->getRecord($filename);
                if ($record->exists()) {
                    return $record->getFile();
                }
            }
        }
        
        return null;

    }
    
}
