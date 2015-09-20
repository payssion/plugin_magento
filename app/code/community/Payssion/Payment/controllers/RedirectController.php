<?php


class Payssion_Payment_RedirectController extends Mage_Core_Controller_Front_Action {

    protected $_order;

    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function indexAction() {
    	$order = Mage::helper('payssion')->getOrder();
        $this->getResponse()
                ->setHeader('Content-type', 'text/html; charset=utf8')
                ->setBody($this->getLayout()
                ->createBlock('payssion/redirect')
                ->setOrder($order)
                ->toHtml());
    }

    public function successAction() {
            $event = $this->getRequest()->getParams();
            $transaction_id= $event['transaction_id'];
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($transaction_id);
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }

    
    public function cancelAction()
    {
        $event = $this->getRequest()->getParams();
        $transaction_id= $event['transaction_id'];
        $this->_getCheckout()->addError(Mage::helper('payssion')->__('The order has been canceled. Order #').$transaction_id);
        $this->_redirect('checkout/cart');
    }
}

?>
