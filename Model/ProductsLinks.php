<?php


/**
 *
 */
class ProductsLinks extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_products_links';


    /**
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowById(int $id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("id = ?", $id);

        return $this->fetchRow($select);
    }


    /**
     * @param int    $product_id
     * @param string $url
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByProductIdUrl(int $product_id, string $url):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("product_id = ?", $product_id)
            ->where("url = ?", $url);

        return $this->fetchRow($select);
    }
}