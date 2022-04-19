<?php

/**
 * @author JQ
 * @copyright Copyright (c) 2022 JQ
 * @package JQ_CategorySidebar
*/

namespace JQ\CategorySidebar\Block;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\View\Element\Template;

/**
 * Class:CategorySidebar
 * JQ\CategorySidebar\Block
 *
 * @author      JQ
 * @package     JQ\CategorySidebar
 * @copyright   Copyright (c) 2022, JQ. All rights reserved
 */
class CategorySidebar extends Template
{

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_categoryHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Catalog\Model\Indexer\Category\Flat\State
     */
    protected $categoryFlatConfig;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection */
    protected $_productCollectionFactory;

    /** @var \Magento\Catalog\Helper\Output */
    private $helper;

    /**
     * @param Template\Context                                        $context
     * @param \Magento\Catalog\Helper\Category                        $categoryHelper
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Catalog\Model\Indexer\Category\Flat\State      $categoryFlatState
     * @param \Magento\Catalog\Model\CategoryFactory                  $categoryFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollectionFactory
     * @param \Magento\Catalog\Helper\Output                          $helper
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollectionFactory,
        \Magento\Catalog\Helper\Output $helper,
		\JQ\CategorySidebar\Helper\Data $dataHelper,
        $data = [ ]
    )
    {
        $this->_categoryHelper           = $categoryHelper;
        $this->_coreRegistry             = $registry;
        $this->categoryFlatConfig        = $categoryFlatState;
        $this->_categoryFactory          = $categoryFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->helper                    = $helper;
		$this->_dataHelper = $dataHelper;

        parent::__construct($context, $data);
    }

    /*
    * Get owner name
    * @return string
    */

    /**
     * Get all categories
     *
     * @param bool $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Category\Collection|\Magento\Framework\Data\Tree\Node\Collection
     */
    public function getCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        $status = $this->_scopeConfig->getValue('categorysidebar/general/enabled');
        if($status){
            $cacheKey = sprintf('%d-%d-%d-%d', $this->getSelectedRootCategory(), $sorted, $asCollection, $toLoad);
            if ( isset($this->_storeCategories[ $cacheKey ]) )
            {
                return $this->_storeCategories[ $cacheKey ];
            }

            /**
             * Check if parent node of the store still exists
             */
            $category = $this->_categoryFactory->create();

    		$categoryDepthLevel = $this->_dataHelper->getCategoryDepthLevel();

            $storeCategories = $category->getCategories($this->getSelectedRootCategory(), $recursionLevel = $categoryDepthLevel, $sorted, $asCollection, $toLoad);

            $this->_storeCategories[ $cacheKey ] = $storeCategories;

            return $storeCategories;
        } else {
            return array();
        }
    }

    /**
     * getSelectedRootCategory method
     *
     * @return int|mixed
     */
    public function getSelectedRootCategory()
    {
        $category = $this->_scopeConfig->getValue('categorysidebar/general/category');

		if ($category == 'current_category_children'){
			$currentCategory = $this->_coreRegistry->registry('current_category');
			if($currentCategory){
				return $currentCategory->getId();
			}
			return 1;
		}

		if ($category == 'current_category_parent_children'){
			$currentCategory = $this->_coreRegistry->registry('current_category');
			if($currentCategory){
				$topLevelParent = $currentCategory->getPath();
				$topLevelParentArray = explode("/", $topLevelParent);
				if(isset($topLevelParent)){
					return $topLevelParentArray[2];
				}
			}
			return 1;
		}

		if ($category == 'current_category_branch_only') {
            return 2;
        }

        if ($category === null)
        {
            return 1;
        }

        return $category;
    }

    /**
     * @param        $category
     * @param string $html
     * @param int    $level
     *
     * @return string
     */
    public function getChildCategoryView($category, $html = '', $level = 2)
    {
        $categorydepth = $this->getCatLavel();
        if($level > $categorydepth){
           return false;
        }
        // Check if category has children
        if ( $category->hasChildren() )
        {

            $childCategories = $this->getSubcategories($category);

            if (is_object($childCategories) && count($childCategories) > 0 ){

                $currentCategoryId = $category->getId();

                $html .= '<ul class="cat-list">';

                // Loop through children categories
                foreach ($childCategories as $childCategory)
                {
                    if($this->getShowProductCount()) {
                        $categoryProductCount = ' <span class="product-count">(' . $this->getCategoryProductCount($childCategory->getId()) . ')</span>';
                    } else {
                        $categoryProductCount = '';
                    }

                    $html .= '<li class="level' . $level . ($this->isActive($childCategory) ? ' active' : ''). ($this->isEmptyCategory($childCategory->getId()) ? ' empty' : '') . '" data-cat-id="'. $childCategory->getId().'">';
                    if ($this->isEmptyCategory($childCategory->getId())) {
                        $html .= '<span' . ($this->isActive($childCategory) ? ' class="is-active"' : '') . ' title="'.__('Empty Category').'">' . $childCategory->getName() . $categoryProductCount . '</span>';
                    } else {
                        $html .= '<a href="' . $this->getCategoryUrl($childCategory) . '" title="' . $childCategory->getName() . '"' . ($this->isActive($childCategory) ? 'class="is-active"' : '') . '>' . $childCategory->getName() . $categoryProductCount . '</a>';
                    }

                    if ($childCategory->hasChildren())
                    {
                        $html .= $this->getChildCategoryView($childCategory, '', ($level + 1));
                    }

                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
        }

        return $html;
    }

    /**
     * Retrieve subcategories
     * DEPRECATED
	 *
     * @param $category
     *
     * @return array
     */

    public function getSubcategories($category)
    {
        if ( $this->categoryFlatConfig->isFlatEnabled() && $category->getUseFlatResource() )
        {
            return (array)$category->getChildrenNodes();
        }

        return $category->getChildren();
    }


    /**
     * Get current category
     *
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return Category
     */
    public function isActive($category)
    {
        $activeCategory = $this->_coreRegistry->registry('current_category');
        $activeProduct  = $this->_coreRegistry->registry('current_product');

        if ( !$activeCategory )
        {

            // Check if we're on a product page
            if ( $activeProduct !== null )
            {
                return in_array($category->getId(), $activeProduct->getCategoryIds());
            }

            return false;
        }

        // Check if this is the active category
        if ( $this->categoryFlatConfig->isFlatEnabled() && $category->getUseFlatResource() AND
            $category->getId() == $activeCategory->getId()
        )
        {
            return true;
        }

        // Check if a subcategory of this category is active
        $childrenIds = $category->getAllChildren(true);
        if ( !is_null($childrenIds) AND in_array($activeCategory->getId(), $childrenIds) )
        {
            return true;
        }

        // Fallback - If Flat categories is not enabled the active category does not give an id
        return (($this->getCategoryUrl($activeCategory) == $this->getCategoryUrl($category)) ? true : false); // compare by URL
        //return (($category->getName() == $activeCategory->getName()) ? true : false); // compare by name
    }

    /**
     * Return Category Id for $category object
     *
     * @param $category
     *
     * @return string
     */
    public function getCategoryUrl($category)
    {
        return $this->_categoryHelper->getCategoryUrl($category);
    }

    public function isEmptyCategory($categoryId)
    {
        $category = $this->_categoryFactory->create()->load($categoryId);
        if($category){
            $categoryProducts = $category->getProductCollection()->addAttributeToSelect('*');
            if ($categoryProducts->count() > 0) {
                return false;
            } else {
                return true;
            }
        }
    }

    public function getCategoryProductCount($categoryId)
    {
        $category = $this->_categoryFactory->create()->load($categoryId);
        if($category){
            $categoryProducts = $category->getProductCollection()->addAttributeToSelect('*');
            return $categoryProducts->count();
        }
        return 0;
    }

    public function getCatTitle()
    {
        return $this->_scopeConfig->getValue('categorysidebar/general/title');
    }

    public function getCatLavel()
    {
        return $this->_scopeConfig->getValue('categorysidebar/general/categorydepth');
    }

    public function getShowProductCount()
    {
        return $this->_scopeConfig->getValue('categorysidebar/general/productcount');
    }

    public function getCurrentCategoryPath() {
        $activeCategory = $this->_coreRegistry->registry('current_category');

        if (!$activeCategory) {
            return '';
        }

        return $activeCategory->getPath();
    }

    public function getPathSegment($path = null, $segmentNumber = null) {
        if ($path != null && $segmentNumber != null) {
            $segment = explode('/', $path);
            return $segment[$segmentNumber - 1];
        }
        return null;
    }
}
