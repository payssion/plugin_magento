<?php


class Payssion_Payment_Model_Payssion extends Mage_Payment_Model_Method_Abstract {

    protected $_code          = 'payssion';
    protected $_formBlockType = 'paysion/form';
    protected $_infoBlockType = 'payssion/info';
    protected $_order;
    
    const     API_KEY       = 'payment/payssion/payssion_apikey';
    const     SECRET_KEY       = 'payment/payssion/payssion_secretkey';
    protected $pm_id = '';
    
    public function __construct()
    {
    	parent::__construct();
    	
    	if ($this->pm_id) {
    		$this->_code = $this->_code . '_' . $this->pm_id;
    	}
    }

    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('payssion/redirect', array('_secure' => true));
    }

    public function getPayssionUrl() {
        $url = 'https://www.Payssion.com/process.html';
        return $url;
    }

    public function getLocale()
    {
        return Mage::app()->getLocale()->getLocaleCode();
    }
    
    public function getPayssionCheckoutFormFields() {

        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order    = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        if ($order->getBillingAddress()->getEmail()) {
            $email = $order->getBillingAddress()->getEmail();
        } else {
            $email = $order->getCustomerEmail();
        }
      
        $params = array(
            'api_key'           => Mage::getStoreConfig(MagentoCenter_Payssion_Model_Checkout::API_KEY),
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
        
        $params['api_sig'] = $this->generateSignature($params, Mage::getStoreConfig(MagentoCenter_Payssion_Model_Checkout::SECRET_KEY));
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
