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
     * Get ipn data, send verification to OkPay, run corresponding handler
     */
    public function processIpnRequest(array $request)
    {
        $this->_request   = $request;
        $this->_debugData = array('ipn' => $request);
        ksort($this->_debugData['ipn']);

        try {
            $this->_getOrder();
			$this->_postBack($this->_debugData);
            $this->_processOrder();
        } catch (Exception $e) {
            $this->_debugData['exception'] = $e->getMessage();
            $this->_debug();
            throw $e;
        }
    }

    /**
     * Post back to OkPay to check whether this request is a valid one
     */
    protected function _postBack($data)
    {
		$header = '';
        $req = 'ok_verify=true';
		foreach ($data['ipn'] as $key => $value) { 
			if(get_magic_quotes_gpc() == 1) {
				$value = urlencode(stripslashes($value)); 
			} else { 
				$value = urlencode($value); 
			}
			$req .= "&$key=$value";  
		}
		// Post back to OKPAY to validate 
		$header .= "POST /ipn-verify.html HTTP/1.0\r\n"; 
		$header .= "Host: www.okpay.com\r\n"; 
		$header .= "Content-Type: application/x-www-form-urlencoded\n"; 
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
        try {
			$fp = fsockopen ('www.okpay.com', 80, $errno, $errstr, 30);
			if (!$fp) {
				throw new Exception('HTTP/1.1 406 Not Acceptable', 406);
			}
		} catch (Exception $e) {
			$this->_debugData['http_error'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
            throw $e;
		}
		fputs ($fp, $header . $req); 
		while (!feof($fp)) {
			$response = fgets ($fp, 1024);
		}
		if ($response != 'VERIFIED') {
			throw new Exception('OkPay IPN postback failure. See ' . self::DEFAULT_LOG_FILE . ' for details.');
		}
    }

    /**
     * Load and validate order, instantiate proper configuration
     */
    protected function _getOrder()
    {
        if (empty($this->_order)) {
            // get proper order
            $id = $this->_request['ok_invoice'];
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
        $payment->setTransactionId($this->getRequestData('ok_txn_id'))
            ->setPreparedMessage($this->_createIpnComment(''))
            ->registerCaptureNotification($this->getRequestData('ok_txn_gross'));
        $this->_order->save();

        // notify customer
        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$this->_order->getEmailSent()) {
            $this->_order->sendNewOrderEmail()->addStatusHistoryComment(
                Mage::helper('okpay')->__('Notified customer about invoice #%s.', $invoice->getIncrementId())
            )
            ->setIsCustomerNotified(true)
            ->save();
        }
    }
	
	
	
    protected function _createIpnComment($comment = '', $addToHistory = false)
    {
        $paymentInvoice = $this->getRequestData('ok_invoice');
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
