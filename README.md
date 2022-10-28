# Category Sidebar for Magento 2 

## Installation with composer
* Include the repository: `composer require elgentos/magento2-category-sidebar`
* Enable the extension: `php bin/magento --clear-static-content module:enable Elgentos_CategorySidebar`
* Upgrade db scheme: `php bin/magento setup:upgrade`
* Clear cache

## Installation without composer
* Download zip file of this extension
* Place all the files of the extension in your Magento 2 installation in the folder `app/code/Elgentos/CategorySidebar`
* Enable the extension: `php bin/magento --clear-static-content module:enable Elgentos_CategorySidebar`
* Upgrade db scheme: `php bin/magento setup:upgrade`
* Clear cache

## Configuration
* Enable module 
* Decide whether to show the title of the "Categories" block
* Select the type of category tree ( Default Category / Current Category Children / Current Category Parent Children / Only Branch of Current Category )
* You can show (or hide) the number of products in a given category, e.g. Jackets (20)
* Select children depth level (1 - 5)
* Categories will appear in col.left sidebar of the theme

![CategorySidebar Search Results page](https://github.com/elgentos/magento2-category-sidebar/blob/master/README-assets/config.png)
