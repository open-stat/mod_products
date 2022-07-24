<?php
namespace Core2\Mod\Products\Index\Onliner;


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
    public function parseSections(string $content): array {

        $sections = [];

        $dom = new \PHPHtmlParser\Dom();
        $dom->loadStr($content);
        $dom_sections = $dom->find('.catalog-navigation-list__category .catalog-navigation-list__aside-item');

        foreach ($dom_sections as $dom_section) {
            $section_title  = trim(html_entity_decode($dom_section->find('> .catalog-navigation-list__aside-title')->text));
            $dom_categories = $dom_section->find('.catalog-navigation-list__dropdown-item');
            $categories     = [];

            foreach ($dom_categories as $dom_category) {
                $category_title = trim(html_entity_decode($dom_category->find('.catalog-navigation-list__dropdown-title')->text));
                $category_url   = html_entity_decode(urldecode($dom_category->getAttribute('href')));

                $categories[] = [
                    'title' => $category_title,
                    'url'   => $category_url,
                ];
            }

            $sections[] = [
                'title'      => $section_title,
                'categories' => $categories,
            ];
        }

        return $sections;
    }
}