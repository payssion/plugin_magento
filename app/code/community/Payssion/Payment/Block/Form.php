<?php


class Payssion_Payment_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payssion/form.phtml');
    }
}