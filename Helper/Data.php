<?php

namespace Elgentos\CategorySidebar\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLED                      = 'general/enabled';
    const XML_PATH_CATEGORY                     = 'general/category';
    const XML_PATH_CATEGORY_DEPTH_LEVEL         = 'general/categorydepth';

    protected ScopeConfigInterface $_scopeConfig;
    protected $scopeConfig;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }


    public function getConfigPath($xmlPath, string $section = 'categorysidebar'): string
    {
        return $section . '/' . $xmlPath;
    }

    public function isEnabled(): ?string
    {
        return $this->scopeConfig->getValue(
            $this->getConfigPath(self::XML_PATH_ENABLED),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	 /**
     * Get Category CategorySidebar
     *
     * @return string|null
     */
    public function getSidebarCategory(): ?string
    {
        return $this->scopeConfig->getValue(
            $this->getConfigPath(self::XML_PATH_CATEGORY),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	 /**
     * Get category depth level
     *
     * @return string|null
     */
    public function getCategoryDepthLevel(): ?string
    {
        return $this->scopeConfig->getValue(
            $this->getConfigPath(self::XML_PATH_CATEGORY_DEPTH_LEVEL),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

}
