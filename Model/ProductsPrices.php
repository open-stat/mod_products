<?php


/**
 *
 */
class ProductsPrices extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_products_prices';


    /**
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowById(int $id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("id = ?", $id);

        return $this->fetchRow($select);
    }


    /**
     * @param int    $shop_id
     * @param int    $product_id
     * @param int    $link_id
     * @param string $date
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByShopProductLinkDate(int $shop_id, int $product_id, int $link_id, string $date): ?Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("shop_id = ?", $shop_id)
            ->where("product_id = ?", $product_id)
            ->where("link_id = ?", $link_id)
            ->where("DATE_FORMAT(date_mark, '%Y-%m-%d') = ?", $date);

        return $this->fetchRow($select);
    }


    /**
     * @return Zend_Db_Select
     */
    public function getSelect(): Zend_Db_Select {

        return $this->_db->select()
            ->from(['pp' => $this->_name], [
                'id',
                'product_id',
                'shop_id',
                'city',
                'source_name',
                'is_available',
                'price',
                'currency',
                'delivery_price',
                'delivery_currency',
                'delivery_days',
                'halva_price',
                'halva_currency',
                'halva_term',
                'warranty',
                'date_mark',
                'date_created',
            ]);
    }
}