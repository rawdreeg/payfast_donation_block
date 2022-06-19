# payfast_donation_block

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

A very minimal module that provide a block from to help sites accept donations on their drupal site with payfast.


 * For a full description of the module, visit the project page:
   TBC

 * To submit bug reports and feature suggestions, or to track changes:
   https://github.com/rawdreeg/payfast_donation_block/issues


REQUIREMENTS
------------

This module requires the [PayFast PHP SDK](https://github.com/PayFast/payfast-php-sdk) as a dependency


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.
 
 * Configure the module.


CONFIGURATION
-------------

    1. Navigate to "Adminstration > Extend" and enable module.
    2. Navigate to "Adminstration > Configuration > Web Services > Payfast Donation Block.
        1. Configure Payfast API.
        2. Configure redirect links.
    3. Navigate to Structure -> Block Layout and place "Payfast Donation Block" block to your page.
    4. The payment form can also be access on the /pdb/donate path
    

TO-DO
------------

- [x] Payment form
- [x] Onsite payment mode
- [x] External payment mode
- [x] Handle itn route.
- [x] Log payment.
