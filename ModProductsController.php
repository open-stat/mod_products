<?php
use \Core2\Mod\Products;

require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Panel.php";

require_once 'classes/Index/View.php';


/**
 * @property \Products       $dataProducts
 * @property \ProductsShops  $dataProductsShops
 * @property \ProductsPrices $dataProductsPrices
 * @property \ProductsLinks  $dataProductsLinks
 * @property \ProductsPages  $dataProductsPages
 */
class ModProductsController extends Common {

    /**
     * @return string
     * @throws Exception
     */
    public function action_index(): string {

        if (isset($_GET['page'])) {
            try {
                switch ($_GET['page']) {
                    case 'table_links':
                        if (empty($_GET['product_id'])) {
                            throw new Exception('Не указан обязательный параметр product_id');
                        }
                        $product = $this->dataProducts->getRowById((int)$_GET['product_id']);

                        if (empty($product)) {
                            throw new Exception('Указанный товар не найден');
                        }

                        return (new Products\Index\View())->getTableLinks($product);
                        break;

                    case 'table_prices':
                        if (empty($_GET['product_id'])) {
                            throw new Exception('Не указан обязательный параметр product_id');
                        }
                        $product = $this->dataProducts->getRowById((int)$_GET['product_id']);

                        if (empty($product)) {
                            throw new Exception('Указанный товар не найден');
                        }

                        return (new Products\Index\View())->getTablePrices($product);
                        break;
                }

                throw new Exception($this->_('Некорректный адрес'));

            } catch (Exception $e) {
                return Alert::danger($e->getMessage());
            }
        }


        $base_url = 'index.php?module=products';
        $panel    = new Panel('tab');
        $content  = [];

        $view = new Products\Index\View();


        if (isset($_GET['edit'])) {
            if ( ! empty($_GET['edit'])) {
                $products = $this->dataProducts->getRowById((int)$_GET['edit']);

                if (empty($products)) {
                    throw new Exception('Указанный товар не найден');
                }

                $panel->setTitle("Редактирование товара", '', $base_url);

            } else {
                $panel->setTitle("Добавление товара", '', $base_url);
            }

            $content[] = $view->getEdit($base_url, $products ?? null);

        } else {
            $content[] = $view->getTable($base_url);
        }


        $panel->setContent(implode('', $content));
        return $panel->render();
    }
}