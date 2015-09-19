<?php

class Payssion_Payment_NotifyController extends Mage_Core_Controller_Front_Action
{
    /**
     * Instantiate notify model and pass notify request to it
     */
    public function indexAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        try {
            $data = $this->getRequest()->getPost();
            Mage::getModel('payssion/notify')->handleNotify($data);
        } catch (Exception $e) {
            Mage::logException($e);
			header("HTTP/1.1 406 Not Acceptable");
			exit;
        }
    }
}
