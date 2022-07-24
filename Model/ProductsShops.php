<?php


/**
 *
 */
class ProductsShops extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_products_shops';

    /**
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowById(int $id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("id = ?", $id);

        return $this->fetchRow($select);
    }


    /**
     * @param string $source_name
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowBySourceName(string $source_name): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("source_name = ?", $source_name);

        return $this->fetchRow($select);
    }


    /**
     * @return Zend_Db_Select
     */
    public function getSelect(): Zend_Db_Select {

        return $this->_db->select()
            ->from(['ps' => $this->_name], [
                'id',
                'title',
                'url',
                'source_name',
                'date_created',
                'date_last_update',
            ]);
    }
}