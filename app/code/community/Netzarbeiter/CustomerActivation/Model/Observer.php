<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * package    Netzarbeiter_CustomerActivation
 * copyright  Copyright (c) 2013 Vinai Kopp http://netzarbeiter.com/
 * license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Netzarbeiter_CustomerActivation_Model_Observer
{
    const XML_PATH_MODULE_DISABLED = 'customer/customeractivation/disable_ext';

    const XML_PATH_ALWAYS_NOTIFY_ADMIN = 'customer/customeractivation/always_send_admin_email';

    /**
     * Fired on customer_login event
     * Check if the customer has been activated (via adminhtml)
     * If not, through login error
     *
     * @param Varien_Event_Observer $observer
     */
    public function customerLogin($observer)
    {
        if (Mage::getStoreConfig(self::XML_PATH_MODULE_DISABLED)) {
            return;
        }

        if ($this->_isApiRequest()) {
            return;
        }

        $customer = $observer->getEvent()->getCustomer();
        $session = Mage::getSingleton('customer/session');

        if (!$customer->getCustomerActivated()) {
            /*
             * Fake the old logout() method without deleting the session and all messages
             */
            $session->setCustomer(Mage::getModel('customer/customer'))
                ->setId(null)
                ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);

            if ($this->_checkRequestRoute('customer', 'account', 'createpost')) {
                /*
                 * If this is a regular registration, simply display message
                 */
                $message = Mage::helper('customeractivation')->__('Please wait for your account to be activated');

                $session->addSuccess($message);
            } else {
                /*
                 * All other types of login
                 */
                Mage::throwException(Mage::helper('customeractivation')->__('This account is not activated.'));
            }
        }
    }

    /**
     * Flag new accounts as such
     *
     * @param Varien_Event_Observer $observer
     */
    public function customerSaveBefore($observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        $storeId = Mage::helper('customeractivation')->getCustomerStoreId($customer);

        if (Mage::getStoreConfig(self::XML_PATH_MODULE_DISABLED, $storeId)) {
            return;
        }

        if (!$customer->getId()) {
            $customer->setCustomerActivationNewAccount(true);
            if (! (Mage::app()->getStore()->isAdmin() && $this->_checkControllerAction('customer', 'save'))) {
                // Do not set the default status on the admin customer edit save action
                $groupId = $customer->getGroupId();
                $defaultStatus = Mage::helper('customeractivation')->getDefaultActivationStatus($groupId, $storeId);
                $customer->setCustomerActivated($defaultStatus);
                
                if (! $defaultStatus) {
                    // Suppress the "enter your billing address for VAT validation" message.
                    // This setting will not be saved, its just for this request.
                    $helper = Mage::helper('customer/address');
                    if (method_exists($helper, 'isVatValidationEnabled')) {
                        if (is_callable(array($helper, 'isVatValidationEnabled'))) {
                            if (Mage::helper('customer/address')->isVatValidationEnabled($storeId)) {
                                Mage::app()->getStore($storeId)->setConfig(
                                    Mage_Customer_Helper_Address::XML_PATH_VAT_VALIDATION_ENABLED, false
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Send out emails
     *
     * @param Varien_Event_Observer $observer
     */
    public function customerSaveAfter($observer)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $observer->getEvent()->getCustomer();

        $storeId = Mage::helper('customeractivation')->getCustomerStoreId($customer);

        if (Mage::getStoreConfig(self::XML_PATH_MODULE_DISABLED, $storeId)) {
            return;
        }

        $groupId = $customer->getGroupId();
        $defaultStatus = Mage::helper('customeractivation')->getDefaultActivationStatus($groupId, $storeId);

        try {
            if (Mage::app()->getStore()->isAdmin()) {
                if (!$customer->getOrigData('customer_activated') && $customer->getCustomerActivated()) {
                    // Send customer email only if it isn't a new account and it isn't activated by default
                    if (!($customer->getCustomerActivationNewAccount() && $defaultStatus)) {
                        Mage::helper('customeractivation')->sendCustomerNotificationEmail($customer);
                    }
                }
            } else {
                if ($customer->getCustomerActivationNewAccount()) {
                    // Only notify the admin if the default is deactivated or the "always notify" flag is configured
                    $alwaysNotify = Mage::getStoreConfig(self::XML_PATH_ALWAYS_NOTIFY_ADMIN, $storeId);
                    if (!$defaultStatus || $alwaysNotify) {
                        Mage::helper('customeractivation')->sendAdminNotificationEmail($customer);
                    }
                }
                $customer->setCustomerActivationNewAccount(false);
            }
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    public function salesCovertQuoteAddressToOrder(Varien_Event_Observer $observer)
    {
        /** @var $address Mage_Sales_Model_Quote_Address */
        $address = $observer->getEvent()->getAddress();
        $this->_abortCheckoutRegistration($address->getQuote());
    }

    /**
     * Abort registration during checkout if default activation status is false.
     *
     * Should work with: onepage checkout, multishipping checkout and custom
     * checkout types, as long as they use the standard converter model
     * Mage_Sales_Model_Convert_Quote.
     *
     * Expected state after checkout:
     * - Customer saved
     * - No order placed
     * - Guest quote still contains items
     * - Customer quote contains no items
     * - Customer redirected to login page
     * - Customer sees message
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    protected function _abortCheckoutRegistration(Mage_Sales_Model_Quote $quote)
    {
        if (Mage::getStoreConfig(self::XML_PATH_MODULE_DISABLED, $quote->getStoreId())) {
            return;
        }

        if ($this->_isApiRequest()) {
            return;
        }

        if (!Mage::getSingleton('customer/session')->isLoggedIn() && !$quote->getCustomerIsGuest()) {
            // Order is being created by non-activated customer
            $customer = $quote->getCustomer()->save();
            if (!$customer->getCustomerActivated()) {
                // Abort order placement
                // Exception handling can not be assumed to be useful

                // Todo: merge guest quote to customer quote and save customer quote, but don't log customer in

                // Add message
                $message = Mage::helper('customeractivation')->__(
                    'Please wait for your account to be activated, then log in and continue with the checkout'
                );
                Mage::getSingleton('core/session')->addSuccess($message);

                // Handle redirect to login page
                $targetUrl = Mage::getUrl('customer/account/login');
                $response = Mage::app()->getResponse();

                if (Mage::app()->getRequest()->isAjax()) {
                    // Assume one page checkout
                    $result = array('redirect' => $targetUrl);
                    $response->setBody(Mage::helper('core')->jsonEncode($result));
                } else if ($response->canSendHeaders(true)) {
                    // Assume multishipping checkout
                    $response->clearHeader('location')
                            ->setRedirect($targetUrl);
                }
                $response->sendResponse();
                /* ugly, but we need to stop the further order processing */
                exit();
            }
        }
    }

    /**
     * Return true if the reqest is made via the api
     *
     * @return boolean
     */
    protected function _isApiRequest()
    {
        return Mage::app()->getRequest()->getModuleName() === 'api';
    }

    /**
     * Check the current module, controller and action against the given values.
     *
     * @param string $module
     * @param string $controller
     * @param string $action
     * @return bool
     */
    protected function _checkRequestRoute($module, $controller, $action)
    {
        $req = Mage::app()->getRequest();
        if (strtolower($req->getModuleName()) == $module
                && strtolower($req->getControllerName()) == $controller
                && strtolower($req->getActionName()) == $action
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check the current controller and action match the passed names
     *
     * @param string $controller
     * @param string $action
     * @return bool
     */
    protected function _checkControllerAction($controller, $action)
    {
        $req = Mage::app()->getRequest();
        return $this->_checkRequestRoute($req->getModuleName(), $controller, $action);
    }

    /**
     * Add customer activation option to the mass action block.
     *
     * This can't be done during the block abstract e
     *
     * @param Varien_Event_Observer $observer
     */
    public function adminhtmlBlockHtmlBefore(Varien_Event_Observer $observer)
    {
        // Check the grid is the customer grid
        if ($observer->getBlock()->getId() != 'customerGrid') {
            return;
        }

        // Check if there is a massaction block and if yes, add the massaction for customeractivation
        $massBlock = $observer->getBlock()->getMassactionBlock();
        if ($massBlock) {
            /** @var $helper Netzarbeiter_CustomerActivation_Helper_Data */
            $helper = Mage::helper('customeractivation');

            $noEmail = Netzarbeiter_CustomerActivation_Helper_Data::STATUS_ACTIVATE_WITHOUT_EMAIL;
            $withEmail = Netzarbeiter_CustomerActivation_Helper_Data::STATUS_ACTIVATE_WITH_EMAIL;
            $deactivate = Netzarbeiter_CustomerActivation_Helper_Data::STATUS_DEACTIVATE;

            $massBlock->addItem(
                'customer_activated',
                array(
                    'label' => $helper->__('Customer Activated'),
                    'url' => Mage::getUrl('customeractivation/admin/massActivation'),
                    'additional' => array(
                        'status' => array(
                            'name' => 'customer_activated',
                            'type' => 'select',
                            'class' => 'required-entry',
                            'label' => $helper->__('Customer Activated'),
                            'values' => array(
                                $noEmail => $helper->__('Yes (No Notification)'),
                                $withEmail => $helper->__('Yes (With Notification)'),
                                $deactivate => $helper->__('No')
                            )
                        )
                    )
                )
            );
        }
    }

    /**
     * Add the customer_activated attribute to the customer grid collection
     *
     * @param Varien_Event_Observer $observer
     */
    public function eavCollectionAbstractLoadBefore(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig(self::XML_PATH_MODULE_DISABLED)) {
            return;
        }

        // Cheap check to reduce overhead on product and category collections
        if (Mage::app()->getRequest()->getControllerName() !== 'customer') {
            return;
        }

        /** @var $collection Mage_Customer_Model_Resource_Customer_Collection */
        $collection = $observer->getEvent()->getCollection();

        // Only add attribute to customer collections
        $customerTypeId = Mage::getSingleton('eav/config')->getEntityType('customer')->getId();
        $collectionTypeId = $collection->getEntity()->getTypeId();
        if ($customerTypeId == $collectionTypeId) {
            $collection->addAttributeToSelect('customer_activated');

        }
    }

    /**
     * Add customer_activated column to CSV and XML exports
     *
     * @param Varien_Event_Observer $observer
     */
    public function coreBlockAbstractPrepareLayoutAfter(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig(self::XML_PATH_MODULE_DISABLED)) {
            return;
        }

        // Only add column to grids rendered by the adminhtml customer controller
        if (Mage::app()->getRequest()->getControllerName() !== 'customer') {
            return;
        }

        $block = $observer->getBlock();
        if ($block->getType() === 'adminhtml/customer_grid') {
            // I don't think we need to limit applying the column by action
            //$action = Mage::app()->getRequest()->getActionName();
            //if (in_array($action, array('grid', 'index', 'exportCsv', 'exportXml'))) {

                $this->_addActivationStatusColumn($block);
            
            //}
        }
    }

    /**
     * Add the activation status column to the customer grid block.
     *
     * This is used from different events when displaying the block as well as
     * during exporting the grid to CSV or XML.
     *
     * @param Mage_Adminhtml_Block_Customer_Grid $block
     */
    protected function _addActivationStatusColumn(Mage_Adminhtml_Block_Customer_Grid $block)
    {
        /** @var $helper Netzarbeiter_CustomerActivation_Helper_Data */
        $helper = Mage::helper('customeractivation');

        // Add the attribute as a column to the grid
        $block->addColumnAfter(
            'customer_activated',
            array(
                'header' => $helper->__('Customer Activated'),
                'align' => 'center',
                'width' => '80px',
                'type' => 'options',
                'options' => array(
                    '0' => $helper->__('No'),
                    '1' => $helper->__('Yes')
                ),
                'default' => '0',
                'index' => 'customer_activated',
                'renderer' => 'customeractivation/adminhtml_widget_grid_column_renderer_boolean'
            ),
            'customer_since'
        );

        // Set the new columns order.. otherwise our column would be the last one
        $block->sortColumnsByOrder();
    }
}
