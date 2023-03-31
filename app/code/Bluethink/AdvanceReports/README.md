# Mage2 Module Bluethink AdvanceReports

    ``bluethink/module-advancereports``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Bluethink Advance Reports

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Bluethink`
 - Enable the module by running `php bin/magento module:enable Bluethink_AdvanceReports`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require bluethink/module-advancereports`
 - enable the module by running `php bin/magento module:enable Bluethink_AdvanceReports`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

 - enable_disable (bluethink/advance_reports/enable_disable)


## Specifications

 - Controller
	- adminhtml > bluethink_advancereports/index/index

 - Controller
	- frontend > bluethink_advancereports/index/salesOverview

 - Controller
	- frontend > bluethink_advancereports/index/salesDetaild

 - Controller
	- frontend > bluethink_advancereports/index/salesByCategory

 - Controller
	- frontend > bluethink_advancereports/index/salesByProductAttributes


## Attributes



