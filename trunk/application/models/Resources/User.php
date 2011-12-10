<?php

class Elm_Model_Resource_User extends Colony_Db_Table
{
    protected $_name = 'user';

	protected $_primary = 'user_id';

    /**
     * Check customer scope, email and confirmation key before saving
     *
     * @param Colony_Model_Abstract $user
     * @return Elm_Model_Resource_User
     * @throws Colony_Exception
     */
    protected function _beforeSave(Colony_Model_Abstract $user)
    {
        parent::_beforeSave($user);
        if (!$user->getEmail()) {
            Bootstrap::throwException(Mage::helper('customer')->__('User email is required.'));
        }
        return $this;
    }

    /**
     * Save customer addresses and set default addresses in attributes backend
     *
     * @param   Colony_Model_Abstract $user
     * @return  Colony_Db_Table
     */
    protected function _afterSave(Colony_Model_Abstract $user)
    {
        return parent::_afterSave($user);
    }

    /**
     * Load customer by email
     *
     * @param Elm_Model_User $user
     * @param string $email
     * @return Elm_Model_Resource_User
     * @throws Colony_Exception
     */
    public function loadByEmail(Elm_Model_User $user, $email)
    {
		$row = $this->fetchRow(Zend_Db_Table::getDefaultAdapter()->quoteInto('email=?', $email));
        if ($row !== null) {
            $this->load($user, $row->user_id);
        } else {
            $user->setData(array());
        }
        return $this;
    }









	




	
    /**
     * Change customer password
     *
     * @param   Mage_Customer_Model_Customer
     * @param   string $newPassword
     * @return  this
     */
    public function changePassword(Mage_Customer_Model_Customer $customer, $newPassword)
    {
        $customer->setPassword($newPassword);
        $this->saveAttribute($customer, 'password_hash');
        return $this;
    }

    /**
     * Check whether there are email duplicates of customers in global scope
     *
     * @return bool
     */
    public function findEmailDuplicates()
    {
        $lookup = $this->_getReadAdapter()->fetchRow("SELECT email, COUNT(*) AS `qty`
            FROM `{$this->getTable('customer/entity')}`
            GROUP BY 1 ORDER BY 2 DESC LIMIT 1
        ");
        if (empty($lookup)) {
            return false;
        }
        return $lookup['qty'] > 1;
    }

    /**
     * Check user by id
     *
     * @param int $userId
     * @return bool
     */
    public function checkUserId($userId)
    {
        return $select = $this->find($userId)->current();
    }
}

