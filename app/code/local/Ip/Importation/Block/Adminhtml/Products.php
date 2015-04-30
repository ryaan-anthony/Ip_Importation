<?php

class Ip_Importation_Block_Adminhtml_Products extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /*
     * Form = $this->_blockGroup . '/' . $this->_controller . '_' . $this->_mode . '_form'
     */
    protected $_blockGroup = 'importation';
    protected $_controller = 'adminhtml_products';
    protected $_mode = 'import';

    /**
     * Set the template for the block
     *
     */
    public function _construct()
    {
        parent::_construct();

        $this->removeButton('back')
            ->removeButton('reset')
            ->_updateButton('save', 'label', $this->__('Run Import'))
            ->_updateButton('save', 'id', 'upload_button')
            ->_updateButton('save', 'onclick', 'editForm.submit();');
    }
    public function getHeaderText()
    {
        return Mage::helper('adminhtml')->__('Import (Customized for %s)', Mage::app()->getStore()->getFrontendName());
    }

}