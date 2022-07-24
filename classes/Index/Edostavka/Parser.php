<?php
namespace Core2\Mod\Products\Index\Edostavka;


class Parser {

    /**
     * Получение разделов магазина
     * @param string $content
     * @return array
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    public function parseMainSections(string $content): array {

        $sections = [];

        $dom = new \PHPHtmlParser\Dom();
        $dom->loadStr($content);
        $dom_sections = $dom->find('.main .index_page .catalog_menu > li');

        foreach ($dom_sections as $dom_section) {
            $dom_section_link = $dom_section->find('> a');
            $section_title    = trim(html_entity_decode($dom_section_link->text));
            $section_url      = trim(html_entity_decode($dom_section_link->getAttribute('href')));

            $dom_categories = $dom_section->find('> .catalog_menu__subsubmenu > ul > li > ul > li');
            $categories     = [];

            foreach ($dom_categories as $dom_category) {
                $dom_category_link = $dom_category->find('> a');
                $category_title    = trim(html_entity_decode($dom_category_link->text));
                $category_url      = html_entity_decode(urldecode($dom_category_link->getAttribute('href')));

                $categories[] = [
                    'title' => $category_title,
                    'url'   => $category_url,
                ];
            }

            $sections[] = [
                'title'      => $section_title,
                'url'        => $section_url,
                'categories' => $categories,
            ];
        }

        return $sections;
    }

    /**
     * Получение разделов магазина
     * @param string $content
     * @return array
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    public function parseSections(string $content): array {

        $sections = [];

        $dom = new \PHPHtmlParser\Dom();
        $dom->loadStr($content);
        $dom_sections = $dom->find('.content .rubrics_table > .item');

        foreach ($dom_sections as $dom_section) {
            try {
                $dom_section_link = $dom_section->find('> .title a');
                $section_title    = trim(html_entity_decode($dom_section_link->text));
                $section_url      = trim(html_entity_decode($dom_section_link->getAttribute('href')));

                $dom_categories = $dom_section->find('> .item');
                $categories     = [];

                foreach ($dom_categories as $dom_category) {
                    $dom_category_link = $dom_category->find('> .title a');
                    $category_title    = trim(html_entity_decode($dom_category_link->text));
                    $category_url      = html_entity_decode(urldecode($dom_category_link->getAttribute('href')));

                    $categories[] = [
                        'title' => $category_title,
                        'url'   => $category_url,
                    ];
                }

                $sections[] = [
                    'title'      => $section_title,
                    'url'        => $section_url,
                    'categories' => $categories,
                ];
            } catch (\Exception $e) {
                // ignore
            }
        }

        return $sections;
    }


    /**
     * @param string $content
     * @return array
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    public function parseProducts(string $content): array {

        $sections = [];

        $dom = new \PHPHtmlParser\Dom();
        $dom->loadStr($content);
        $dom_products = $dom->find('.products_card');

        if ( ! empty($dom_products)) {
            foreach ($dom_products as $dom_product) {
                try {
                    $dom_product_link    = $dom_product->find('.title a');
                    $dom_product_price   = $dom_product->find('.price_byn .price');
                    $dom_product_measure = $dom_product->find('.form_elements input');

                    $product_title    = trim(html_entity_decode($dom_product_link->text));
                    $product_url      = trim(html_entity_decode($dom_product_link->getAttribute('href')));
                    $product_measure  = trim(html_entity_decode($dom_product_measure->getAttribute('data-measure')));
                    $product_unit     = null;
                    $product_quantity = null;

                    if (preg_match("~[\.,\sxх](\d+([\.,]\d+|))\s*(кг|г|гр|м|мг|л|мл|шт|ед)~iu", $product_title, $match)) {
                        $product_unit     = trim($match[3]) ?: null;
                        $product_quantity = trim($match[1]) ?: null;
                        $product_quantity = str_replace(',', '.', $product_quantity);
                        $product_quantity = trim($product_quantity, '.');


                        if (($product_quantity >= 2020 && $product_quantity <= 2030) &&
                            $product_unit == 'г'
                        ) {
                            $product_quantity  = null;
                            $product_unit      = null;
                            $product_title_cut = str_replace($match[0], '', $product_title);

                            if (preg_match("~[\.,\sxх](\d+([\.,]\d+|))\s*(кг|г|гр|м|мг|л|мл|шт|ед)~iu", $product_title_cut, $match2)) {
                                $product_unit     = trim($match2[3]) ?: null;
                                $product_quantity = trim($match2[1]) ?: null;
                                $product_quantity = str_replace([',', ' '], ['.', ''], $product_quantity);
                                $product_quantity = trim($product_quantity, '.');
                            }
                        }

                        if (empty($product_quantity)) {
                            $product_quantity = null;
                            $product_unit     = null;

                            if (preg_match("~[\.,\sxх](\d+\s*\d+([\.,]\d+|))\s*(кг|г|гр|м|мг|л|мл|шт|ед)~iu", $product_title, $match2)) {
                                $product_unit     = trim($match2[3]) ?: null;
                                $product_quantity = trim($match2[1]) ?: null;
                                $product_quantity = str_replace([',', ' '], ['.', ''], $product_quantity);
                                $product_quantity = trim($product_quantity, '.');
                            }
                        }
                    }


                    $product_price = $dom_product_price->text;
                    $product_price .= '.' . $dom_product_price->find('.cent')->text;
                    $product_price = preg_replace('~[^\d\.]*~', '', $product_price);
                    $product_price = trim(html_entity_decode($product_price));

                    $sections[] = [
                        'title'    => $product_title,
                        'url'      => $product_url,
                        'price'    => $product_price,
                        'measure'  => $product_measure,
                        'quantity' => $product_quantity,
                        'unit'     => $product_unit,
                        'currency' => 'byn',
                    ];
                } catch (\Exception $e) {
                    // ignore
                }
            }
        }

        return $sections;
    }
}