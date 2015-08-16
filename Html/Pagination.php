<?php namespace Sutil\Html;

use Sutil\Pagination\Paginator;

class Pagination
{
    protected static $_lang = array(
        'first_page' => 'First Page',
        'prev_page' => 'Previous Page',
        'next_page' => 'Next Page',
        'last_page' => 'Last Page',
        'items' => 'Items',
        'pages' => 'Pages'
    );

    public static function setLang($lang)
    {
        self::$_lang = array_merge(self::$_lang, $lang);
    }

    /**
     * @param $item_count
     * @param null $options
     * @param null $page_url
     * @return string
     *
     * 语言，样式
     */
    public static function render($item_count, $options = null, $page_url = null)
    {
        if ($item_count <= Paginator::DEFAULT_PER_PAGE) {
            return;
        }

        $page = (int)filter_input(INPUT_GET, 'page');
        $theme = empty($options['theme']) ? 'basic' : trim($options['theme']);
        unset($options['theme']);
        $paginator = new Paginator($item_count, $page, $options);

        $url = $page_url ?: $_SERVER['REQUEST_URI'];
        $part = explode('?', $url);
        $url = $part[0] . '?' . (empty($part[1]) ? '' : (trim(preg_replace('/(&?)page=\d+\&?/i', '$1', $part[1]), '&')));

        $html = '<div id="page">';
        if ($theme == 'full') {
            $html .= '<div class="state"><span class="items">'. self::$_lang['items'] . $paginator->getItemCount() .'</span> <span class="pages">'. self::$_lang['pages'] . $paginator->getPageCount() .'</span></div>';
        }
        $html .= '<ul>';
        $html .= '<li class="first"><a href="' . $url . 'page=1" title="'. self::$_lang['first_page'] .'">'. self::$_lang['first_page'] .'</a></li>';
        if ($paginator->hasPrev()) {
            $html .= '<li class="prev"><a href="' . $url . 'page=' . $paginator->getPrev() . '"title="'. self::$_lang['prev_page'] .'">'. self::$_lang['prev_page'] .'</a></li>';
        }
        foreach ($paginator->getRange() as $p) {
            $current = ($p == $page) ? ' class="current"' : '';
            $html .= '<li' . $current . '><a href="' . $url . 'page=' . $p . '">' . $p . '</a></li>';
        }
        if ($paginator->hasNext()) {
            $html .= '<li class="next"><a href="' . $url . 'page=' . $paginator->getNext() . '" title="'. self::$_lang['next_page'] .'">'. self::$_lang['next_page'] .'</a></li>';
        }
        $html .= '<li class="last"><a href="' . $url . 'page=' . $paginator->getPageCount() . '" title="'. self::$_lang['last_page'] .'">'. self::$_lang['last_page'] .'</a></li>';
        $html .= '</ul></div>';

        echo $html;
    }
}