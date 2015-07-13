<?php namespace Sutil\Html;


class Form
{
    protected $_data = array();

    public function __construct($data = array())
    {
        if (!empty($data)) {
            $this->setData($data);
        }
    }

    /**
     * Set Form data
     */
    public function setData($data)
    {
        $this->_data = $data;
    }

    /**
     * ===========================
     * From inputs
     * ===========================
     */

    public function text($name, $value = null, array $options = [])
    {
        echo '<input type="text" name="' . $name . '" id="' . $name . '" value="' . $this->_value($name, $value) . '"' . $this->_options($options) . ' />';
    }

    public function hidden($name, $value = null, array $options = [])
    {
        echo '<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $this->_value($name, $value) . '"' . $this->_options($options) . ' />';
    }

    public function email($name, $value = null, array $options = [])
    {
        echo '<input type="email" name="' . $name . '" id="' . $name . '" value="' . $this->_value($name, $value) . '"' . $this->_options($options) . ' />';
    }

    public function password($name, $value = null, array $options = [])
    {
        echo '<input type="password" name="' . $name . '" id="' . $name . '" value="' . $this->_value($name, $value) . '"' . $this->_options($options) . ' />';
    }

    public function textarea($name, $value = null, array $options = [])
    {
        echo '<textarea name="' . $name . '" id="' . $name . '"' . $this->_options($options) . '>' . $this->_value($name, $value) . '</textarea>';
    }

    public function select($name, $data, $value = null, array $options = [])
    {
        $str = '<select name="' . $name . '" id="' . $name . '"' . $this->_options($options) . '>';
        foreach ($data as $k => $v) {
            $selected = ($k == $this->_value($name, $value)) ? ' selected' : '';
            $str .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
        }
        $str .= '</select>';
        return $str;
    }

    /**
     * $value two style:
     * '1'    => name="test[]" value="1"
     * '10:2' => name="test[10]" value="2"
     */
    public function checkbox($name, $value = 1, $checked = false, array $options = [])
    {
        $checked = $checked ? ' checked' : '';
        if (true == ($poz = strpos($value, ':'))) {
            $name .= '['. substr($value, 0, $poz) .']';
            $value = substr($value, $poz + 1);
        } else {
            $name .= '[]';
        }
        echo '<input type="checkbox" name="' . $name . '" value="' . $value . '"' . $checked . $this->_options($options) . ' />';
    }

    public function radio($name, $value = null, $checked = false, array $options = [])
    {
        $checked = $checked ? ' checked' : '';
        echo '<input type="radio" name="' . $name . '" value="' . $this->_value($name, $value) . '"' . $checked . $this->_options($options) . ' />';
    }

    public function checkboxGroup($name, $data, $checked_list = null, array $options = [])
    {
        $checked_list = $this->_value($name, $checked_list);
        if (!is_array($checked_list)) {
            $checked_list = explode(',', $checked_list);
        }
        $str = '';
        foreach ($data as $k => $v) {
            $checked = in_array($k, $checked_list);
            $str .= '<label>' . $this->checkbox($name, $k, $checked, $options) . $v . '</label>';
        }
        return $str;
    }

    public function radioGroup($name, $data, $value = null, array $options = array())
    {
        $str = '';
        foreach ($data as $k => $v) {
            $checked = ($k == $this->_value($name, $value)) ? ' checked' : '';
            $str .= '<label>' . $this->radio($name, $k, $checked, $options) . $v . '</label>';
        }
        return $str;
    }

    /**
     * Get the value
     * @param $name
     * @param $value
     * @return string
     */
    protected function _value($name, $value)
    {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        } elseif ($value === null) {
            return '';
        } else {
            return $value;
        }
    }

    /**
     * Concat options
     * @param array $options
     * @param array $apend
     * @return string
     */
    protected function _options($options)
    {
        if (empty($options)) {
            return '';
        }
        $str = '';
        foreach ($options as $k => $v) {
            $str .= " {$k}='{$v}'";
        }
        return $str;
    }
}