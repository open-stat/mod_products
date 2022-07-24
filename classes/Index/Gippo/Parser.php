<?php
namespace Core2\Mod\Products\Index\Gippo;
use PHPHtmlParser\Dom\Node\Collection;


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
        $dom_sections = $dom->find('.nav-list .nav-item');

        foreach ($dom_sections as $dom_section) {
            try {
                $dom_section_link = $dom_section->find('> a.nav-link');
                $section_title    = trim(html_entity_decode($dom_section_link->find('.nav-item-name')->text));
                $section_url      = trim(html_entity_decode($dom_section_link->getAttribute('href')));

                $dom_sections2 = $dom_section->find('.nav-lvl2-item');
                $categories    = [];

                foreach ($dom_sections2 as $dom_section2) {
                    $dom_sections3 = $dom_section2->find('.nav-lvl3-item');

                    if (count($dom_sections3) == 0) {
                        $dom_category_link = $dom_section2->find('> a.nav-lvl2-link');
                        $category_title    = trim(html_entity_decode($dom_category_link->find('.nav-lvl2-item-name')->text));
                        $category_url      = html_entity_decode(urldecode($dom_category_link->getAttribute('href')));

                        $categories[] = [
                            'title' => $category_title,
                            'url'   => $category_url,
                        ];

                    } else {
                        foreach ($dom_sections3 as $dom_section3) {
                            try {
                                $dom_category_link = $dom_section3->find('> a');
                                $category_title    = trim(html_entity_decode($dom_category_link->find('.nav-lvl3-item-name')->text));
                                $category_url      = html_entity_decode(urldecode($dom_category_link->getAttribute('href')));

                                $categories[] = [
                                    'title' => $category_title,
                                    'url'   => $category_url,
                                ];

                            } catch (\Exception $e) {
                                echo $e->getMessage() . PHP_EOL;
                                // ignore
                            }
                        }
                    }
                }

                $sections[] = [
                    'title'      => $section_title,
                    'url'        => $section_url,
                    'categories' => $categories,
                ];
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
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

        $products = [];

        $dom = new \PHPHtmlParser\Dom();
        $dom->loadStr($content);
        $dom_products = $dom->find('.catalog-panel__item');

        if ( ! empty($dom_products)) {
            foreach ($dom_products as $dom_product) {
                try {
                    $dom_product_link  = $dom_product->find('.product-card__body a.title');
                    $dom_product_price = $dom_product->find('.product-card__body .price');

                    $product_title    = $dom_product_link->text;
                    $product_url      = $dom_product_link->getAttribute('href');
                    $product_measure  = null;
                    $product_unit     = null;
                    $product_quantity = null;

                    $product_price_text = trim(html_entity_decode($dom_product_price->text));
                    preg_match('~([\d]+[\.,][\d]*)\s*([а-я]+)\./([а-я]*)~su', $product_price_text, $match);

                    if ( ! empty($match[1])) {
                        $product_price   = $match[1];
                        $product_measure = $match[3];
                    } else {
                        continue;
                    }

                    if (preg_match("~[\.,\sxх](\d+([\.,]\d+|))\s*(кг|г|гр|мг|л|м|мл|шт|ед)~iu", $product_title, $match)) {
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

                    $products[] = [
                        'title'    => trim(html_entity_decode($product_title)),
                        'url'      => trim(html_entity_decode($product_url)),
                        'price'    => $product_price,
                        'measure'  => trim(html_entity_decode($product_measure)),
                        'quantity' => $product_quantity,
                        'unit'     => $product_unit,
                        'currency' => 'byn',
                    ];
                } catch (\Exception $e) {
                    // ignore
                }
            }
        }

        return $products;
    }
}