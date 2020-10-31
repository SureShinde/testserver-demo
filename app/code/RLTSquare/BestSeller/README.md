# BestSellingProducts-Magento2

# Overview

The Best Selling Products extension for Magento 2 has been developed by the product team at RLTSquare. This extension displays your best selling products on the home page and other pages that you want so customers can view and easily buy them if they want. We have implemented AJAX Lazy Load on the slider to decrease the page load time of the website and to fetch only those images or thumbnails from the database that fits on the screen size of the webpage. We can exclude or include out of stock products. Our extension provides many options for settings in the admin panel.

Here are some of the salient features for the extension:

```
1. Shows the bestsellers in your store
2. Apply AJAX Lazy Load on slider where all the products are displayed
3. Visible on Home, Product and Category Page
4. Exclude or include out of stock products from the slider
5. Effectively display bestseller products in the slider or grid layout.
6. You can set best seller products limit
7. Enable/Disable auto scroll products in slider
```

## Installation

### Magento® Marketplace

This extension will also be available on the Magento® Marketplace when approved.

### Manually

1. Go to Magento® 2 root folder

2. Require/Download this extension:

   Enter following commands to install extension.

   ```
   composer require rltsquare/best-seller
   ```

   Wait while composer is updated.
   
   #### OR
   
   You can also download code from this repo under Magento® 2 following directory:
    
    ```
    app/code/RLTSquare/BestSeller
    ```

3. Enter following commands to enable the module:

   ```
   php bin/magento module:enable RLTSquare_BestSeller
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   php bin/magento cache:flush
   ```

4. If Magento® is running in production mode, deploy static content: 

   ```
   php bin/magento setup:static-content:deploy
   ```


## Requirements

1. This Magento® extension works on Magento 2.2 and 2.3 versions. Tested on versions 2.2.5 and above.

2. Tested on different themes specifically Ultimo, Porto and certain custom themes

For details, read our blog:
https://www.rltsquare.com/blog/best-selling-products-magento-2-extension
