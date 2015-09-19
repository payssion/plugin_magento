<?php


class Payssion_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{

	/**
	 * @desc    Give Default settings
	 * @example $this->_getConfigData('demoMode','payssion_boleto')
	 * @since   0.0.2
	 *
	 * @param string $code
	 *
	 * @return mixed
	 */
	public function getConfigData($code, $paymentMethodCode = null, $storeId = null) {
		if (null === $storeId) {
			$storeId = Mage::app()->getStore()->getStoreId();
		}
		if (empty($paymentMethodCode)) {
			return trim(Mage::getStoreConfig("payment/payssion/$code", $storeId));
		}
		return trim(Mage::getStoreConfig("payment/$paymentMethodCode/$code", $storeId));
	}
	
	public function getPaymentMethodIcon($paymentMethodCode) {
		if ($paymentMethodCode) {
			$index = strrpos($paymentMethodCode, '_');
			if ($index) {
				$icon = strtolower(substr($class_name, $index + 1));
				return "images/payssion/$icon.png";
			}
		}
		return null;
	}
}
