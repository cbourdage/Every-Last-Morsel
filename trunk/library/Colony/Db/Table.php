<?php

abstract class Colony_Db_Table extends Zend_Db_Table_Abstract
{
    /**
     * Resource model name that contains entities (names of tables)
     *
     * @var string
     */
    protected $_resourceModel;

    /**
     * Load an object
     *
     * @param   Colony_Model_Abstract $object
     * @param   mixed $id
     * @return  Colony_Db_Table
     */
    public function load(Colony_Model_Abstract $object, $id)
    {
		if ($data = $this->find($id)) {
			$object->setData(array_shift($data->toArray()));
		}

        $this->_afterLoad($object);
        return $this;
    }

	/**
     * Save a row to the database
     *
     * @param Colony_Model_Abstract $object
     * @return mixed The primary key
     */
    public function save($object)
    {
		if ($object->isDeleted()) {
            return $this->delete($object);
        }

		$this->_beforeSave($object);
        $row = $this->createRow();
        $columns = $this->info('cols');
        foreach ($columns as $column) {
            if (array_key_exists($column, $object->toArray())) {
                $row->$column = $object->getData($column);
            }
        }

		$row->save();

		if (is_null($object->getId())) {
			$object->setId($this->getDefaultAdapter()->lastInsertId($this->_name));
		}

        $this->_afterSave($object);
        return $this;
    }

    /**
     * Delete the object
     *
     * @param Colony_Model_Abstract $object
     * @return Mage_Core_Model_Mysql4_Abstract
     */
    public function delete(Colony_Model_Abstract $object)
    {
        $this->_beforeDelete($object);
        $this->delete($this->quoteInto($this->_primary . '=?', $object->getId()));
        $this->_afterDelete($object);
        return $this;
    }

     /**
     * Check that model data fields that can be saved
     * has really changed comparing with origData
     *
     * @param Colony_Model_Abstract $object
     * @return boolean
     */
    public function hasDataChanged($object)
    {
        if (!$object->getOrigData()) {
            return true;
        }

        $columns = $this->info('cols');
        foreach ($columns as $col) {
            if ($object->getOrigData($col) != $object->getData($col)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Perform actions after object load
     *
     * @param Colony_Model_Abstract $object
	 * @return Colony_Db_Table
     */
    protected function _afterLoad(Colony_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions before object save
     *
     * @param Colony_Model_Abstract $object
	 * @return Colony_Db_Table
     */
    protected function _beforeSave(Colony_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions after object save
     *
     * @param Colony_Model_Abstract $object
	 * @return Colony_Db_Table
     */
    protected function _afterSave(Colony_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions before object delete
     *
     * @param Colony_Model_Abstract $object
	 * @return Colony_Db_Table
     */
    protected function _beforeDelete(Colony_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions after object delete
     *
     * @param Colony_Model_Abstract $object
	 * @return Colony_Db_Table
     */
    protected function _afterDelete(Colony_Model_Abstract $object)
    {
        return $this;
    }
}

