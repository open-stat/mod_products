<?php


/**
 *
 */
class Products extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_products';

    /**
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowById(int $id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("id = ?", $id);

        return $this->fetchRow($select);
    }


    /**
     * @param string $title
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTitle(string $title):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("title = ?", $title);

        return $this->fetchRow($select);
    }


    /**
     * @return Zend_Db_Select
     */
    public function getSelect(): Zend_Db_Select {

        return $this->_db->select()
            ->from(['p' => $this->_name], [
                'id',
                'parent_id',
                'section',
                'category',
                'title',
                'date_created',
                'date_last_update',
            ]);
    }
}