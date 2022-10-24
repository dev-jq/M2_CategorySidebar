<?php

namespace Elgentos\CategorySidebar\Block;

use Elgentos\CategorySidebar\Helper\Data;
use Magento\Catalog\Helper\Category;
use Magento\Catalog\Helper\Output;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Indexer\Category\Flat\State;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Tree\Node\Collection;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class CategorySidebar extends Template
{

    protected Category $_categoryHelper;
    protected Registry $_coreRegistry;
    protected State $categoryFlatConfig;
    protected CategoryFactory $_categoryFactory;
    protected Product\Collection $_productCollectionFactory;
    protected Output $helper;
    private Data $_dataHelper;
    protected $_scopeConfig;

    /**
     * @param Context $context
     * @param Category $categoryHelper
     * @param Registry $registry
     * @param State $categoryFlatState
     * @param CategoryFactory $categoryFactory
     * @param Product\Collection $productCollectionFactory
     * @param Output $helper
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Category             $categoryHelper,
        Registry             $registry,
        State                $categoryFlatState,
        CategoryFactory      $categoryFactory,
        Product\Collection   $productCollectionFactory,
        Output               $helper,
        Data                 $dataHelper,
        array $data = [ ]
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

    public function getCategories(bool $sorted = false, bool $asCollection = false, bool $toLoad = true): array
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

    public function getSelectedRootCategory(): bool
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
				$topLevelParentArray = explode("/", (string) $topLevelParent);
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

    public function getChildCategoryView($category, string $html = '', int $level = 2, $prefix = null): bool|string
    {
        $categorydepth = $this->getCatLevel();
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
                    if($this->getShowProductCount() && !$prefix) {
                        $categoryProductCount = ' <span class="product-count">(' . $this->getCategoryProductCount($childCategory->getId()) . ')</span>';
                    } else {
                        $categoryProductCount = '';
                    }

                    $html .= '<li class="level' . $level . ($this->isActive($childCategory) ? ' active' : ''). ($this->isEmptyCategory($childCategory->getId()) ? ' empty' : '') . '" data-cat-id="'. $childCategory->getId().'">';
                    if ($this->isEmptyCategory($childCategory->getId())) {
                        $html .= sprintf("<span%s title=\"%s\">%s%s</span>", $this->isActive($childCategory) ? ' class="is-active"' : '', __('Empty Category'), $childCategory->getName(), $categoryProductCount);
                    } else {
                        $html .= sprintf("<a href=\"%s\" title=\"%s\"%s>%s%s</a>", $this->getCategoryUrl($childCategory, $prefix), $childCategory->getName(), $this->isActive($childCategory) ? 'class="is-active"' : '', $childCategory->getName(), $categoryProductCount);
                    }

                    if ($childCategory->hasChildren())
                    {
                        $html .= $this->getChildCategoryView($childCategory, '', ($level + 1), $prefix);
                    }

                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
        }

        return $html;
    }

    public function getSubcategories($category): array
    {
        if ( $this->categoryFlatConfig->isFlatEnabled() && $category->getUseFlatResource() )
        {
            return (array)$category->getChildrenNodes();
        }

        return $category->getChildren();
    }

    public function isActive($category): bool
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
        return $this->getCategoryUrl($activeCategory) == $this->getCategoryUrl($category); // compare by URL
        //return (($category->getName() == $activeCategory->getName()) ? true : false); // compare by name
    }

    public function getCategoryUrl($category, $prefix = null): string
    {
        $fullUrl = $this->_categoryHelper->getCategoryUrl($category);
        if (is_null($prefix)) {
            return $fullUrl;
        }

        return str_replace($this->_storeManager->getStore()->getBaseUrl(), $prefix, $fullUrl);
    }

    public function isEmptyCategory($categoryId): bool
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

    public function getCategoryProductCount($categoryId): int
    {
        $category = $this->_categoryFactory->create()->load($categoryId);
        if($category){
            $categoryProducts = $category->getProductCollection()->addAttributeToSelect('*');
            return $categoryProducts->count();
        }
        return 0;
    }

    public function getCatTitle(): bool
    {
        return $this->_scopeConfig->getValue('categorysidebar/general/title');
    }

    public function getCatLevel(): int
    {
        return $this->_scopeConfig->getValue('categorysidebar/general/categorydepth');
    }

    public function getShowProductCount(): bool
    {
        return $this->_scopeConfig->getValue('categorysidebar/general/productcount');
    }

    public function getCurrentCategoryPath(): string
    {
        $activeCategory = $this->_coreRegistry->registry('current_category');

        if (!$activeCategory) {
            return '';
        }

        return $activeCategory->getPath();
    }

    public function getPathSegment($path = null, $segmentNumber = null): ?string
    {
        if ($path != null && $segmentNumber != null) {
            $segment = explode('/', (string) $path);
            return $segment[$segmentNumber - 1];
        }
        return null;
    }
}
