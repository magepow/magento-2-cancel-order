<?php

/**
 * @Author: Ha Manh
 * @Date:   2020-12-08 08:29:17
 * @Last Modified by:   Alex Dong
 * @Last Modified time: 2021-06-04 10:45:54
 */

namespace Magepow\CancelOrder\Controller\Cancelorder;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    private   $transportBuilder;
    protected $priceHelper;
    protected $resultPageFactory;
    protected $_order;
    protected $customer;
    protected $helper;
    protected $collectionFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\Order $order,
        \Magento\Customer\Model\Session $customerSession,
        \Magepow\CancelOrder\Helper\Data $helper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
    ) {
        $this->priceHelper       = $priceHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->_order            = $order;
        $this->_customerSession  = $customerSession;
        $this->transportBuilder  = $transportBuilder;
        $this->helper            = $helper;
        $this->collectionFactory = $collectionFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderId = $this->getRequest()->getParam('orderid');
        $order = $this->_order->load($orderId);
        $productId = [];
        foreach ($order->getAllItems() as $item) {
            $productId[] = $item->getProductId();
        }
        $productCollection = $this->collectionFactory->create();
        $productCollection->addAttributeToSelect('*')->addFieldToFilter('entity_id', array('in' => $productId));
            $products = [];
            foreach ($productCollection as $product) {
                $products[] = $product;              

            }
        $post['collectionProduct'] = $products;
        if($order->canCancel()){
            $order->cancel();
            $order->save();
            $this->messageManager->addSuccess(__('Order has been canceled successfully.'));
            if($this->helper->getEmailSeller())
            {
                if($this->helper->getEmailSender())
                {
                    $customerData = $this->_customerSession->getCustomer();
                    $post['store_name'] = $order->getStore()->getName();
                    $post['site_name'] = $order->getStore()->getWebsite()->getName();
                    $post['entity_id'] = $order->getEntity_id();
                    $post['base_grand_total'] = $this->priceHelper->currency($order->getBase_grand_total(), true, false);
                    $post['created_at'] = $order->getCreated_at();
                    $post['customer_lastname'] = $order->getCustomer_lastname();
                    $post['orderid'] = $order->getIncrement_id();

                    $senderName = $customerData->getName();
                    $senderEmail = $customerData->getEmail();
                    $sender = [
                        'name' => $senderName,
                        'email' => $this->helper->getEmailSender(),
                        ];
                    $transport = $this->transportBuilder->setTemplateIdentifier('cancel_order_email_template')
                    ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
                    ->setTemplateVars($post)
                    ->setFrom($sender)
                    ->addTo($senderEmail)
                    ->addCc($this->helper->getEmailSeller())           
                    ->getTransport();               
                    $transport->sendMessage();
                }
            }else{
                if($this->helper->getEmailSender())
                {
                    $customerData = $this->_customerSession->getCustomer();
                    $post['store_name'] = $order->getStore()->getName();
                    $post['site_name'] = $order->getStore()->getWebsite()->getName();
                    $post['entity_id'] = $order->getEntity_id();
                    $post['base_grand_total'] = $this->priceHelper->currency($order->getBase_grand_total(), true, false);
                    $post['created_at'] = $order->getCreated_at();
                    $post['customer_lastname'] = $order->getCustomer_lastname();
                    $post['orderid'] = $order->getIncrement_id();

                    $senderName = $customerData->getName();
                    $senderEmail = $customerData->getEmail();
                    $sender = [
                        'name' => $senderName,
                        'email' => $this->helper->getEmailSender(),
                        ];
                    $transport = $this->transportBuilder->setTemplateIdentifier('cancel_order_email_template')
                    ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
                    ->setTemplateVars($post)
                    ->setFrom($sender)
                    ->addTo($senderEmail)       
                    ->getTransport();               
                    $transport->sendMessage();
                }
            }
        } else {
            $this->messageManager->addError(__('Order cannot be canceled.'));
        }
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}