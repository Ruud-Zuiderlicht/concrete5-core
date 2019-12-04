<?php
namespace Concrete\Controller\Element\Navigation;

use Concrete\Core\Controller\ElementController;
use Concrete\Core\Entity\Express\Entity;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\PageList;

class Menu extends ElementController
{

    protected $currentPage;
    protected $startingParentPage;
    protected $trail = [];
    
    public function __construct(Page $startingParentPage, Page $currentPage = null)
    {
        parent::__construct();
        $this->startingParentPage = $startingParentPage;
        if (is_object($currentPage)) {
            $this->trail = array($currentPage->getCollectionID());
            $cParentID = Page::getCollectionParentIDFromChildID($currentPage->getCollectionID());
            while($cParentID > 0) {
                $this->trail[] = $cParentID;
                $cParentID = Page::getCollectionParentIDFromChildID($cParentID);
            }
        }

        //array_pop($this->trail);
        $this->currentPage = $currentPage;
    }

    public function getElement()
    {
        return 'navigation/menu';
    }

    public function displayChildPages(Page $page)
    {
        if (!is_object($this->currentPage)) {
            return false;
        }
        if ($page->getCollectionID() == $this->currentPage->getCollectionID()) {
            return true;
        }
        if (in_array($page->getCollectionID(), $this->trail)) {
            return true;
        }
    }

    public function getMenuItemClass(Page $page)
    {
        $classes = ['nav-link'];
        if (is_object($this->currentPage) && $page->getCollectionID() == $this->currentPage->getCollectionID()) {
            $classes[] = 'nav-selected';
            $classes[] = 'active';
        }
        if (in_array($page->getCollectionID(), $this->trail)) {
            $classes[] = 'nav-path-selected';
        }
        return implode($classes, ' ');
    }
    
    protected function getPageList($parent)
    {
        $list = new PageList();
        $list->filterByExcludeNav(false);
        $list->sortByDisplayOrder();
        $list->filterByParentID($parent->getCollectionID());
        return $list;
    }

    public function getChildPages($parent)
    {
        $list = $this->getPageList($parent);
        $pages = $list->getResults();
        return $pages;
    }

    public function view()
    {
        $pages = $this->getChildPages($this->startingParentPage);
        $this->set('top', $pages);
    }
}
