<?php
class Eurowsport_Customform_Block_Customform extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getCustomform()     
     { 
        if (!$this->hasData('customform')) {
            $this->setData('customform', Mage::registry('customform'));
        }
        return $this->getData('customform');
        
    }
}