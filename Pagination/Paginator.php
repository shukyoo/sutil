<?php namespace Sutil\Pagination;

use Sutil\Pagination\Style\StyleInterface;

class Paginator
{
    const DEFAULT_PER_PAGE = 20;
    const DEFAULT_PAGE_RANGE = 10;
    const DEFAULT_STYLE = 'sliding';

    protected $_current_page;
    protected $_per_page;
    protected $_item_count;
    protected $_page_count;
    protected $_page_range;
    /**
     * @var StyleInterface
     */
    protected $_style = null;


    /**
     * @param $item_count
     * @param int $current_page
     * @param array $options per_page default 20, page_range default 10, style default 'sliding'
     */
    public function __construct($item_count, $current_page = 1, $options = null)
    {
        $this->_per_page = empty($options['per_page']) ? self::DEFAULT_PER_PAGE : (int)$options['per_page'];
        $this->_item_count = (int)$item_count;
        $this->_page_count = ceil($this->_item_count / $this->_per_page);

        $current_page = (int)$current_page;
        if ($current_page < 1) {
            $current_page = 1;
        } elseif ($current_page > $this->_page_count) {
            $current_page = $this->_page_count;
        }
        $this->_current_page = $current_page;

        $page_range = empty($options['page_range']) ? self::DEFAULT_PAGE_RANGE : (int)$options['page_range'];
        $style = empty($options['style']) ? self::DEFAULT_STYLE : trim($options['style']);
        $this->setPageRange($page_range);
        $this->setStyle($style);
    }

    /**
     * Set page range
     */
    public function setPageRange($page_range)
    {
        $this->_page_range = (int)$page_range;
        return $this;
    }


    /**
     * Set page style
     * @param StyleInterface | string $style
     * @return $this
     */
    public function setStyle($style)
    {
        if (is_string($style)) {
            $style = '\\Sutil\\Pagination\\Style\\'. ucfirst(strtolower($style));
            $style = new $style();
        } elseif (!$style instanceof StyleInterface) {
            throw new \Exception('Page style must implement StyleInterface');
        }
        $this->_style = $style;
        return $this;
    }


    /**
     * To array
     * @return array
     */
    public function toArray()
    {
        return array(
            'current_page' => $this->_current_page,
            'per_page' => $this->_per_page,
            'item_count' => $this->_item_count,
            'page_count' => $this->_page_count,
            'page_range' => $this->_page_range,
            'has_prev' => $this->hasPrev(),
            'has_next' => $this->hasNext(),
            'prev' => $this->getPrev(),
            'next' => $this->getNext(),
            'range' => $this->getRange()
        );
    }


    /**
     * Get the styled range
     * @return array
     */
    public function getRange()
    {
        return $this->_style->getRange($this);
    }

    /**
     * @return int
     */
    public function getItemCount()
    {
        return $this->_item_count;
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return $this->_page_count;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->_per_page;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->_current_page;
    }

    /**
     * @return int
     */
    public function getPrev()
    {
        return $this->_current_page > 1 ? ($this->_current_page - 1) : 1;
    }

    /**
     * @return int
     */
    public function getNext()
    {
        return $this->_current_page < $this->_page_count ? ($this->_current_page + 1) : $this->_page_count;
    }

    /**
     * @return int
     */
    public function getPageRange()
    {
        return $this->_page_range;
    }

    /**
     * @return bool
     */
    public function hasPrev()
    {
        return $this->_current_page > 1;
    }

    /**
     * @return bool
     */
    public function hasNext()
    {
        return $this->_current_page < $this->_page_count;
    }

}