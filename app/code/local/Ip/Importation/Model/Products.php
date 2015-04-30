<?php

class Ip_Importation_Model_Products extends Varien_Object
{

    protected $config_path = 'importation/queue/file';
    protected $attributeSetId = null;
    protected $categories = [];

    /**
     * Import behavior.
     */
    const BEHAVIOR_UPDATE_PRICES  = 'price';

    /**
     * Form field names (and IDs)
     */
    const FIELD_NAME_SOURCE_FILE = 'import_file';

    function __construct()
    {
        $this->attributeSetId = Mage::getModel('eav/entity_attribute_set')
            ->load('Default', 'attribute_set_name')
            ->getAttributeSetId();
    }

    /**
     * Import/Export working directory (source files, result files, lock files etc.).
     *
     * @return string
     */
    public static function getWorkingDir()
    {
        return Mage::getBaseDir('var') . DS . 'import' . DS;
    }


    /**
     * Move uploaded file and create source adapter instance.
     *
     * @throws Mage_Core_Exception
     * @return string Source file path
     */
    public function uploadSource()
    {
        $behavior    = $this->getBehavior();
        $uploader  = Mage::getModel('core/file_uploader', self::FIELD_NAME_SOURCE_FILE);
        $uploader->skipDbProcessing(true);
        $result    = $uploader->save(self::getWorkingDir());
        $extension = pathinfo($result['file'], PATHINFO_EXTENSION);

        $uploadedFile = $result['path'] . $result['file'];
        if (!$extension) {
            unlink($uploadedFile);
            Mage::throwException(Mage::helper('importexport')->__('Uploaded file has no extension'));
        }
        $sourceFile = self::getWorkingDir() . $behavior . '-' . date('Y-m-d-H-i-s') . '.csv';

        if(strtolower($uploadedFile) != strtolower($sourceFile)) {
            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }

            if (!@rename($uploadedFile, $sourceFile)) {
                Mage::throwException(Mage::helper('importexport')->__('Source file moving failed'));
            }
        }

        $queue = [];;
        if($current = Mage::getStoreConfig($this->config_path)){
            $current = json_decode($current, true);
            $current[$behavior][] = ['file' => $sourceFile];
            $queue = $current;
        } else {
            $queue[$behavior][] = ['file' => $sourceFile];
        }
        $this->setFlag($queue);
        $this->setSuccessMessage(
            Mage::helper('adminhtml')->__('Your import has been added to the queue.')
        );
    }

    /**
     * Entry point for upload
     */
    public function cron()
    {
        try{

            if(list($filename, $method) = $this->getNext()){

                if(is_callable([$this, $method]) && file_exists($filename)){

                    $this->log(array("Calling method: ".$method, "Loading file: ".$filename));

                    $this->importData($filename, $method);

                }
            }

        } catch(Exception $e){

            $this->log($e->getMessage());

        }

    }

    protected function getNext()
    {
        if($current = Mage::getStoreConfig($this->config_path)){

                $current = json_decode($current, true);

                foreach($current as $method => $filenames){

                    foreach($filenames as $filekey => $filedata){

                        unset($current[$method][$filekey]);

                        $this->setFlag($current);

                        $filename = $filedata['file'];

                        return [$filename, $method]; // process one at a time

                    }
                }
        }

        return false;
    }

    /**
     * @param $path
     * @return mixed
     */
    protected function check_path($path)
    {
        $io = new Varien_Io_File();
        if (!$io->isWriteable($path) && !$io->mkdir($path, 0777, true)) {
            Mage::throwException(Mage::helper('adminhtml')->__("Cannot create writeable directory '%s'", $path));
        }
        return $path;
    }

    protected function importData($filename, $method)
    {
        $file = fopen($filename, "r");

        $data = $this->map($file);

//        $this->log($method);
//        $this->log($data);

        foreach($data as $row){
            #$this->log(array("row #".$i, $row));
            try{
                $this->$method($row);
            } catch(Exception $e){
                #$this->log($e->getMessage());
            }
        }
        fclose($file);
        #$this->log(stristr($filename, self::getWorkingDir()));
        if(stristr($filename, self::getWorkingDir()) !== false){
            unlink($filename);
        }
    }


    /**
     * Map csv file to assoc array
     * @param $file
     * @return array
     */
    protected function map($file)
    {
        $count = 0;
        $columns = [];
        $master = [];
        while ($data = fgetcsv($file, 0)) {
            if(!$count++){
                $columns = $data;
                continue;
            }
            $results = [];
            foreach($data as $key => $item){
                $col = trim($columns[$key]);
                $results[$col] = trim($item);
            }
            $master[] = $results;
        }
        return $master;
    }

    /**
     * Product must  exist
     * @param array $row
     */
    protected function price($row)
    {
        $product = $this->loadProduct($row['Sku']);
        if($product->getId()){
            $product->setPrice($row['Price']);
            $product->save();
        }
    }


    protected function loadProduct($sku)
    {
        /** @var Mage_Catalog_Model_Product $model */
        $model = Mage::getModel('catalog/product');
        $product = $model->loadByAttribute('sku', $sku);
        if($product && $product->getId()){
            #$this->log(array("product found: ", $product->getId()));
            return $product;
        }
        #$this->log(array("product NOT found: ", get_class($model)));
        return $model;
    }

    protected function log($mixed)
    {
        Mage::log($mixed, null, 'importation.log');
    }

    protected function setFlag($flag)
    {
        if(is_array($flag)) $flag = json_encode($flag);
        /** @var Mage_Core_Model_Config $config */
        $config = Mage::getModel('core/config');
        $config->saveConfig($this->config_path, $flag);
        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();
    }
}