<?php

class Payssion_Payment_Model_Notify
{
    /**
     * Default log filename
     */
    const DEFAULT_LOG_FILE = 'payssion_notify.log';

    /*
     * @param Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * IPN request data
     */
    protected $_request = array();

    /**
     * Collected debug information
     */
    protected $_debugData = array();
    /**
     * IPN request data getter
     */
    public function getRequestData($key = null)
    {
        if (null === $key) {
            return $this->_request;
        }
        return isset($this->_request[$key]) ? $this->_request[$key] : null;
    }

    /**
     * handle payssion postback
     */
    public function handleNotify(array $request)
    {
        $this->_request   = $request;
        $this->_debugData = array('notify' => $request);
        ksort($this->_debugData['notify']);

        try {
            $this->_getOrder();
            $this->_processOrder();
        } catch (Exception $e) {
            $this->_debugData['exception'] = $e->getMessage();
            $this->_debug();
            throw $e;
        }
    }
    
    public function isValidNotify($data) {
    	$apiKey = Configuration::get(self::PAYSSION_API_KEY);
    	$secretKey = Configuration::get(self::PAYSSION_SECRET_KEY);
    
    	// Assign payment notification values to local variables
    	$pm_id = $data['pm_id'];
    	$amount = $data['amount'];
    	$currency = $data['currency'];
    	$track_id = $data['track_id'];
    	$sub_track_id = $data['sub_track_id'];
    	$state = $data['state'];
    
    	$check_array = array(
    			$apiKey,
    			$pm_id,
    			$amount,
    			$currency,
    			$track_id,
    			$sub_track_id,
    			$state,
    			$secretKey
    	);
    	$check_msg = implode('|', $check_array);
    	echo "check_msg=$check_msg";
    	$check_sig = md5($check_msg);
    	$notify_sig = $data['notify_sig'];
    	return ($notify_sig == $check_sig);
    }

    /**
     * Load and validate order, instantiate proper configuration
     */
    protected function _getOrder()
    {
        if (empty($this->_order)) {
            // get proper order
            $id = $this->_request['track_id'];
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($id);
            if (!$this->_order->getId()) {
                $this->_debugData['exception'] = sprintf('Wrong order ID: "%s".', $id);
                $this->_debug();
                Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1','503 Service Unavailable')
                    ->sendResponse();
                exit;
            }
        }
        return $this->_order;
    }

    /**
     * IPN workflow implementation
     */
    protected function _processOrder()
    {
        try {
            $this->_registerPaymentSuccess();
        } catch (Mage_Core_Exception $e) {
            $comment = $this->_createIpnComment(Mage::helper('paypal')->__('Note: %s', $e->getMessage()), true);
            $comment->save();
            throw $e;
        }
    }

    /**
     * Process completed payment (either full or partial)
     */
    protected function _registerPaymentSuccess()
    {
        $payment = $this->_order->getPayment();
        $payment->setTransactionId($this->getRequestData('transaction_id'))
            ->setPreparedMessage($this->_createNotifyComment(''))
            ->registerCaptureNotification($this->getRequestData('paid'));
        $this->_order->save();

        // notify customer
        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$this->_order->getEmailSent()) {
            $this->_order->sendNewOrderEmail()->addStatusHistoryComment(
                Mage::helper('payssion')->__('Notified customer about invoice #%s.', $invoice->getIncrementId())
            )
            ->setIsCustomerNotified(true)
            ->save();
        }
    }
	
	
	
    protected function _createNotifyComment($comment = '', $addToHistory = false)
    {
        $paymentInvoice = $this->getRequestData('transaction_id');
        $message = Mage::helper('paypal')->__('IPN "%s".', $paymentInvoice);
        if ($comment) {
            $message .= ' ' . $comment;
        }
        if ($addToHistory) {
            $message = $this->_order->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }
        return $message;
    }
    /**
     * Log debug data to file
     */
    protected function _debug()
    {
            $file = self::DEFAULT_LOG_FILE;
            Mage::getModel('core/log_adapter', $file)->log($this->_debugData);
    }
}
