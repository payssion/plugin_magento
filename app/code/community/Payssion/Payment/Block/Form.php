<?php


class Payssion_Payment_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payssion/form.phtml');
        $this->setMethodTitle('');
    }
    
    
    public function getMethodLabelAfterHtml()
    {
    	if (! $this->hasData('_method_label_html')) {
    		$code = $this->getMethod()->getCode();
    		$labelBlock = Mage::app()->getLayout()->createBlock('core/template', null, array(
    				'template' => 'payssion/payment/payment_method_label.phtml',
    				'payment_method_icon' =>  $this->getSkinUrl(Mage::helper('payssion')->getPaymentMethodIcon($code)),
    				'payment_method_label' => Mage::helper('payssion')->getConfigData('title', $code),
    				'payment_method_class' => $code
    		));
    
    		$this->setData('_method_label_html', $labelBlock->toHtml());
    	}
    
    	return $this->getData('_method_label_html');
    }
    
}