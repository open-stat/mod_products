<?php


/**
 *
 */
class ProductsPages extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_products_pages';

    /**
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowById(int $id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("id = ?", $id);

        return $this->fetchRow($select);
    }
}