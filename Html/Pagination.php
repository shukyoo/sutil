<?php namespace Sutil\Html;

use Sutil\Pagination\Paginator;

class Pagination
{
    protected $_page = 1;
    protected $_item_count;
    protected $_options;
    
    protected $_theme = 'basic';
    protected $_url;
    
    protected $_trans = array(
        'first_page' => 'First Page',
        'prev_page' => 'Previous Page',
        'next_page' => 'Next Page',
        'last_page' => 'Last Page',
        'items' => 'Items',
        'pages' => 'Pages'
    );
    
    public function __construct($item_count, $options = null, $trans = null)
    {
        $this->_page = (int)filter_input(INPUT_GET, 'page');
        if (isset($options['page'])) {
            $this->_page = (int)$options['page'];
            unset($options['page']);
        }
        
        $this->_item_count = $item_count;

        if (!empty($options['theme'])) {
            $this->setTheme($options['theme']);
            unset($options['theme']);
        }
        
        if (!empty($options['url'])) {
            $this->setUrl($options['url']);
            unset($options['url']);
        } else {
            $this->setUrl($_SERVER['REQUEST_URI']);
        }
        
        $this->_options = $options;
        
        if (!empty($trans)) {
            $this->setTrans($trans);
        }
    }

    /**
     * @param $trans
     * @return $this
     */
    public function setTrans($trans)
    {
        $this->_trans = array_merge($this->_trans, $trans);
        return $this;
    }

    /**
     * @param $theme
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->_theme = $theme;
        return $this;
    }

    /**
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->_page_url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->_item_count <= Paginator::DEFAULT_PER_PAGE) {
            return;
        }
        $paginator = new Paginator($this->_item_count, $this->_page, $this->_options);

        $part = explode('?', $this->_url);
        $url = $part[0] . '?' . (empty($part[1]) ? '' : (trim(preg_replace('/(&?)page=\d+\&?/i', '$1', $part[1]), '&')));

        $html = '<div id="page">';
        if ($this->_theme == 'full') {
            $html .= '<div class="state"><span class="items">'. $this->_trans['items'] . $paginator->getItemCount() .'</span> <span class="pages">'. $this->_trans['pages'] . $paginator->getPageCount() .'</span></div>';
        }
        $html .= '<ul>';
        $html .= '<li class="first"><a href="' . $url . 'page=1" title="'. $this->_trans['first_page'] .'">'. $this->_trans['first_page'] .'</a></li>';
        if ($paginator->hasPrev()) {
            $html .= '<li class="prev"><a href="' . $url . 'page=' . $paginator->getPrev() . '"title="'. $this->_trans['prev_page'] .'">'. $this->_trans['prev_page'] .'</a></li>';
        }
        foreach ($paginator->getRange() as $p) {
            $current = ($p == $this->_page) ? ' class="current"' : '';
            $html .= '<li' . $current . '><a href="' . $url . 'page=' . $p . '">' . $p . '</a></li>';
        }
        if ($paginator->hasNext()) {
            $html .= '<li class="next"><a href="' . $url . 'page=' . $paginator->getNext() . '" title="'. $this->_trans['next_page'] .'">'. $this->_trans['next_page'] .'</a></li>';
        }
        $html .= '<li class="last"><a href="' . $url . 'page=' . $paginator->getPageCount() . '" title="'. $this->_trans['last_page'] .'">'. $this->_trans['last_page'] .'</a></li>';
        $html .= '</ul></div>';

        echo $html;
    }
}