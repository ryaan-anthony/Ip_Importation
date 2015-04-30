<?php

class Ip_Importation_Block_Adminhtml_Products_Import_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Add fieldset
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/execute'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('adminhtml')->__('Import Settings')));

        $fieldset->addField('behavior', 'select', array(
            'name'     => 'behavior',
            'title'    => Mage::helper('adminhtml')->__('Import Behavior'),
            'label'    => Mage::helper('adminhtml')->__('Import Behavior'),
            'required' => true,
            'values'   => array(
                array('value' => Ip_Importation_Model_Products::BEHAVIOR_UPDATE_PRICES, 'label' => 'Update Product Prices'),
            )
        ));
        $fieldset->addField(Ip_Importation_Model_Products::FIELD_NAME_SOURCE_FILE, 'file', array(
            'name'     => Ip_Importation_Model_Products::FIELD_NAME_SOURCE_FILE,
            'label'    => Mage::helper('adminhtml')->__('Select File to Import'),
            'title'    => Mage::helper('adminhtml')->__('Select File to Import'),
            'required' => true
        ));


        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Get form HTML
     *
     * @return string
     */
    public function getFormHtml()
    {
        return parent::getFormHtml().
            $this->getParentBlock()->getChildHtml('example');
    }
}
