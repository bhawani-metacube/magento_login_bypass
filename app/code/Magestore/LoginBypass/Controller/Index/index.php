<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\LoginBypass\Controller\Index;
use Magento\Customer\Model\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\Phrase;
use Magento\Framework\View\Result\PageFactory;


/**
 * Login form page. Accepts POST for backward compatibility reasons.
 */
class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     */
    
    protected $_customer;
    protected $_customerSession;
    protected $customerAccountManagement;
    protected $resultPageFactory;
    

    //    /**
//     * @var AccountRedirect
//     */
    protected $accountRedirect;
    
    private $scopeConfig;
    
        /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;
    
    public function __construct(
        Context $context,
            \Magento\Customer\Model\Customer $customer,
                         \Magento\Customer\Model\Session $customerSession,
            CustomerUrl $customerHelperData,
            AccountManagementInterface $customerAccountManagement,
            AccountRedirect $accountRedirect,
            PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_customer = $customer;
        $this->_customerSession = $customerSession;
        $this->customerUrl = $customerHelperData;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->accountRedirect = $accountRedirect;
        $this->resultPageFactory = $resultPageFactory;
    }
    
    
        /**
     * Retrieve cookie manager
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }
    
     private function getScopeConfig()
    {
        if (!($this->scopeConfig instanceof \Magento\Framework\App\Config\ScopeConfigInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\App\Config\ScopeConfigInterface::class
            );
        } else {
            return $this->scopeConfig;
        }
    }

    /**
     * Customer login form page
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($email = $this->getRequest()->getParam('email')) {
            if (!empty($email)) {
       // $email = "bhawani.shekhawat@metacube.com";
        try {
            $customer = $this->_customer->setWebsiteId(1)->loadByEmail($email); 
            $this->_customerSession->setCustomerAsLoggedIn($customer);
           // $this->_customerSession->setCustomerDataAsLoggedIn($customer);
            if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath('/');
                $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
            }
            
            $redirectUrl = $this->accountRedirect->getRedirectCookie();
            if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {
                $this->accountRedirect->clearRedirectCookie();
                $resultRedirect = $this->resultRedirectFactory->create();
                // URL is checked to be internal in $this->_redirect->success()
                $resultRedirect->setUrl($this->_redirect->success($redirectUrl));
                
                return $resultRedirect;
            }
        }catch (EmailNotConfirmedException $e) {
            $value = $this->customerUrl->getEmailConfirmationUrl($email);
            $message = __(
                'This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.',
                $value
            );
        } catch (UserLockedException $e) {
            $message = __(
                'The account sign-in was incorrect or your account is disabled temporarily. '
                . 'Please wait and try again later.'
            );
        } catch (AuthenticationException $e) {
            $message = __(
                'The account sign-in was incorrect or your account is disabled temporarily. '
                . 'Please wait and try again later.'
            );
        } catch (LocalizedException $e) {
            $message = $e->getMessage();
        } catch (\Exception $e) {
            // PA DSS violation: throwing or logging an exception here can disclose customer password
            $this->messageManager->addError(
                __('An unspecified error occurred. Please contact us for assistance.')
            );
        } finally {
            if (isset($message)) {
                $this->messageManager->addError($message);
                $this->_customerSession->setUsername($email);
            }
        }
        } else {
                $this->messageManager->addError(__('A login and a password are required.'));
            }
        }
        return $this->accountRedirect->getRedirect();
    }
    
}


/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

//namespace Magestore\LoginBypass\Controller\Index;
//
//use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
//use Magento\Customer\Model\Account\Redirect as AccountRedirect;
//use Magento\Framework\App\Action\Context;
//use Magento\Customer\Model\Session;
//use Magento\Customer\Api\AccountManagementInterface;
//use Magento\Customer\Model\Url as CustomerUrl;
//use Magento\Framework\App\CsrfAwareActionInterface;
//use Magento\Framework\App\Request\InvalidRequestException;
//use Magento\Framework\App\RequestInterface;
//use Magento\Framework\Controller\Result\Redirect;
//use Magento\Framework\Exception\EmailNotConfirmedException;
//use Magento\Framework\Exception\AuthenticationException;
//use Magento\Framework\Data\Form\FormKey\Validator;
//use Magento\Framework\Exception\LocalizedException;
//use Magento\Framework\Exception\State\UserLockedException;
//use Magento\Framework\App\Config\ScopeConfigInterface;
//use Magento\Customer\Controller\AbstractAccount;
//use Magento\Framework\Phrase;
//use Magento\Customer\Model\Customer;
//
///**
// * Post login customer action.
// *
// * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
// */
//class Index extends AbstractAccount implements CsrfAwareActionInterface, HttpPostActionInterface
//{
//    /**
//     * @var \Magento\Customer\Api\AccountManagementInterface
//     */
//    protected $customerAccountManagement;
//    protected $_customer;
//
//    /**
//     * @var \Magento\Framework\Data\Form\FormKey\Validator
//     */
//    protected $formKeyValidator;
//
//    /**
//     * @var AccountRedirect
//     */
//    protected $accountRedirect;
//
//    /**
//     * @var Session
//     */
//    protected $session;
//
//    /**
//     * @var ScopeConfigInterface
//     */
//    private $scopeConfig;
//
//    /**
//     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
//     */
//    private $cookieMetadataFactory;
//
//    /**
//     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
//     */
//    private $cookieMetadataManager;
//
//    /**
//     * @param Context $context
//     * @param Session $customerSession
//     * @param AccountManagementInterface $customerAccountManagement
//     * @param CustomerUrl $customerHelperData
//     * @param Validator $formKeyValidator
//     * @param AccountRedirect $accountRedirect
//     */
//    public function __construct(
//        Context $context,
//        Session $customerSession,
//        Customer $customer,
//        AccountManagementInterface $customerAccountManagement,
//        CustomerUrl $customerHelperData,
//        Validator $formKeyValidator,
//        AccountRedirect $accountRedirect
//    ) {
//        $this->session = $customerSession;
//        $this->customerAccountManagement = $customerAccountManagement;
//        $this->customerUrl = $customerHelperData;
//        $this->formKeyValidator = $formKeyValidator;
//        $this->accountRedirect = $accountRedirect;
//        $this->_customer = $customer;
//        parent::__construct($context);
//    }
//
//    /**
//     * Get scope config
//     *
//     * @return ScopeConfigInterface
//     * @deprecated 100.0.10
//     */
//    private function getScopeConfig()
//    {
//        if (!($this->scopeConfig instanceof \Magento\Framework\App\Config\ScopeConfigInterface)) {
//            return \Magento\Framework\App\ObjectManager::getInstance()->get(
//                \Magento\Framework\App\Config\ScopeConfigInterface::class
//            );
//        } else {
//            return $this->scopeConfig;
//        }
//    }
//
//    /**
//     * Retrieve cookie manager
//     *
//     * @deprecated 100.1.0
//     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
//     */
//    private function getCookieManager()
//    {
//        if (!$this->cookieMetadataManager) {
//            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
//                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
//            );
//        }
//        return $this->cookieMetadataManager;
//    }
//
//    /**
//     * Retrieve cookie metadata factory
//     *
//     * @deprecated 100.1.0
//     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
//     */
//    private function getCookieMetadataFactory()
//    {
//        if (!$this->cookieMetadataFactory) {
//            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
//                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
//            );
//        }
//        return $this->cookieMetadataFactory;
//    }
//
//    /**
//     * @inheritDoc
//     */
//    public function createCsrfValidationException(
//        RequestInterface $request
//    ): ?InvalidRequestException {
//        /** @var Redirect $resultRedirect */
//        $resultRedirect = $this->resultRedirectFactory->create();
//        $resultRedirect->setPath('*/*/');
//
//        return new InvalidRequestException(
//            $resultRedirect,
//            [new Phrase('Invalid Form Key. Please refresh the page.')]
//        );
//    }
//
//    /**
//     * @inheritDoc
//     */
//    public function validateForCsrf(RequestInterface $request): ?bool
//    {
//        return null;
//    }
//
//    /**
//     * Login post action
//     *
//     * @return \Magento\Framework\Controller\Result\Redirect
//     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
//     */
//    public function execute()
//    {
//        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($this->getRequest())) {
//            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
//            $resultRedirect = $this->resultRedirectFactory->create();
//            $resultRedirect->setPath('*/*/');
//            return $resultRedirect;
//        }
//
//       // if ($this->getRequest()->isPost()) {
//           // $login = $this->getRequest()->getPost('login');
//           // if (!empty($login['username']) && !empty($login['password'])) {
//                try {
//                    $customer = $this->_customer->loadByEmail("roni_cost@example.com"); 
//                    //$customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
//                    $this->session->setCustomerDataAsLoggedIn($customer);
//                    if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
//                        $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
//                        $metadata->setPath('/');
//                        $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
//                    }
//                    $redirectUrl = $this->accountRedirect->getRedirectCookie();
//                    if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {
//                        $this->accountRedirect->clearRedirectCookie();
//                        $resultRedirect = $this->resultRedirectFactory->create();
//                        // URL is checked to be internal in $this->_redirect->success()
//                        $resultRedirect->setUrl($this->_redirect->success($redirectUrl));
//                        return $resultRedirect;
//                    }
//                } catch (EmailNotConfirmedException $e) {
//                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
//                    $message = __(
//                        'This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.',
//                        $value
//                    );
//                } catch (UserLockedException $e) {
//                    $message = __(
//                        'The account sign-in was incorrect or your account is disabled temporarily. '
//                        . 'Please wait and try again later.'
//                    );
//                } catch (AuthenticationException $e) {
//                    $message = __(
//                        'The account sign-in was incorrect or your account is disabled temporarily. '
//                        . 'Please wait and try again later.'
//                    );
//                } catch (LocalizedException $e) {
//                    $message = $e->getMessage();
//                } catch (\Exception $e) {
//                    // PA DSS violation: throwing or logging an exception here can disclose customer password
//                    $this->messageManager->addError(
//                        __('An unspecified error occurred. Please contact us for assistance.')
//                    );
//                } finally {
//                    if (isset($message)) {
//                        $this->messageManager->addError($message);
//                        $this->session->setUsername($login['username']);
//                    }
//                }
////            } else {
////                $this->messageManager->addError(__('A login and a password are required.'));
////            }
//      //  }
//
//        return $this->accountRedirect->getRedirect();
//    }
//}

