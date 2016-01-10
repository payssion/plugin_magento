<?php

class Payssion_Payment_Model_Payssion extends Mage_Payment_Model_Method_Abstract {

    protected $_code          = 'payssion';
    protected $_formBlockType = 'payssion/form';
    protected $_infoBlockType = 'payssion/info';
    protected $_order;
    
    protected $pm_id = null;
    
    public function __construct()
    {
    	parent::__construct();
    	
    	if ($this->pm_id) {
    		$this->_code = $this->_code . '_' . str_replace('_', '', $this->pm_id);
    	}
    }
    
    
    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
    	if (!$this->_order) {
    		$this->_order = $this->getInfoInstance()->getOrder();
    	}
    	return $this->_order;
    }

    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('payssion/redirect', array('_secure' => true));
    }

    public function getUrl() {
    	$sandbox = Mage::helper('payssion')->getConfigData('payssion_sandbox');
    	if (!$sandbox) {
    		$url = 'https://www.payssion.com/payment/create.html';
    	} else {
    		$url = 'http://sandbox.payssion.com/payment/create.html';
    	}
        return $url;
    }

    public function getLocale()
    {
        return Mage::app()->getLocale()->getLocaleCode();
    }
    
    public function getFormFields() {
        $order = $this->getOrder();
        $order_id = $this->getOrder()->getRealOrderId();
        if ($order->getBillingAddress()->getEmail()) {
            $email = $order->getBillingAddress()->getEmail();
        } else {
            $email = $order->getCustomerEmail();
        }
      
        $params = array(
        	'source' => 'magento',
            'api_key'           => Mage::helper('payssion')->getConfigData('payssion_apikey'),
        	'pm_id' => $this->pm_id,
        	'track_id'            => $order_id,
            'success_url'     => Mage::getUrl('payssion/redirect/success', array('transaction_id' => $order_id)),
            'redirect_url'        => Mage::getUrl('payssion/redirect/cancel', array('transaction_id' => $order_id)),
            'language'           => $this->getLocale(),
            'description'        => Mage::helper('payssion')->__('Payment for order #').$order_id,
            'amount'       => trim(round($order->getGrandTotal(), 2)),
            'currency'           => $order->getOrderCurrencyCode(),
            'notify_url'           		=> Mage::getUrl('payssion/notify'),
            'payer_name'   => $order->getBillingAddress()->getFirstname() . ' ' . $order->getBillingAddress()->getLastname(),
            'payer_email'        => $email,
        );
        
        $params['api_sig'] = $this->generateSignature($params, Mage::helper('payssion')->getConfigData('payssion_secretkey'));
        return $params;
    }
    
    private function generateSignature(&$req, $secretKey) {
    	$arr = array($req['api_key'], $req['pm_id'], $req['amount'], $req['currency'],
    			$req['track_id'], '', $secretKey);
    	$msg = implode('|', $arr);
    	return md5($msg);
    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

}
