<?php
namespace Core2\Mod\Products\Index;
use Core2\Classes\Table;


require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/class.edit.php";
require_once DOC_ROOT . "core2/inc/classes/Table/Db.php";


/**
 * @property \ModProductsController $modProducts
 */
class View extends \Common {


    /**
     * @param string $base_url
     * @return false|string
     * @throws \Zend_Db_Select_Exception
     * @throws \Exception
     */
    public function getTable(string $base_url): string {

        $table = new Table\Db($this->resId);
        $table->setTable("mod_products");
        $table->setPrimaryKey('id');
        $table->setAddUrl("{$base_url}&edit=0");
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->showDelete();

        $table->setQuery("
            SELECT p.id,
                   ps.title AS shop_title,
                   p.section,
                   p.category,
                   p.title,
                   0 AS links,
                   0 AS prices,
                   
                   '' AS price_last,
                   ps.id AS shop_id
            FROM mod_products AS p
                LEFT JOIN mod_products_links AS pl ON p.id = pl.product_id
                LEFT JOIN mod_products_prices AS pp ON p.id = pp.product_id 
                LEFT JOIN mod_products_shops AS ps ON pp.shop_id = ps.id 
            GROUP BY p.id, ps.id
            ORDER BY p.section,
                     p.category,
                     p.title
        ");

        $table->addFilter("CONCAT_WS('|', p.section, p.category)", $table::FILTER_TEXT, $this->_("Раздел, Категория"));
        $table->addFilter("p.title", $table::FILTER_TEXT, $this->_("Название"));

        $table->addSearch($this->_("Магазин"),   "ps.title",   $table::SEARCH_TEXT);
        $table->addSearch($this->_("Раздел"),    "p.section",  $table::SEARCH_TEXT);
        $table->addSearch($this->_("Категория"), "p.category", $table::SEARCH_TEXT);
        $table->addSearch($this->_("Название"),  "p.title",    $table::SEARCH_TEXT);


        $table->addColumn($this->_("Магазин"),   'shop_title', $table::COLUMN_TEXT, 100);
        $table->addColumn($this->_("Раздел"),    'section',    $table::COLUMN_TEXT);
        $table->addColumn($this->_('Категория'), 'category',   $table::COLUMN_TEXT);
        $table->addColumn($this->_("Название"),  'title',      $table::COLUMN_TEXT);
        $table->addColumn($this->_("Цена"),      'price_last', $table::COLUMN_HTML, 120);
        $table->addColumn($this->_("Дата цены"), 'date_last',  $table::COLUMN_DATE, 120)->sorting(false);


        $rows = $table->fetchRows();
        if ( ! empty($rows)) {
            foreach ($rows as $key => $row) {

                // Ссылки
                if ($row->links->getValue() > 0) {
                    $url = "index.php?module=products&action=index&page=table_links&product_id={$row->id}";

                    $row->links->setAttr('onclick', "event.cancelBubble = true;");
                    $row->links = "
                        <button type=\"button\" class=\"btn btn-xs btn-default\"
                                onclick=\"CoreUI.table.toggleExpandColumn('[RESOURCE]', '{$key}', '{$url}')\">
                            {$row->links} <i class='fa fa-chevron-down'></i>
                        </button>
                    ";

                } else {
                    $row->links = 0;
                }

                // Цены
                if ($row->prices->getValue() > 0) {
                    $url = "index.php?module=products&action=index&page=table_prices&product_id={$row->id}";

                    $row->prices->setAttr('onclick', "event.cancelBubble = true;");
                    $row->prices = "
                        <button type=\"button\" class=\"btn btn-xs btn-default\"
                                onclick=\"CoreUI.table.toggleExpandColumn('[RESOURCE]', '{$key}', '{$url}')\">
                            {$row->prices} <i class='fa fa-chevron-down'></i>
                        </button>
                    ";

                } else {
                    $row->prices = 0;
                }

                $row->price_last = $this->db->fetchOne("
                    SELECT CONCAT_WS('|', pp.price, date_mark)
                    FROM mod_products_prices AS pp
                    WHERE pp.product_id = ?
                      AND pp.shop_id = ?
                    ORDER BY pp.date_mark DESC 
                    LIMIT 1
                ", [
                    $row->id,
                    $row->shop_id
                ]);


                $price_last_explode = explode('|', $row->price_last->getValue());

                $row->price_last = "<b>" . \Tool::commafy($price_last_explode[0]) . "</b> <small class=\"text-muted\">BYN</small>";
                $row->price_last->setAttr('class', 'text-right');

                $row->date_last = date('Y-m-d', strtotime($price_last_explode[1]));
                $row->date_last->setAttr('class', 'text-right');
            }
        }

        return $table->render();
    }


    /**
     * Список ссылок товара
     * @param \Zend_Db_Table_Row $product
     * @return string
     * @throws Table\Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableLinks(\Zend_Db_Table_Row $product): string {

        $table = new Table\Db("{$this->resId}xxx_links_in_" . $product->id);
        $table->setAjax();
        $table->hideCheckboxes();
        $table->hideNumberRows();
        $table->hideService();
        $table->hideFooter();

        $table->setQuery("
			SELECT pl.id,
				   pl.url,
				   pl.date_created
		    FROM mod_products_links AS pl   
		    WHERE pl.product_id = ?
		    ORDER BY pl.date_created DESC
		", [
            $product->id
        ]);

        $table->addColumn("Ссылка",          'url',          $table::COLUMN_HTML);
        $table->addColumn("Дота добавления", 'date_created', $table::COLUMN_DATE, 140);

        $rows = $table->fetchRows();
        foreach ($rows as $row) {

            $row->url->setAttr('onclick', "event.cancelBubble = true;");
            $row->url = "<a href=\"{$row->url}\" target=\"_blank\">{$row->url}</a>";
        }

        return $table->render();
    }


    /**
     * Список цен товара
     * @param \Zend_Db_Table_Row $product
     * @return string
     * @throws Table\Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTablePrices(\Zend_Db_Table_Row $product): string {

        $table = new Table\Db("{$this->resId}xxx_prices_in_" . $product->id);
        $table->setAjax();
        $table->hideCheckboxes();
        $table->hideService();

        $table->setQuery("
			SELECT pp.id,
				   pp.price,
				   pp.measure,
				   UPPER(pp.currency) AS currency,
				   CONCAT_WS(' ', pp.quantity, pp.unit) AS unit,
				   pp.date_mark,
			       pl.url
		    FROM mod_products_prices AS pp
			    JOIN mod_products_links AS pl ON pp.link_id = pl.id
		    WHERE pp.product_id = ?
		    ORDER BY pp.date_mark DESC
		", [
            $product->id
        ]);

        $table->addColumn("Дата цены",     'date_mark', $table::COLUMN_DATE, 120);
        $table->addColumn("Цена",          'price',     $table::COLUMN_NUMBER, 100);
        $table->addColumn("За количество", 'unit',      $table::COLUMN_TEXT, 200);
        $table->addColumn("Ссылка",        'url',       $table::COLUMN_HTML);
        $table->addColumn("Мера",          'measure',   $table::COLUMN_HTML, 70);

        $rows = $table->fetchRows();
        foreach ($rows as $row) {
            $row->price = "<b>{$row->price}</b> <small class=\"text-muted\">{$row->currency}</small>";
            $row->url   = "<a href=\"{$row->url}\" target=\"_blank\">{$row->url}</a>";
        }

        return $table->render();
    }


    /**
     * @param string                           $base_url
     * @param \Zend_Db_Table_Row_Abstract|null $product
     * @return string
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Exception
     */
    public function getEdit(string $base_url, \Zend_Db_Table_Row_Abstract $product = null): string {

        $edit = new \editTable($this->resId);
        $edit->table = 'mod_products';

        $edit->SQL = [
            [
                'id'       => $product->id ?? null,
                'section'  => $product->section ?? null,
                'category' => $product->category ?? null,
                'title'    => $product->title ?? null,
            ],
        ];

        $edit->addControl('Раздел',    "TEXT", 'style="width:300px;"');
        $edit->addControl('Категория', "TEXT", 'style="width:300px;"');
        $edit->addControl('Название',  "TEXT", 'style="width:300px;"', '', '', true);


        $edit->firstColWidth = "200px";
        $edit->save("xajax_saveProducts(xajax.getFormValues(this.id))");

        return $edit->render();
    }
}
