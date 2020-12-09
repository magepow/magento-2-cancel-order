<?php

/**
 * @Author: Ha Manh
 * @Date:   2020-12-08 08:29:17
 * @Last Modified by:   Ha Manh
 * @Last Modified time: 2020-12-09 09:51:08
 */

namespace Magepow\CancelOrder\Controller\Cancelorder;

use Magento\Framework\Controller\ResultFactory;

use Magento\Framework\Mail\Template\TransportBuilder;

class Index extends \Magento\Framework\App\Action\Action
{


    protected $resultPageFactory;
    protected $_order;
    protected $customer;
    private $transportBuilder;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\Order $order,
        \Magento\Customer\Model\Session $customerSession,
        TransportBuilder $transportBuilder,
        array $data = [])
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_order = $order;
        $this->_customerSession = $customerSession;
        $this->transportBuilder = $transportBuilder;
        return parent::__construct($context,$data);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderId = $this->getRequest()->getParam('orderid');
        $order = $this->_order->load($orderId);
        if($order->canCancel()){
            $order->cancel();
            $order->save();
            $this->messageManager->addSuccess(__('Order has been canceled successfully.'));
            $customerData = $this->_customerSession->getCustomer(); //Get Current Customer Data
            $emailTemplateVariables = array();
            $emailTempVariables = [
                'orderid' => $order->getId()
            ];
            $senderName = $customerData->getName();
            $senderEmail = $customerData->getEmail();
            $postObject = new \Magento\Framework\DataObject();
            $postObject->setData($emailTempVariables);
            $sender = [
                        'name' => $senderName,
                        'email' => $senderEmail,
                        ];
            $transport = $this->transportBuilder->setTemplateIdentifier('cancel_order_email_template')
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
            ->setTemplateVars(['data' => $postObject])
            ->setFrom($sender)
            ->addTo($senderEmail)
            ->setReplyTo($senderEmail)            
            ->getTransport();               
            $transport->sendMessage();
        } else {
            $this->messageManager->addError(__('Order cannot be canceled.'));
        }
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}