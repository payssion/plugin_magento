<?php


class Payssion_Payment_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $form = new Varien_Data_Form();
        $form->setAction($this->getFormAction())
            ->setId('pay')
            ->setName('pay')
            ->setMethod('POST')
            ->setUseContainer(true);
        $formData = $this->getFormData();
        foreach ($formData as $field=>$value) {
            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }

        $html = '<html><body>';
        $html.= $this->__('Redirect to payssion.com ...');
        $html.= '<hr>';
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("pay").submit();</script>';
        $html.= '</body></html>';
        

        return $html;
    }
    
    /**
     * Return order instance
     *
     * @return Mage_Sales_Model_Order|null
     */
    protected function _getOrder()
    {
    	if ($this->getOrder()) {
    		return $this->getOrder();
    	} elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
    		return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    	} else {
    		return null;
    	}
    }
    
    /**
     * Get Form data by using ogone payment api
     *
     * @return array
     */
    public function getFormData()
    {
    	//return $this->_getOrder()->getPayment()->getMethodInstance()->getFormFields();
    	$formData['code'] = $code;
    	$payment = $this->_getOrder()->getPayment()->getMethodInstance();
    	$code = $payment->getCode();
    	$formData = $this->_getOrder()->getPayment()->getMethodInstance()->getFormFields();
    	$formData['code'] = $code;
    	return $formData;
    }
    
    /**
     * Getting gateway url
     *
     * @return string
     */
    public function getFormAction()
    {
    	return $this->_getOrder()->getPayment()->getMethodInstance()->getUrl();
    }
}
