<?php
use Core2\Mod\Products;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';
require_once "classes/autoload.php";


/**
 * @property \ModProductsController $modProducts
 * @property \ModProxyController    $modProxy
 */
class ModProductsCli extends Common {

    /**
     * @var \Zend_Config_Ini
     */
    static $products_config = null;


    /**
     * catalog.onliner.by 1 - Получение списка товаров
     * @throws Exception
     */
    public function getOnlinerLinks() {

        $responses = $this->modProxy->request('get', ['https://catalog.onliner.by'], [
            'level_anonymity' => ['elite', 'anonymous'],
            'max_try'         => 5,
            'debug'           => 'print',
        ]);

        $response = current($responses);


        $onliner_parser = new Products\Index\Onliner\Parser();
        $sections = $onliner_parser->parseSections($response['content']);

        print_r($sections);
    }


    /**
     * e-dostavka.by 1 - Получение списков товаров
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEdostavkaProducts() {

        $config          = $this->getModuleConfig('products');
        $config_sections = [];

        if ($config->source && $config->source->edostavka && $config->source->edostavka->sections) {
            $config_sections = $config->source->edostavka->sections->toArray();
        }

        if (empty($config_sections)) {
            return;
        }

        $time_start       = time();
        $time_max_minutes = 240;
        $count_errors_max = 400;
        $count_errors     = 0;
        $responses        = $this->modProxy->request('get', ['https://e-dostavka.by'], [
            'level_anonymity' => ['elite', 'anonymous'],
            'max_try'         => 50,
            //'debug'   => 'print',
        ]);
        $response = current($responses);

        if ($response['status'] == 'success' &&
            $response['http_code'] == '200' &&
            ! empty($response['content'])
        ) {
            $edostavka_parser = new Products\Index\Edostavka\Parser();
            $sections         = $edostavka_parser->parseMainSections($response['content']);

            $count_request_pages = 5;

            // TODO Добавить страничность https://e-dostavka.by/catalog/8673.html?page=2&_=1644769864039&lazy_steep=12
            // TODO Запрашивать товары сразу в разных разделах


            if ( ! empty($sections)) {
                foreach ($sections as $section) {

                    $load_section = false;

                    foreach ($config_sections as $config_section) {
                        if (trim($config_section['title']) === trim($section['title'])) {
                            $load_section = $config_section;
                        }
                    }

                    if ($load_section === false) {
                        continue;
                    }

                    if ( ! empty($section['categories'])) {
                        foreach ($section['categories'] as $category) {

                            $is_load_category = false;

                            if (empty($load_section['category'])) {
                                $is_load_category = true;

                            } else {
                                $config_categories = array_combine(
                                    array_values($load_section['category']),
                                    array_values($load_section['category'])
                                );

                                if (isset($config_categories[$category['title']])) {
                                    $is_load_category = true;
                                }
                            }


                            if ($is_load_category === false) {
                                continue;
                            }

                            try {
                                for ($i = 0; $i <= ($count_request_pages - 1); $i++) {

                                    $addresses = [];
                                    for ($page = ($i * $count_request_pages) + 1; $page <= ($i * $count_request_pages) + $count_request_pages; $page++) {
                                        $hash = time() . rand(1, 100);
                                        $addresses[] = "{$category['url']}?_={$hash}&lazy_steep={$page}";
                                    }

                                    // Ограничения выполнения задачи
                                    if ($count_errors > $count_errors_max) {
                                        echo $message = "Edostavka. Превышено максимальное количество ошибочных ответов: {$count_errors_max}";
                                        $this->sendErrorMessage($message);
                                        break 3;
                                    }

                                    if ($time_start + ($time_max_minutes * 60) < time()) {
                                        echo $message = "Edostavka. Превышено время выполнения задачи: {$time_max_minutes} минут";
                                        $this->sendErrorMessage($message);
                                        break 3;
                                    }

                                    $responses = $this->modProxy->request('get', $addresses, [
                                        'request' => [
                                            'headers' => [
                                                'X-Requested-With' => 'XMLHttpRequest',
                                            ],
                                        ],
                                        'level_anonymity' => ['elite', 'anonymous', 'non_anonymous'],
                                        'max_try'         => 5,
                                        'limit'           => 5
                                        //'debug' => 'print',
                                    ]);

                                    $is_load_next = false;

                                    if ( ! empty($responses)) {
                                        foreach ($responses as $response) {

                                            if ($response['status'] == 'success' &&
                                                $response['http_code'] == '200'
                                            ) {
                                                $response['content'] = trim($response['content']);

                                                if ( ! empty($response['content']) &&
                                                    strpos($response['content'], 'products_card') !== false
                                                ) {
                                                    $page = $this->modProducts->dataProductsPages->createRow([
                                                        'shop_name'    => 'edostavka',
                                                        'url'          => $response['url'],
                                                        'section'      => $section['title'],
                                                        'category'     => $category['title'],
                                                        'content'      => $response['content'],
                                                        'content_hash' => md5($response['content']),
                                                    ]);
                                                    $page->save();

                                                    if (preg_match("~&lazy_steep=" . ($i + $count_request_pages) . "$~", $response['url'])) {
                                                        $is_load_next = true;
                                                    }
                                                }

                                            } else {
                                                //$error_message = $response['error_message'] ?? $response['http_code'];
                                                //echo "ERROR: {$error_message}\n";
                                                $count_errors++;
                                            }
                                        }
                                    }

                                    if ( ! $is_load_next) {
                                        break;
                                    }
                                }

                            } catch (\Exception $e) {
                                // ignore
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * e-dostavka.by 2 - Обработка товаров
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    public function parseEdostavkaProducts() {

        $pages = $this->db->fetchAll("
            SELECT id,
                   section,
                   category,
                   content,
                   date_created
            FROM mod_products_pages
            WHERE is_parsed_sw = 'N' 
              AND shop_name = 'edostavka'
            LIMIT 3000 
        ");

        if (empty($pages)) {
            return;
        }


        $shop = $this->modProducts->dataProductsShops->getRowBySourceName('edostavka');

        if (empty($shop)) {
            $shop = $this->modProducts->dataProductsShops->createRow([
                'title'       => 'E-dostavka.by',
                'source_name' => 'edostavka',
            ]);
            $shop->save();
        }


        $parser = new Products\Index\Edostavka\Parser();

        foreach ($pages as $page) {
            try {
                $products = $parser->parseProducts($page['content']);
            } catch (\Exception $e) {
                echo "PAGE_ID = {$page['id']}: {$e->getMessage()}\n";
                continue;
            }

            if ( ! empty($products)) {
                foreach ($products as $product) {

                    $product_row = $this->modProducts->dataProducts->getRowByTitle($product['title']);

                    if (empty($product_row)) {
                        $product_row = $this->modProducts->dataProducts->createRow([
                            'section'  => $page['section'],
                            'category' => $page['category'],
                            'title'    => $product['title'],
                        ]);
                        $product_row->save();
                    }


                    $product_link = $this->modProducts->dataProductsLinks->getRowByProductIdUrl((int)$product_row->id, $product['url']);

                    if (empty($product_link)) {
                        $product_link = $this->modProducts->dataProductsLinks->createRow([
                            'product_id' => $product_row->id,
                            'url'        => $product['url'],
                        ]);
                        $product_link->save();
                    }


                    $product_price = $this->modProducts->dataProductsPrices->getRowByShopProductLinkDate(
                        (int)$shop->id,
                        (int)$product_row->id,
                        (int)$product_link->id,
                        date('Y-m-d', strtotime($page['date_created']))
                    );

                    if (empty($product_price)) {
                        $product_price = $this->modProducts->dataProductsPrices->createRow([
                            'shop_id'    => $shop->id,
                            'product_id' => $product_row->id,
                            'link_id'    => $product_link->id,
                            'date_mark'  => date('Y-m-d', strtotime($page['date_created'])),
                            'price'      => $product['price'],
                            'measure'    => $product['measure'],
                            'unit'       => $product['unit'],
                            'quantity'   => $product['quantity'],
                            'currency'   => $product['currency'],
                        ]);

                    } else {
                        $product_price->price    = $product['price'];
                        $product_price->measure  = $product['measure'];
                        $product_price->unit     = $product['unit'];
                        $product_price->quantity = $product['quantity'];
                        $product_price->currency = $product['currency'];
                    }

                    $product_price->save();
                }
            }

            $where = $this->db->quoteInto('id = ?', $page['id']);
            $this->db->update('mod_products_pages', [
                'is_parsed_sw' => 'Y',
            ], $where);
        }
    }


    /**
     * gippo-market.by 1 - Получение списков товаров
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGippoProducts() {

        if ( ! $this->isLoadShop('gippo')) {
            return;
        }

        $time_max_minutes = 240;
        $count_errors_max = 400;
        $count_request    = 5;


        $base_uri     = "https://gippo-market.by";
        $time_start   = time();
        $count_errors = 0;
        $responses    = $this->modProxy->request('get', ["{$base_uri}/catalog/?getAjaxCatalogMenu=Y"], [
            'level_anonymity' => ['elite', 'anonymous'],
            'max_try'         => 50,
            //'debug'   => 'print',
            'request' => [
                'connection'         => 20,
                'connection_timeout' => 20,
                'headers'            => [
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
            ],
        ]);
        $response = current($responses);


        if ($response['status'] != 'success' ||
            $response['http_code'] != '200' ||
            empty($response['content'])
        ) {
            return;
        }

        //$response['content'] = file_get_contents(__DIR__ . '/assets/gippo.html');

        $gippo_parser = new Products\Index\Gippo\Parser();
        $sections     = $gippo_parser->parseMainSections($response['content']);




        if ( ! empty($sections)) {
            $category_sets    = [];
            $category_sets_i  = 0;
            $category_sets2   = [];
            $category_sets2_i = 0;

            // Создание наборов с разделами
            foreach ($sections as $section) {

                if ( ! $this->isLoadShop('gippo', $section['title'])) {
                    continue;
                }

                if ( ! empty($section['categories'])) {
                    foreach ($section['categories'] as $category) {

                        if ( ! $this->isLoadShop('gippo', $section['title'], $category['title'])) {
                            continue;
                        }

                        if (empty($category_sets[$category_sets_i])) {
                            $category_sets[$category_sets_i] = [
                                'addresses'  => [],
                                'categories' => [],
                            ];
                        }
                        $url = "{$base_uri}{$category['url']}?PAGEN_1=1&nav_ajax=y";

                        $category_sets[$category_sets_i]['addresses'][]  = $url;
                        $category_sets[$category_sets_i]['categories'][] = [
                            'section_title'  => $section['title'],
                            'category_title' => $category['title'],
                            'url'            => $url,
                        ];

                        if (count($category_sets[$category_sets_i]['addresses']) >= $count_request) {
                            $category_sets_i++;
                        }
                    }
                }
            }


            foreach ($category_sets as $category_set) {
                $responses = $this->modProxy->request('get', $category_set['addresses'], [
                    'request' => [
                        'connection'         => 20,
                        'connection_timeout' => 20,
                        'headers' => [
                            'X-Requested-With' => 'XMLHttpRequest',
                        ],
                    ],
                    'level_anonymity' => ['elite', 'anonymous', 'non_anonymous'],
                    'max_try'         => 5,
                    'limit'           => 5
                    //'debug' => 'print',
                ]);


                foreach ($category_set['categories'] as $category) {
                    foreach ($responses as $response) {

                        if ($response['url'] == $category['url']) {
                            if ($response['status'] == 'success' && $response['http_code'] == '200') {
                                if (strpos($response['content'], 'catalog-panel__item') !== false) {
                                    $page_row = $this->modProducts->dataProductsPages->createRow([
                                        'shop_name'    => 'gippo',
                                        'url'          => $response['url'],
                                        'section'      => $category['section_title'],
                                        'category'     => $category['category_title'],
                                        'content'      => $response['content'],
                                        'content_hash' => md5($response['content']),
                                    ]);
                                    $page_row->save();


                                    $count_pages = 1;
                                    preg_match_all('~data-page-num="([0-9]+)"~', $response['content'], $matches);

                                    if ( ! empty($matches[1])) {
                                        $count_pages = max($matches[1]);
                                    }

                                    // Создание наборов со страницами разделав
                                    if ($count_pages >= 2) {
                                        $pages = range(2, $count_pages);

                                        foreach ($pages as $page) {
                                            if (empty($category_sets2[$category_sets2_i])) {
                                                $category_sets2[$category_sets2_i] = [
                                                    'addresses'  => [],
                                                    'categories' => [],
                                                ];
                                            }
                                            $url = preg_replace('~\?PAGEN_1=[0-9]+~', "?PAGEN_1={$page}", $category['url']);

                                            $category_sets2[$category_sets2_i]['addresses'][]  = $url;
                                            $category_sets2[$category_sets2_i]['categories'][] = [
                                                'section_title'  => $category['section_title'],
                                                'category_title' => $category['category_title'],
                                                'url'            => $url,
                                            ];

                                            if (count($category_sets2[$category_sets2_i]['addresses']) >= $count_request) {
                                                $category_sets2_i++;
                                            }
                                        }
                                    }

                                } else {
                                    // пустая страница
                                }

                            } else {
                                $count_errors++;
                            }

                            break;
                        }
                    }
                }


                // Ограничения выполнения задачи
                if ($count_errors > $count_errors_max) {
                    echo $message = "Гиппо. Превышено максимальное количество ошибочных ответов: {$count_errors_max}";
                    $this->sendErrorMessage($message);
                    return;
                }

                if ($time_start + ($time_max_minutes * 60) < time()) {
                    echo $message = "Гиппо. Превышено время выполнения задачи: {$time_max_minutes} минут";
                    $this->sendErrorMessage($message);
                    return;
                }
            }

            // Запросы страниц
            foreach ($category_sets2 as $category_set2) {
                $responses = $this->modProxy->request('get', $category_set2['addresses'], [
                    'request' => [
                        'connection'         => 20,
                        'connection_timeout' => 20,
                        'headers' => [
                            'X-Requested-With' => 'XMLHttpRequest',
                        ],
                    ],
                    'level_anonymity' => ['elite', 'anonymous', 'non_anonymous'],
                    'max_try'         => 5,
                    'debug' => 'print',
                ]);

                foreach ($category_set2['categories'] as $category) {
                    foreach ($responses as $response) {

                        if ($response['url'] == $category['url']) {
                            if ($response['status'] == 'success' && $response['http_code'] == '200') {
                                if (strpos($response['content'], 'catalog-panel__item') !== false) {
                                    $page_row = $this->modProducts->dataProductsPages->createRow([
                                        'shop_name'    => 'gippo',
                                        'url'          => $response['url'],
                                        'section'      => $category['section_title'],
                                        'category'     => $category['category_title'],
                                        'content'      => $response['content'],
                                        'content_hash' => md5($response['content']),
                                    ]);
                                    $page_row->save();

                                } else {
                                    // пустая страница
                                }

                            } else {
                                $count_errors++;
                            }

                            break;
                        }
                    }
                }


                // Ограничения выполнения задачи
                if ($count_errors > $count_errors_max) {
                    echo $message = "Превышено максимальное количество ошибочных ответов: {$count_errors_max}";
                    $this->sendErrorMessage($message);
                    return;
                }

                if ($time_start + ($time_max_minutes * 60) < time()) {
                    echo $message = "Превышено время выполнения задачи: {$time_max_minutes} минут";
                    $this->sendErrorMessage($message);
                    return;
                }
            }
        }
    }


    /**
     * gippo-market.by 2 - Обработка товаров
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    public function parseGippoProducts() {

        $pages = $this->db->fetchAll("
            SELECT id,
                   section,
                   category,
                   content,
                   date_created
            FROM mod_products_pages
            WHERE is_parsed_sw = 'N'
              AND shop_name = 'gippo'
            LIMIT 3000 
        ");

        if (empty($pages)) {
            return;
        }


        $shop = $this->modProducts->dataProductsShops->getRowBySourceName('gippo');

        if (empty($shop)) {
            $shop = $this->modProducts->dataProductsShops->createRow([
                'title'       => 'Гиппо',
                'source_name' => 'gippo',
            ]);
            $shop->save();
        }


        $parser = new Products\Index\Gippo\Parser();

        foreach ($pages as $page) {
            try {
                $products = $parser->parseProducts($page['content']);
            } catch (\Exception $e) {
                echo "PAGE_ID = {$page['id']}: {$e->getMessage()}\n";
                continue;
            }

            if ( ! empty($products)) {
                foreach ($products as $product) {

                    $product_row = $this->modProducts->dataProducts->getRowByTitle($product['title']);

                    if (empty($product_row)) {
                        $product_row = $this->modProducts->dataProducts->createRow([
                            'section'  => $page['section'],
                            'category' => $page['category'],
                            'title'    => $product['title'],
                        ]);
                        $product_row->save();
                    }

                    $url          = "https://gippo-market.by{$product['url']}";
                    $product_link = $this->modProducts->dataProductsLinks->getRowByProductIdUrl((int)$product_row->id, $url);

                    if (empty($product_link)) {
                        $product_link = $this->modProducts->dataProductsLinks->createRow([
                            'product_id' => $product_row->id,
                            'url'        => $url,
                        ]);
                        $product_link->save();
                    }


                    $product_price = $this->modProducts->dataProductsPrices->getRowByShopProductLinkDate(
                        (int)$shop->id,
                        (int)$product_row->id,
                        (int)$product_link->id,
                        date('Y-m-d', strtotime($page['date_created']))
                    );

                    if (empty($product_price)) {
                        $product_price = $this->modProducts->dataProductsPrices->createRow([
                            'shop_id'    => $shop->id,
                            'product_id' => $product_row->id,
                            'link_id'    => $product_link->id,
                            'date_mark'  => date('Y-m-d', strtotime($page['date_created'])),
                            'price'      => $product['price'],
                            'measure'    => $product['measure'],
                            'unit'       => $product['unit'],
                            'quantity'   => $product['quantity'],
                            'currency'   => $product['currency'],
                        ]);

                    } else {
                        $product_price->price    = $product['price'];
                        $product_price->measure  = $product['measure'];
                        $product_price->unit     = $product['unit'];
                        $product_price->quantity = $product['quantity'];
                        $product_price->currency = $product['currency'];
                    }

                    $product_price->save();
                }
            }

            $where = $this->db->quoteInto('id = ?', $page['id']);
            $this->db->update('mod_products_pages', [
                'is_parsed_sw' => 'Y',
            ], $where);
        }
    }


    /**
     * @param string $shop_name
     * @param string $section_name
     * @param string $category_name
     * @return bool
     * @throws Zend_Config_Exception
     */
    private function isLoadShop(string $shop_name, string $section_name = '', string $category_name = ''): bool {

        if (is_null(self::$products_config)) {
            self::$products_config = $this->getModuleConfig('products');
        }

        $config_sections = [];

        if (self::$products_config->source &&
            self::$products_config->source->{$shop_name} &&
            self::$products_config->source->{$shop_name}->sections
        ) {
            $config_sections = self::$products_config->source->{$shop_name}->sections->toArray();
        }

        if (empty($config_sections)) {
            return false;
        }

        if (empty($section_name)) {
            return true;
        }

        $load_section = false;

        foreach ($config_sections as $config_section) {

            if (isset($config_section['title']) &&
                trim($config_section['title']) === trim($section_name)
            ) {
                $load_section = $config_section;
            }
        }

        if ($load_section === false) {
            return false;
        }

        if (empty($category_name)) {
            return true;
        }


        $is_load_category = false;

        if (empty($load_section['category'])) {
            $is_load_category = true;

        } else {
            $config_categories = array_combine(
                array_values($load_section['category']),
                array_values($load_section['category'])
            );

            if (isset($config_categories[$category_name])) {
                $is_load_category = true;
            }
        }


        if ($is_load_category === false) {
            return false;
        }

        return true;
    }
}
