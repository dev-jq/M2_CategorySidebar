<?php


namespace Elgentos\CategorySidebar\Model\Config\Source;

use Magento\Catalog\Model\CategoryFactory;

class Categories implements \Magento\Framework\Option\ArrayInterface {

    protected array $_storeCategories = [];
    private CategoryFactory $_categoryFactory;


    public function __construct(CategoryFactory $categoryFactory)
    {
        $this->_categoryFactory = $categoryFactory;
    }

    public function toOptionArray(): array
    {
        $cacheKey = sprintf('%d-%d-%d-%d', 1, false, false, true);
        if (isset($this->_storeCategories[$cacheKey])) {
            return $this->_storeCategories[$cacheKey];
        }

        /**
         * Check if parent node of the store still exists
         */
        $category = $this->_categoryFactory->create();
        $storeCategories = $category->getCategories(1, $recursionLevel = 1, false, false, true);

        $this->_storeCategories[$cacheKey] = $storeCategories;

        $resultArray = [];
        foreach($storeCategories as $category) {
            $resultArray[$category->getId()] = __($category->getName());
        }

		$resultArray['current_category_children'] = __('Current Category Children');
		$resultArray['current_category_parent_children'] = __('Current Category Parent Children');
        $resultArray['current_category_branch_only'] = __('Only Branch of Current Category');

        return $resultArray;
    }
}
