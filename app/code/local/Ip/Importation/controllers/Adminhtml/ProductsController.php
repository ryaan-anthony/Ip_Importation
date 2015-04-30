<?php

class Ip_Importation_Adminhtml_ProductsController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $status = Mage::getStoreConfig('importation/queue/file');

        $count = 0;

        foreach(json_decode($status, true) as $method => $filenames){

            $count += count($filenames);

        }

        if($count){

            $this->_getSession()->addSuccess(
                $this->__('You currently have '.$count.' files currently in queue for import.')
            );

        }

        $maxUploadSize = Mage::helper('importexport')->getMaxUploadSize();
        $this->_getSession()->addNotice(
            $this->__('Total size of uploadable files must not exceed %s', $maxUploadSize)
        );
        $this->loadLayout();
        $this->renderLayout();
    }

    public function executeAction()
    {
        $data = $this->getRequest()->getPost();
        if($data){
            /** @var $import Ip_Importation_Model_Products */
            $import = Mage::getModel('importation/products');
            $import->setData($data)->uploadSource();
            $this->_getSession()->addSuccess(
                $import->getSuccessMessage()
            );
        } else {
            $this->_getSession()->addError($this->__('Data is invalid or file is not uploaded'));
        }
        $this->_redirect('*/*/index');
    }


}