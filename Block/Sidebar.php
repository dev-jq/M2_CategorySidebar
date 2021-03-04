<?php namespace Sebwite\Sidebar\Block;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\View\Element\Template;

/**
 * Class:Sidebar
 * Sebwite\Sidebar\Block
 *
 * @author      Sebwite
 * @package     Sebwite\Sidebar
 * @copyright   Copyright (c) 2015, Sebwite. All rights reserved
 */
class Sidebar extends Template
{

    /** * @var \Magento\Catalog\Helper\Category */
    protected $_categoryHelper;

    /** * @var \Magento\Framework\Registry */
    protected $_coreRegistry;

    /** * @var \Magento\Catalog\Model\Indexer\Category\Flat\State */
    protected $categoryFlatConfig;

    /** * @var \Magento\Catalog\Model\CategoryFactory */
    protected $_categoryFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection */
    protected $_productCollectionFactory;
	
    /** @var \Magento\Catalog\Helper\Output */
    private $helper;

	/** @var \Sebwite\Sidebar\Helper\Data */
    private $_dataHelper;

	/** @var \Magento\Framework\App\ObjectManager */
	private  $objectManager;
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
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollectionFactory,
        \Magento\Catalog\Helper\Output $helper,
		\Sebwite\Sidebar\Helper\Data $dataHelper,
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
		
		$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        parent::__construct($context, $data);
		setlocale(LC_ALL, 'pl_PL');
    }
	
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

		$sorted = array();
			
		foreach ( $storeCategories as $childCategory )
		{
			$sorted[$childCategory->getName()] = $childCategory;
		}
			
		ksort($sorted, SORT_LOCALE_STRING );
		
        $this->_storeCategories[ $cacheKey ] = $sorted;

        return $sorted;
    }

    /**
     * getSelectedRootCategory method
     *
     * @return int|mixed
     */
    public function getSelectedRootCategory()
    {
		return 3;
        $category = $this->_scopeConfig->getValue(
            'sebwite_sidebar/general/category'
        );

		if ( $category == 'current_category_children'){
			$currentCategory = $this->_coreRegistry->registry('current_category');
			if($currentCategory){
				return $currentCategory->getId();
			}
			return 1;
		}
		
		if ( $category == 'current_category_parent_children'){
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
		
        if ( $category === null )
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
    public function getChildCategoryView($category, $html = '', $level = 1)
    {
		$childCategories = $category->getChildrenCategories();
		
        // Check if category has children
        if ( !empty($childCategories) )
        {
			$sorted = array();
			
			foreach ( $childCategories as $childCategory )
			{
				$sorted[$childCategory->getName()] = $childCategory;
			}
			
			ksort($sorted, SORT_LOCALE_STRING );
			
            if ( count($sorted) > 0 )
            {
                $html .= '<ul class="o-list o-list--unstyled">';

                // Loop through children categories
                foreach ( $sorted as $childCategory )
                {
                    $html .= '<li class="level' . $level . ($this->isActive($childCategory) ? ' active' : '') . '">';
                    $html .= '<a href="' . $this->getCategoryUrl($childCategory) . '" title="' . $childCategory->getName() . '" class="' . ($this->isActive($childCategory) ? 'is-active' : '') . '">' . $childCategory->getName() . '</a>';

                    if ( !empty($childCategory->getChildrenCategories()) )
                    {
                        if ( $this->isActive($childCategory) )
                        {
                            $html .= '<span class="expanded"><i class="fa fa-angle-down"></i></span>';
                        }
                        else
                        {
                            $html .= '<span class="expand"><i class="fa fa-angle-up"></i></span>';
                        }
						
                        $html .= $this->getChildCategoryView($childCategory, '', ($level + 1));
                    }

                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
			else
			{
				$html .= '<span style="">!</span>';
			}
        }
		else
		{
			$html .= '<span style="">*</span>';
		}

        return $html;
    }

    /**
     * Retrieve subcategories
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
        return (($category->getName() == $activeCategory->getName()) ? true : false);
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

    /**
     * Return Is Enabled config option
     *
     * @return string
     */
    public function isEnabled()
    {
        return $this->_dataHelper->isEnabled();
    }

    /**
     * Return Title Text for menu
     *
     * @return string
     */
    public function getTitleText()
    {
        return $this->_dataHelper->getTitleText();
    }

    /**
     * Return Menu Open config option
     *
     * @return string
     */
    public function isOpenOnLoad()
    {
        return $this->_dataHelper->isOpenOnLoad();
    }
}
