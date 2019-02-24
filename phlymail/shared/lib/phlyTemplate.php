<?php
/**
 * phlyMail specifics for using the FXL Template Engine
 * It modifies the way, the caching is handled and automagically assigns
 * translation values according to their prefix as HTML safe or Javascript safe
 * values.
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage main application
 * @author  Matthias Sommerfeld
 * @copyright 2010-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.1.4 2015-03-30 
 */
require_once __DIR__.DIRECTORY_SEPARATOR.'fxl_template.inc.php';

/**
 * FXL TEMPLATE CACHE PLUGIN: ser v1.0.0
 * base class for the caching implementation
 */
class fxl_ser_template extends fxl_template
{
    protected $mode = '';
    protected $check = true;
    protected $force = false;
    protected $cache_suffix = '.cache';
    protected $cache_prefix = '';
    protected $halt_on_error = false;
    protected $cache_file = '';
    protected $template_file = '';
    protected $version = 1.0;
    protected $sub = false;

    public function __construct($template_file = '')
    {
        if ($template_file && !$this->set_template_file($template_file)) {
            return false;
        }
    }

    public function set_check($val)
    {
        $this->check = (bool) $val;
        return true;
    }

    public function set_mode($val)
    {
        if (in_array($val, array('md5'))) {
            $this->mode = $val;
            return true;
        }
        return false;
    }

    public function set_template($data, $type = 'file')
    {
        if ($type == 'file') {
            if (!file_exists($data) || !is_readable($data)) {
                die('Cannot open template file '.$data);
            }
            $this->tpl['template'] = file_get_contents($data);
            $this->template_file = $data;
            return true;
        }
        if ($type == 'string') {
            return (bool) $this->tpl['template'] = $data;
        }
        return false;
    }

    public function get_cache_file_name($template_file = '')
    {
        if ($template_file && ($this->cache_prefix || $this->cache_suffix)) {
            return $this->cache_prefix.$this->template_file.$this->cache_suffix;
        } elseif ($this->cache_file) {
            return $this->cache_file;
        } elseif ($this->template_file && ($this->cache_prefix || $this->cache_suffix)) {
            return $this->cache_prefix.$this->template_file.$this->cache_suffix;
        }
        return false;
    }

    public function set_cache_file($filename)
    {
        return (bool) $this->cache_file = $filename;
    }

    public function init()
    {
        if (!$this->tpl['template']) return false;
        if (!$cfile = $this->get_cache_file_name()) return false;

        if ($this->check && file_exists($cfile) && is_readable($cfile)) {
            $fp = fopen($cfile, 'r');
            $header_line = fgets($fp, 256);
            $header = explode(':', $header_line, 2);
            if (!isset($header[1]) || (chop($header[1]) != md5($this->tpl['template']))) $this->force = true;
            if (!$this->force) $ser = fread($fp, filesize($cfile) - strlen($header_line));
            fclose($fp);
        } elseif ($this->check && (!file_exists($cfile))) {
            $this->force = true;
        }
        if ($this->force) {
        	$head = 'md5:'.md5($this->tpl['template'])."\n";
            $this->parse($this->tpl['template']);
            $cached = serialize($this);
            file_put_contents($cfile, $head.$cached);
        } else {
            $cached = unserialize($ser);
            $this->tpl = $cached->tpl;
        }
    }

    protected function md5_check($val1, $val2)
    {
        return ($val1 == md5($val2));
    }

    protected function __clone()
    {
        $this->cache_file = ''; // cannot be the same
        $this->template_file = ''; // cannot be the same
        $this->sub = true;
    }

    public function version() { return $this->version; }
}

/**
 *  FXL CACHED TEMPLATE WRAPPER
 *
 *  working cache plugin example for fxl_template based on md5 and php serialize
 *  Feel free to customize it for your needs ;-)  Just use:
 *
 *  $tpl = new fxl_cached_template($tpl_file, $cache_file);
 *
 *  instead of:
 *
 *  $tpl = new fxl_template($tpl_file);
 *
 *  btw, you could also use:
 *  $tpl = new fxl_template($tpl_file, array('cache_file' => 'tpl.cache', 'cache_mode' => 'ser'));
 */
class fxl_cached_template extends fxl_ser_template
{
    /**
     * fxl_cached_template constructor
     *
     * make sure you have write permissions for your cache dir / cache file
     *
     * @param string $tpl template file
     * @param string $ctpl cached template file
     * @return object fxl_cached_template
     */
    function __construct($tpl, $ctpl = false)
    {
        if (!$ctpl) $ctpl = $tpl.'.cache';
        $this->set_template($tpl);
        $this->set_cache_file($ctpl);
        $this->set_mode('ser');
        $this->init();
    }
}

class phlyTemplate extends fxl_cached_template
{
    protected $msg = null;

    /**
     * @param string $filename  Full path to the template file
     * @param string $cachepath Base path where to cache the file
     * @param array $msg  Localization strings, which get assigned to the template
     */
    public function __construct($filename, $cachepath = null, $msg = null)
    {
        // Default: use the globally defined array
        if (is_null($msg)) {
            $msg = &$GLOBALS['WP_msg'];
        }
        foreach ($msg as $k => $v) { // Flatten array messages into scalars
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) $msg[$k.'_'.$k2] = $v2;
            }
        }
        $this->msg = $msg;
        if (is_null($cachepath) && isset($GLOBALS['_PM_']['path']['tplcache'])) {
            $cachepath = $GLOBALS['_PM_']['path']['tplcache'].basename($filename);
        }
        if (is_null($cachepath)) {
            $cachepath = $GLOBALS['_PM_']['path']['storage'].'/tplcache/'.basename($filename);
        }
        if (!is_null($cachepath)) {
            parent::__construct($filename, $cachepath);
        } else {
            parent::__construct($filename);
        }
    }

    /**
     * Allows to extent the $param property by custom keys.
     *
     * @param string|array $key  Array of key => value pairs or scalar param name
     *[@param mixed $value  If $key is a string, this holds the value, otherwise leave it NULL]
     */
    public function set_custom_param($key, $value = null)
    {
        if (!is_null($value) && is_scalar($key)) {
            $key = array($key => $value);
        }
        foreach ($key as $k => $v) {
            if ($k == 'clipleft' || $k == 'clipright') continue;
            $this->param[$k] = $v;
        }
    }

    /**
     * Retrieve the value of a custom param
     *
     * @param string $name  Name of the param to retrieve
     * @return mixed  NULL, if param could not be found, the value of it otherwise
     */
    public function get_custom_param($name)
    {
        return (isset($this->param[$name])) ? $this->param[$name] : null;
    }

    /**
     * Wrapper for getting a block, assigining it one or more placeholders and then
     * assigning the now filled block to its parent template again.
     * @param string $blk Name of the block
     * @param mixed $var See $this->assign()
     * @param mixed $val See $this->assign()
     * @since 2.0.5
     */
    public function fill_block($blk, $var, $val = '')
    {
        $b = $this->get_block($blk);
        $b->assign($var, $val);
        $this->assign($blk, $b);
    }

    /**
     * Variation of the original method: Replaces localization strings on the spot
     *
     * @param mixed $data  Either a file name reference or a string
     * @param string $type  Denotes the type of $data
     * @return bool
     */
    public function set_template($data, $type = 'file')
    {
        if ($type == 'file') {
            $data = file_get_contents($data);
        } elseif ($type == 'string') {
            // void
        } else {
            return false;
        }
        if (!is_null($this->msg)) {
            $data = preg_replace_callback('!%([hjt])%([a-zA-Z0-9\_]+)%!', array(&$this, 'localize'), $data);
        }
        $this->tpl['template'] = $data;
        return true;
    }

    public function localize($array)
    {
        if (isset($this->msg[$array[2]])) {
            if ($array[1] == 'h') return str_replace('\n', '<br>', htmlentities($this->msg[$array[2]], null, 'utf-8'));
            if ($array[1] == 'j') return phm_addcslashes($this->msg[$array[2]]);
            return $this->msg[$array[2]];
        }
        return false;
    }
}
