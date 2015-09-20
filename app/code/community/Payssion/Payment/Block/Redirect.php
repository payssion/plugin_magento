<?php


class Payssion_Payment_Block_Redirect extends Mage_Core_Block_Abstract
{
	protected function _getOrder() {
		if ($this->getOrder()) {
			return $this->getOrder();
		} else {
			// log the exception
			Mage::log("Redirect exception could not load the order:", Zend_Log::DEBUG, "payssion_notification.log", true);
			return null;
		}
	}
	
    protected function _toHtml()
    {
    	$paymentObject = $this->_getOrder()->getPayment();
    	$payssion = $this->_getOrder()->getPayment()->getMethodInstance();
        $form = new Varien_Data_Form();
        $form->setAction($payssion->getPayssionUrl())
            ->setId('pay')
            ->setName('pay')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($payssion->getPayssionCheckoutFormFields() as $field=>$value) {
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
}
