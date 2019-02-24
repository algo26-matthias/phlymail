<?php
/**
 * fxl_template libraries
 *
 * a library for template processing
 *
 * @package fxl_template
 */

# FXL TEMPLATE v2.1.1 (2010-09-02)
#
# Copyright (C) 2005-2010
# Fever XL Steffen Reinecke
# Heinrich-Heine-Str. 14, 10179 Berlin, Germany
#
# E-Mail: sreinecke@feverxl.de
# Web: http://www.feverxl.de/
# Doc: http://www.feverxl.de/template/
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# http://www.gnu.org/copyleft/lesser.html
#

# IMPORTANT NOTES for v2.1:
# * special note for php 5.2.0+ users:
#   be sure you have enough memory and stack for PHP and have a look
#   at pcre.backtrack_limit and pcre.recursion_limit
# * removed beta version of fxl_ser_template from distribution
#   feel free to test the new alpha version of the fxl_memcached extension

###########################
# THE FXL TEMPLATE ENGINE #
###########################

/**
 * fxl_template
 *
 * a very tiny but flexible library for template processing
 *
 * Have a look into the examples directory
 * for some useful examples incl. template files.
 *
 * Goals of this library:
 *
 * - plain text/html templates without any control mechanisms
 *   like loops, php code or sql queries
 *
 * - easy to learn template markup (only 2 elements)
 *
 * - flexibility: you can more or less assign everything to everywhere
 *
 * - speed
 *
 * - easy handling: It's just this tiny file you have to include
 *   to use fxl template
 *
 * @version 2.1.1
 * @package fxl_template
 */
class fxl_template {
    /**
     * @ignore
     * @var array options
     */
    protected $tpl = array(
        'param' => array(
            'clipleft' => '{', 'clipright' => '}',
            'trim_block' => 0
        ),
        'block' => array(),
        'place' => array(),
        'template' => ''
    );

   /**
    * fxl_template constructor
    *
    * example:
    * <code>
    * $tpl = new fxl_template('template.tpl');
    * </code>
    *
    * @param string $content file name
    * @param array $options not in use
    * @return object fxl_template
    */
    public function __construct ($content = false, $options = false)
    {
        if (!file_exists($content) || !is_readable($content)) return false;
        $this->set_template($content, 'file');
        if ($this->tpl['template']) $this->init();
    }

   /**
    * finish rendering process
    *
    * @since 1.0.0
    * @return string rendered template
    */
    public function get_content()
    {
        $tmp = preg_replace('/'.$this->tpl['param']['clipleft'].'([a-z0-9\-\_]+)'.$this->tpl['param']['clipright'].'/i', '', $this->get());
        return preg_replace('/'.$this->tpl['param']['clipleft'].'\\\/i', $this->tpl['param']['clipleft'], $tmp);
    }

   /**
    * displays the output
    *
    * same as:
    * <code>
    * echo $tpl->get_content();
    * </code>
    * @since 1.0.0
    * @return void
    */
    public function display()
    {
        echo $this->get_content();
    }

   /**
    * new assignment
    *
    * complete example:
    * <code>
    * $tpl = new fxl_template('address_form.tpl');
    * $tpl_address = $tpl->get_block('address');
    * $tpl_address->assign('name', 'Peter');
    * $tpl_address->assign(array('zip' => '10179', 'town' => 'Berlin'));
    * $tpl->assign('address', $tpl_address);
    * $tpl->display();
    * </code>
    *
    * usage examples:
    * <code>
    * $tpl->assign(<string var>, <string val>);
    * $tpl->assign(<string var>, <object fxl_template>);
    * $tpl->assign(<string blockname>, <object fxl_template>);
    * $tpl->assign(<array [var=val,var=val]>);
    * $tpl->assign(<string blockname>); *
    * $tpl->assign(<string blockname>, val);
    * </code>
    *
    * * since version 2.1.1
    *
    * @since 1.0.0
    *
    * @param string|array $var string: name of the block / place holder OR array with key/value pairs
    * @param string|object $val string OR object (another FXL Template object)
    * @return void
    */
    public function assign($var, $val = null)
    {
        if (is_array($var)) foreach($var as $k => $v) $this->tpl['place'][$k][] = $v;
        elseif (is_object($val)) $this->tpl['place'][$var][] = clone $val;
        elseif (strlen($var) && is_null($val)) $this->assign_block($var);
        elseif (strlen($var)) $this->tpl['place'][$var][] = $val;
    }

   /**
    * assigns a whole block in place
    *
    * <code>
    * $tpl->assign_block('blockname');
    *
    * same as:
    * $tpl_block = $tpl->get_block('blockname');
    * $tpl->assign('blockname', $tpl_block);
    *
    * same as:
    * $tpl->assign('blockname');
    * </code>
    *
    * @param string $blockname block name
    * @return void
    */
    public function assign_block($blockname)
    {
        $block = $this->get_block($blockname);
        $this->assign($blockname, $block);
    }

   /**
    * fetching a block for assignments
    *
    * @since 1.0.0
    * @param string $blockname block name
    * @return fxl_template
    */
    public function get_block($blockname)
    {
        if (isset($this->tpl['block'][$blockname]) && is_object($this->tpl['block'][$blockname])) return clone $this->tpl['block'][$blockname];
        elseif (isset($this->halt_on_error) && $this->halt_on_error) {
            die("Block: $blockname not found!");
        }
        else return false;
    }

   /**
    * checks a block exists or not
    *
    * @since 2.0.0
    * @param string $blockname block name
    * @return bool
    */
    public function block_exists($blockname)
    {
        if (isset($this->tpl['block'][$blockname]) && is_object($this->tpl['block'][$blockname])) return true;
        else return false;
    }

   /**
    * refresh block for new assignments
    *
    * example:
    * <code>
    * $names = array('peter', 'nicole');
    * $tpl_name = $tpl->get_block('name_block');
    * foreach ($names as $name) {
    *     $tpl_name->assign('name', $name);
    *     $tpl->assign('name_block', $tpl_name);
    *     $tpl_name->clear();
    * }
    * </code>
    *
    * @since 1.0.0
    * @return void
    */
    public function clear()
    {
        $this->tpl['place'] = array();
    }

    /**
     * pre-rendered content - not all replacements done
     *
     * @ignore
     * @return string pre-rendered template
     */
    public function get()
    {
        if (count($this->tpl['place'])) {
            foreach ($this->tpl['place'] as $k => $v) {
                $replace = '';
                for ($i = 0; $i < count($this->tpl['place'][$k]); $i++) {
                    $replace .= (is_object($this->tpl['place'][$k][$i])) ? $this->tpl['place'][$k][$i]->get() : $this->tpl['place'][$k][$i];
                }
                $this->tpl['template'] = ($this->tpl['param']['trim_block'] == 2) ? preg_replace("/[\n\r\s]*".$this->tpl['param']['clipleft'].$k.$this->tpl['param']['clipright']."[\n\r\s]/", trim($replace), $this->tpl['template']) : str_replace($this->tpl['param']['clipleft'].$k.$this->tpl['param']['clipright'], $replace, $this->tpl['template']);
            }
        }
        return $this->tpl['template'];
    }

    # INTERNAL METHODS #

   /**
    * sets the template
    *
    * @since 2.0.0
    * @ignore
    * @param string $data enum: file name, content string
    * @param string $type enum: file, string
    * @return bool
    */
    public function set_template($data, $type = 'file')
    {
        if ($type == 'file') {
            if (($this->tpl['template'] = file_get_contents($data))) return true;
        }
        elseif ($type == 'string') {
            $this->tpl['template'] = $data;
            return true;
        }
        return false;
    }

   /**
    * template initialization
    *
    * @ignore
    * @since 2.0.0
    * @return void
    */
    public function init()
    {
    	return $this->parse($this->tpl['template']);
    }

    /**
     * parser
     *
     * @ignore
     * @param string $tplstring
     * @return void
     */
    protected function parse($tplstring = '')
    {
        $this->tpl['template'] = $tplstring;
        $m = $this->_match_block();
        for ($x = 0; $x < count($m[0]); $x++) {
        	$this->tpl['template'] = $this->parse_block($m[1][$x], $this->tpl['template']);
        	$this->tpl['block'][$m[1][$x]] = clone $this;
            $this->tpl['block'][$m[1][$x]]->tpl['place'] = array();
            $this->tpl['block'][$m[1][$x]]->tpl['block'] = array();
            $this->tpl['block'][$m[1][$x]]->parse(($this->tpl['param']['trim_block']) ? trim($m[2][$x]) : $m[2][$x]);
        }
    }

    /**
     * block replacer
     *
     * @ignore
     * @param string $blockname
     * @param string $tplstring
     * @return string
     */
    protected function parse_block($blockname = '', $template = '')
    {
        $blockname = preg_quote($blockname);
        return preg_replace(($this->tpl['param']['trim_block']) ? "/[\s\r\n]+<!--\sSTART\s(" .$blockname. ")\s-->.*<!--\sEND\s(" .$blockname. ")\s-->[\s\r\n]+/ms":"/<!--\sSTART\s(" .$blockname. ")\s-->.*<!--\sEND\s(" .$blockname. ")\s-->/ms",$this->tpl['param']['clipleft'].$blockname.$this->tpl['param']['clipright'], $template);
    }

    /**
     * block finder
     *
     * @ignore
     * @return array matches
     */
    protected function _match_block()
    {
        preg_match_all("/<!--\sSTART\s([a-z0-9_]+)\s-->(.*)<!--\sEND\s(\\1)\s-->/mis", $this->tpl['template'], $m);
        return $m;
    }
}

/**
 * FXL Template - Memcache Extension (alpha, 0.5)
 *
 * for use with FXL Template v2.1+
 *
 * php memcache documentation:
 * http://www.php.net/manual/en/book.memcache.php
 *
 * @package fxl_template
 */

class fxl_memcached_template extends fxl_template {
    protected $check = 'always';
    protected $validate = 'date';
    protected $memcache_prefix = 'fxlt';
    protected $memcache_flag = null;
    protected $memcache_expire = null;
    public $cached = false;
    protected $cache_md5;
    protected $cache_date;

    /**
     * Constructor
     *
     * possible options:
     * - check
     * value: 'never' never check for a new version of the template (fastest)
     * value: 'always' always check for a newer version of the template (default, recommended)
     * - validate
     * value: 'date' (fastest)
     * value: 'md5' (default, recommended)
     *
     * @param string $filename template filename
     * @param Memcache $memcache Memcache object
     * @param string $check (never|always) optional
     * @param string $validate (date|md5) optional
     * @param array $mc_option (0=>key, 1=>flag, 2=>expire) optional
     */
    function __construct($filename, $memcache, $check = null, $validate = null, $mc_option = null) {
        if (in_array($check, array('always', 'never'))) $this->check = $check;
        if (in_array($validate, array('date', 'md5'))) $this->validate = $validate;
        if (isset($mc_option[1])) $this->memcache_flag = $mc_option[1];
        if (isset($mc_option[2])) $this->memcache_expire = $mc_option[2];
        if (!file_exists($filename) || !is_readable($filename)) return false;
        $key = (isset($mc_option[0]) && !is_null($mc_option[0])) ? $mc_option[0] : $this->memcache_prefix.realpath($filename);
        if ($memcache->get($key)) {
            $data = $memcache->get($key);
            if ($this->check == 'always') {
                if ($this->validate == 'md5') {
                    $content = file_get_contents($filename);
                    if (md5($content) == $data->cache_md5) {
                        $this->tpl = $data->tpl;
                    }
                }
                elseif ($data->cache_date == filemtime($filename)) {
                    $this->tpl = $data->tpl;
                }
            }
            else $this->tpl = $data->tpl;
        }
        if (!$this->tpl['template']) {
            $content = file_get_contents($filename);
            $this->set_template($content, 'string');
            $this->init();
            $this->cache_md5 = md5($content);
            $this->cache_date = filemtime($filename);
            $memcache->set($key, $this, $this->memcache_flag, $this->memcache_expire);
        }
        else $this->cached = true;
    }
}
