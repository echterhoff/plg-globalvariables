<?php

/**
 * @version  3.4
 * @Project  GLOABL VARIABLES
 * @author   Lars Echterhoff
 * @package
 * @copyright Copyright (C) 2015 Lars Echterhoff
 * @license  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
 * @description This plugin gives you the possibility to decrease information fragmentation. Store often used information as variable and reuse it in what ever content within joomla you like.
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

define("GLOBAL_VARS_DEV", true);
define("GLOBAL_VARS_OPENINGBRACE", '{');
define("GLOBAL_VARS_CLOSINGBRACE", '}');

if (GLOBAL_VARS_DEV) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

if (!function_exists("dd")) {

    function dd()
    {
        if (!GLOBAL_VARS_DEV)
            return;
        $args = func_get_args();
        echo "<pre>";
        foreach ($args as $arg) {
            if (is_array($arg) || is_object($arg)) {
                print_r($arg);
            } else {
                var_dump($arg);
            }
        }
        echo "</pre>";
    }

}
if (!function_exists("dd_entities")) {

    function dd_entities()
    {
        if (!GLOBAL_VARS_DEV)
            return;
        $args = func_get_args();
        echo "<pre>";
        foreach ($args as $arg) {
            if (is_array($arg) || is_object($arg)) {
                echo htmlentities(utf8_decode(print_r($arg, true)));
            } else {
                var_dump($arg);
            }
        }
        echo "</pre>";
    }

}

jimport('joomla.plugin.plugin');
jimport('joomla.form.helper');

$version = new JVersion;

if (JFactory::getApplication()->isAdmin()) {
    JFormHelper::addFieldPath(JPATH_PLUGINS . '/content/' . basename(__FILE__, '.php') . '/form/fields');
    JHtml::_('bootstrap.framework');
    JHtml::_('jquery.framework');
    if (!$version->isCompatible("3.4.0")) {
        JHtml::script(substr(dirname(__FILE__), strlen(JPATH_SITE) + 1) . '/form/fields/blockrepeat.js');
    }
    JHtml::script(substr(dirname(__FILE__), strlen(JPATH_SITE) + 1) . '/form/fields/repeatable-radio-mod.js');
    JHtml::stylesheet(substr(dirname(__FILE__), strlen(JPATH_SITE) + 1) . '/css/repeatable.css');
}

class plgContentGlobalVariables extends JPlugin
{

    /**
     * Contains the variable vault
     * @var array
     */
    private $variables = array();
    private $article;
    private $parameter = array();
    private $mounted_sources = null;

    /**
     * Plugin constructor
     *
     * @param object $subject
     * @param object $params
     */
    function plgContentGlobalVariables(&$subject, $params)
    {
        define("GV_SOURCE_DEFAULT", "default_globalvariables_resource");
        define("GV_SOURCE_INTERNAL", "internal_globalvariables_resource");
        parent::__construct($subject, $params);
        $this->_plugin = JPluginHelper::getPlugin('content', 'globalvariables');


        $gv_params = $this->params;

        $this->parameter['sourceconfiguration'] = $this->rotateRepeatableData($gv_params->get("sourceconfiguration"));
        $this->parameter['direct_variable_input'] = $this->rotateRepeatableData($gv_params->get("direct_variable_input"));
        $this->parameter['direct_variable_input'] = $gv_params->get("direct_variable_input");

        $this->parameter['replace_variables_iteration_limit'] = $gv_params->get("replace_variables_iteration_limit");

        $this->parameter['variable_style'] = array();
        $this->parameter['variable_style']['curly'] = ($gv_params->get("variable_style_curly") ? true : false);
        $this->parameter['variable_style']['doublecurly'] = ($gv_params->get("variable_style_doublecurly") ? true : false);

        $this->parameter['replace_undefined_variables'] = ($gv_params->get("replace_undefined_variables") ? true : false);
        $this->parameter['replace_variables_debug'] = ($gv_params->get("replace_variables_debug") ? true : false);
        $this->parameter['highlight_variables_debug'] = ($gv_params->get("highlight_variables_debug") ? true : false);

        if (JFactory::getApplication()->isSite()) {
            $this->mountSources();
        }
    }

    public function getParameter($name)
    {
        if (isset($this->parameter[$name])) {
            return $this->parameter[$name];
        }
        return false;
    }

    private function mountSources()
    {
        if ($this->params->get("direct_variable_input")) {
            $div = json_decode($this->getParameter("direct_variable_input"));
            if (isset($div->varname) && $div->varname) {
                $div = array_combine($div->varname, $div->varvalue);
                $this->mounted_sources[GV_SOURCE_INTERNAL] = new globalVariablesSource(GV_SOURCE_INTERNAL, $div);
            }
        } else {
            $this->mounted_sources[GV_SOURCE_INTERNAL] = new globalVariablesSource(GV_SOURCE_INTERNAL, array());
        }

        if ($this->getParameter('sourceconfiguration')) {
            foreach ($this->getParameter('sourceconfiguration') as $data) {
                if ($data['sourceactive']) {
                    if (!$data['sourcealias'] && $data['sourcedefault']) {
                        $sourcealias = GV_SOURCE_DEFAULT;
                        $this->mounted_sources[$sourcealias] = new globalVariablesSource($sourcealias, ($data['sourceurl'] ? $data['sourceurl'] : ($data['variablesource'] ? $data['variablesource'] : false)));
                    } elseif ($data['sourcealias'] && !$data['sourcedefault']) {
                        $sourcealias = $data['sourcealias'];
                        $this->mounted_sources[$sourcealias] = new globalVariablesSource($sourcealias, ($data['sourceurl'] ? $data['sourceurl'] : ($data['variablesource'] ? $data['variablesource'] : false)));
                    } elseif (!$data['sourcealias'] && !$data['sourcedefault']) {
                        $sourcealias = $data['sourcealias'];
                    }
                }
            }
        }
    }

    /**
     * Rotate the data retrieved from field repeatable for better iteration over it.
     * @param string $json_input_string
     * @return array
     */
    private function rotateRepeatableData($json_input_string)
    {
        $json = json_decode($json_input_string, true);
        $tmp = array();
        if (is_array($json)) {
            foreach ($json as $field => $data) {
                foreach ($data as $row => $value) {
                    $tmp[$row][$field] = $value;
                }
            }
        }
        return $tmp;
    }

    /**
     * Event hook
     * @param object $context
     * @param object $article
     * @param object $params
     * @param int $limitstart
     */
    function onContentPrepare($context, &$article, &$params, $limitstart)
    {
        $this->replace_array($article);
    }

    /**
     * Starts the replacing for a string or an array (array is not done yet)
     * @param string/array $data
     * @return inputtype $data
     */
    function replace_array(&$data)
    {
        try {
            jimport('joomla.document.html.html');
            if (is_object($data)) {
                if (isset($data->title))
                    $data->title = $this->start_replace($data->title, "Title");
                if (isset($data->introtext))
                    $data->introtext = $this->start_replace($data->introtext, "Introtext");
                if (isset($data->text))
                    $data->text = $this->start_replace($data->text, "Text");
            } elseif (is_array($data)) {
                if (isset($data["title"]))
                    $data->title = $this->start_replace($data["title"], "Title");
                if (isset($data["introtext"]))
                    $data["introtext"] = $this->start_replace($data["introtext"], "Introtext");
                if (isset($data["text"]))
                    $data["text"] = $this->start_replace($data["text"], "Text");
            } elseif (is_string($data)) {
                $data = $this->start_replace($data, "Datastring");
            }
        } catch (Exception $e) {
            JFactory::getApplication()
                    ->enqueueMessage(
                            JText::_(
                                    "Error in Plugin: <b>" . $this->_plugin->name . "</b><br>" . $e->getMessage()
                            ), 'error');
        }
        return $data;
    }

    /**
     * Start replacing the defined variables
     * @param string $string
     * @return string
     */
    function start_replace($string, $info = false)
    {
        $match = array();
        $proto_rx_string = '\{\{([^\} ]+) ?([^\}]*?)\}\}|\{([^ \}]*) ?([^\}]*?)\}([^\{]*?)\{/(?:\\3)\}';

        $opening_brace = GLOBAL_VARS_OPENINGBRACE;
        $closing_brace = GLOBAL_VARS_CLOSINGBRACE;
        $rx_delimiter = '#';
        $bo = preg_quote($opening_brace, $rx_delimiter);
        $bonq = $opening_brace;
        $bc = preg_quote($closing_brace, $rx_delimiter);
        $bcnq = $closing_brace;

        $proto_string = str_replace('}', $bcnq, str_replace('{', $bonq, str_replace('\}', $bc, str_replace('\{', $bo, $proto_rx_string))));
        $varmatch_rx = $rx_delimiter . $proto_string . $rx_delimiter . 'is';

        $block = new globalVariablesString($string);
        $replace_variables_iteration_limit = $this->getParameter("replace_variables_iteration_limit");

        $matches = array();
        while (preg_match($varmatch_rx, $block, $matches, PREG_OFFSET_CAPTURE)) {
            $match = new GlobalVariablesMatch($matches);
            $this->attachSourceTo($match);
            $this->execute_replacement($block, $match);
            if ((!isset($i) ? $i = 0 : $i++) > $replace_variables_iteration_limit) {
                break;
            }
        }

        return str_replace("[GLOBAL_VARS_CLOSINGBRACE]", GLOBAL_VARS_CLOSINGBRACE, str_replace("[GLOBAL_VARS_OPENINGBRACE]", GLOBAL_VARS_OPENINGBRACE, $block));
    }

    /**
     *
     * @param globalVariablesString $block
     * @param globalVariablesMatch $match
     */
    private function execute_replacement(globalVariablesString $block, globalVariablesMatch $match)
    {
        $block->replace($match);
    }

    /**
     *
     * @param type $alias
     * @return boolean
     */
    public function hasSource($alias)
    {
        if (isset($this->mounted_sources[$alias])) {
            return true;
        }
    }

    /**
     *
     * @param type $alias
     * @return globalVariablesSource
     */
    public function getSource($alias)
    {
        if ($this->hasSource($alias)) {
            return $this->mounted_sources[$alias];
        } else {
            return $this->mounted_sources[GV_SOURCE_INTERNAL];
        }
    }

    /**
     *
     * @param globalVariablesMatch $match
     * @return globalVariablesSource
     */
    private function sourceSelector(globalVariablesMatch $match)
    {
        if (!isset($this->mounted_sources[$match->source])) {
            return null;
        }
        return $this->mounted_sources[$match->source];
    }

    /**
     *
     * @param globalVariablesMatch $match
     */
    private function attachSourceTo(globalVariablesMatch $match)
    {
        $match->setPlugin($this);
    }

    private function InjectHeadCode()
    {
        //Currently no use and just for remind purposes still in here
        return;
    }

    /**
     *
     * @param string $string
     * @return string
     */
    public function processValue($string)
    {


        $start_tag = '';
        $close_tag = '';
        if ($this->getParameter("highlight_variables_debug")) {
            $start_tag = '<span style="color:red;background-color:white;box-shadow: 0px 0px 0px 2px #F00;">';
            $close_tag = '</span>';
        }

        return $start_tag . $string . $close_tag;
    }

}

class globalVariablesMatch
{

    public $type;
    public $variabletag;
    public $varname;
    public $key = false;
    public $parameter;
    public $offset = -1;
    public $length = -1;
    public $language;
    public $source;

    /**
     *
     * @var plgContentGlobalVariables
     */
    public $plugin;

    /**
     *
     * @var globalVariablesSource
     */
    public $data_source;

    const TYPE_CURLY = "curly";
    const TYPE_DOUBLECURLY = "doublecurly";

    public function __construct(array $match)
    {
        $keys = array(
            0, // 0
            'doublecurlytag_varname', // 1
            'doublecurlytag_parameter', // 2
            'curlytag_key', // 3
            'curlytag_parameter', // 4
            'curlytag_varname' // 5
        );

        $keys = array_slice($keys, 0, count($match));
        $match = array_combine($keys, $match);
        $loaded = false;

        $this->variabletag = $match[0][0];
        $this->language = JFactory::getDocument()->getLanguage();

        if (isset($match['doublecurlytag_varname'][1]) && $match['doublecurlytag_varname'][1] >= 0) {
            $this->offset = $match[0][1];
            $this->length = strlen($this->variabletag);
            $this->type = self::TYPE_DOUBLECURLY;
            $this->varname = $match['doublecurlytag_varname'][0];
            $this->parameter = new globalVariablesMatchParameters($match['doublecurlytag_parameter'][0]);
            $loaded = true;
        } elseif (isset($match['curlytag_key'][1]) && $match['curlytag_key'][1] >= 0 && $match['curlytag_varname'][1] >= 0) {
            $this->offset = $match[0][1];
            $this->length = strlen($this->variabletag);
            $this->type = self::TYPE_CURLY;
            $this->varname = $match['curlytag_varname'][0];
            $this->parameter = new globalVariablesMatchParameters($match['curlytag_parameter'][0]);
            $loaded = true;
        }
        if (isset($this->parameter->lang)) {
            $this->language = $this->parameter->lang;
        }

        if ($loaded) {
            $this->source = $this->parameter->source;
        }
    }

    /**
     *
     * @return \globalVariablesMatch
     */
    private function setSource()
    {
        $this->data_source = $this->plugin->getSource($this->source);
        return $this;
    }

    /**
     *
     * @param string $varname
     * @param string $language
     * @return string
     */
    private function getKeyFromSource($varname, $language)
    {
        if ($this->plugin->getSource(GV_SOURCE_INTERNAL)->hasKey($varname, $language)) {
            return $this->plugin->getSource(GV_SOURCE_INTERNAL)->getKey($varname, $language);
        } elseif ($this->plugin->getSource($this->source)->hasKey($varname, $language)) {
            return $this->plugin->getSource($this->source)->getKey($varname, $language);
        }
    }

    /**
     *
     * @param array $get
     * @param array $post
     * @return string
     */
    public function getValue($get = array(), $post = array())
    {
        if ($this->parameter->query && $this->plugin->getSource($this->source)->isQueryable()) {
            $post = array_replace_recursive($post, $this->parameter->parameters, array(
                "_varname" => $this->varname,
                "_language" => $this->language,
                "_charset" => JFactory::getDocument()->getCharset(),
                "_type" => JFactory::getDocument()->getType()
            ));
            return $this->plugin->processValue($this->plugin->getSource($this->source)->queryKey($this->varname, $get, $post));
        } elseif (!$this->parameter->query && $this->plugin->getSource($this->source) && $this->plugin->getSource($this->source)->hasKey($this->varname, $this->language)) {
            return $this->plugin->processValue($this->getKeyFromSource($this->varname, $this->language));
        }

        if (!$this->plugin->getSource($this->source) && $this->plugin->getParameter("replace_variables_debug") === true) {
            return '<span class="alert alert-warning">Missing source "' . $this->source . '": ' . $this->escapeOpener($this->variabletag) . '</span>';
        } elseif ($this->plugin->getSource($this->source) && !$this->data_source->hasKey($this->varname, $this->language) && $this->plugin->getParameter("replace_variables_debug") === true) {
            return '<span class="alert alert-warning">Missing variable value: ' . $this->escapeOpener($this->variabletag) . '</span>';
        }

        if ($this->plugin->getParameter("replace_undefined_variables") === false) {
            return $this->plugin->processValue(str_replace(GLOBAL_VARS_CLOSINGBRACE, "[GLOBAL_VARS_CLOSINGBRACE]", str_replace(GLOBAL_VARS_OPENINGBRACE, "[GLOBAL_VARS_OPENINGBRACE]", $this->variabletag)));
        }

        return "";
    }

    /**
     *
     * @param plgContentGlobalVariables $plugin
     * @return \globalVariablesMatch
     */
    public function setPlugin(plgContentGlobalVariables $plugin)
    {
        $this->plugin = $plugin;
        $this->setSource();
        return $this;
    }

    /**
     *
     * @param string $string
     * @return string
     */
    private function escapeOpener($string)
    {
        $string = str_replace(GLOBAL_VARS_OPENINGBRACE, sprintf("&#x%x;", $this->ordutf8(GLOBAL_VARS_OPENINGBRACE)), $string);
        $string = str_replace(GLOBAL_VARS_CLOSINGBRACE, sprintf("&#x%x;", $this->ordutf8(GLOBAL_VARS_CLOSINGBRACE)), $string);
        return $string;
    }

    function ordutf8($string, &$offset = null)
    {
        $code = ord(substr($string, $offset, 1));
        if ($code >= 128) {        //otherwise 0xxxxxxx
            if ($code < 224)
                $bytesnumber = 2;                //110xxxxx
            else if ($code < 240)
                $bytesnumber = 3;        //1110xxxx
            else if ($code < 248)
                $bytesnumber = 4;    //11110xxx
            $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
            for ($i = 2; $i <= $bytesnumber; $i++) {
                $offset ++;
                $code2 = ord(substr($string, $offset, 1)) - 128;        //10xxxxxx
                $codetemp = $codetemp * 64 + $code2;
            }
            $code = $codetemp;
        }
        $offset += 1;
        if ($offset >= strlen($string))
            $offset = -1;
        return $code;
    }

}

class globalVariablesMatchParameters
{

    public $parameters;
    public $parameter_string;

    public function __construct($string)
    {
        $this->parameter_string = $string;

        preg_match_all('#(?:[a-z_]+[a-z0-9\[\]_-]*=([\'"]+)((?:\\\\1|[^\\1])*?)\1|\b[a-z_]+[a-z0-9_-]*\b)#is', $string, $matches);

        $param_set = array();

        if ($matches[0]) {
            foreach ($matches[0] as $pos => $parameter) {
                list($key, ) = explode("=", $parameter, 2);
                if (!$matches[2][$pos]) {
                    $param_set[$key] = true;
                } else {
                    $param_set[$key] = $matches[2][$pos];
                }
            }
        }
        $this->parameters = $param_set;
    }

    public function __get($name)
    {
        $name = strtolower($name);
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        } else {
            if ($name === "source") {
                return GV_SOURCE_DEFAULT;
            }
            return null;
        }
    }

    public function __isset($name)
    {
        $name = strtolower($name);
        $result = (preg_match('#(?:' . $name . '=([\'"]+)((?:\\\\1|[^\\1])*?)\1|\b' . $name . '\b)#is', $this->parameter_string, $match) ? true : false);

        if (isset($match[2])) {
            $this->parameters[$name] = $match[2];
        } elseif (isset($match[0]) && $name == strtolower($match[0])) {
            $this->parameters[$name] = true;
        }
        return $result;
    }

}

class globalVariablesString
{

    public $string = "";
    public $length = 0;
    public $position = 0;
    public $matches = 0;
    public $replacements = 0;
    public $passes = 0;

    public function __construct($string)
    {
        $this->string = $string;
        $this->length = strlen($string);
    }

    public function __toString()
    {
        return (string) $this->string;
    }

    public function replace(globalVariablesMatch $match)
    {
        $this->string = substr_replace($this->string, $match->getValue(), $match->offset, $match->length);
    }

}

class globalVariablesParse
{

    const TYPE_JOOMLA_ARTICLE = "globalVariablesParseAdapterText";
    const TYPE_TEXT = "globalVariablesParseAdapterText";
    const TYPE_ARRAY = "globalVariablesParseAdapterArray";
    const TYPE_INI = "ini";
    const TYPE_YAML = "yaml";

    public $variables = array();
    public $supported_types = array(
        self::TYPE_TEXT,
        self::TYPE_ARRAY,
        self::TYPE_YAML
    );

    public function __construct($type, $stream)
    {
        $parser = new $type();
        /* @var $parser globalVariablesParseAdapterMaster */
        $parser->setStream($stream);
        $this->variables = $parser->getVariablesArray();
    }

    public function __isset($name)
    {
        if (isset($this->variables[$name])) {
            return true;
        }
    }

    public function has($name)
    {
        return $this->__isset($name);
    }

    public function get($name)
    {
        if ($this->__isset($name)) {
            return $this->variables[$name];
        }
        return "";
    }

}

interface globalVariablesAdapterInterface
{

    public function setStream($string);

    public function getVariablesArray();
}

abstract class globalVariablesParseAdapterMaster implements globalVariablesAdapterInterface
{

    protected $stream_data;
    protected $variables = array();

}

class globalVariablesParseAdapterText extends globalVariablesParseAdapterMaster
{

    public function setStream($string)
    {
        $this->stream_data = $string;
    }

    public function getVariablesArray()
    {
        $this->variables = $this->parseStream($this->stream_data);

//        $this->stream_data = $string;
        return $this->variables;
    }

    private function parseStream($string)
    {
//        dd("Step 1:", htmlentities($string));

        $var_declaration = '(^|<[a-z]*>[\r\n]</[a-z]*>|[>\b\r\n] *?)([a-z][^= ]*)[ \n]*?=[ \n]*?';

        $variable = array();
        $heredoc_tag = "<<<";
        $heredoc_tag = implode("|", array(
            preg_quote($heredoc_tag),
            preg_quote(htmlspecialchars($heredoc_tag))
                )
        );

        $rx_data_full = 0;
        $rx_data_first_esc_character = 1;
        $rx_data_varname = 2;
        $rx_data_quote = 3;
        $rx_data_content = 4;
        $rx_content = 0;
        $rx_offset = 1;

        $heredoc_rx = '#' . $var_declaration . '(?:' . $heredoc_tag . ')([^ \r\n]*)(?:[ \r\n]|</?[a-z]*/?>(?:[\r\n]</[a-z]*>)?)(.*?)(?:</?[a-z]*/?>)*?\\' . $rx_data_quote . ';*?#is';
        $heredocs = preg_match_all($heredoc_rx, $string, $matches, PREG_OFFSET_CAPTURE + PREG_SET_ORDER);
        if ($heredocs) {
            foreach ($matches as $match) {
                $escaped_heredoc_block = htmlspecialchars($match[$rx_data_content][$rx_content]);
//                dd("Step 2a (Heredoc):", $match);
                $string = str_replace($match[$rx_data_full][$rx_content], $match[$rx_data_first_esc_character][$rx_content] . $match[$rx_data_varname][$rx_content] . '="' . $escaped_heredoc_block . '";', $string);
            }
        } else {
//            dd("Step 2a (Heredoc):", "Nothing to process");
        }

//        dd("Step 2b:", $string);
        $match = array();

        $strEsc = preg_quote("'\"");
//        $rx_parse_var_str = '#' . $var_declaration . '([' . $strEsc . '])([^\\' . $rx_data_quote . ']*?)\\' . $rx_data_quote . ';?#is';
        $rx_parse_var_str = '#' . $var_declaration . '([' . $strEsc . '])((?:<[^>]*>|\\\\\\' . $rx_data_quote . '|[^\\' . $rx_data_quote . '])*?)\\' . $rx_data_quote . ';?#is';
//        dd("rx_parse_var_str", $rx_parse_var_str);
        preg_match_all($rx_parse_var_str, $string, $match, PREG_SET_ORDER);

//        dd("Step 3:", $match);
        if ($match) {
            foreach ($match as $set) {
                $variable[$set[$rx_data_varname]] = stripcslashes(html_entity_decode($set[$rx_data_content]));
//                dd($set[$rx_data_varname], stripcslashes(html_entity_decode($set[$rx_data_content])));
            }
        }
        return $variable;
    }

}

class globalVariablesParseAdapterArray extends globalVariablesParseAdapterMaster
{

    public function setStream($string)
    {
        $this->stream_data = $string;
    }

    public function getVariablesArray()
    {
        $this->variables = $this->parseStream($this->stream_data);
        return $this->variables;
    }

    private function parseStream($string)
    {
        return $string;
    }

}

class globalVariablesSource
{

    public $alias;
    public $type;
    public $sourceurl;

    /**
     *
     * @var globalVariablesParse
     */
    private $variables;
    private $data_stream;
    private $is_mounted = false;
    private $is_queryable = false;

    const SOURCETYPE_JOOMLA_ARTICLE = 'joomla_article';
    const SOURCETYPE_HTTP = 'http';
    const SOURCETYPE_FTP = 'ftp';
    const SOURCETYPE_ARRAY = 'internal';
    const SOURCETYPE_UNKNOWN = 'unknown';

    /**
     *
     * @param string $alias
     * @param mixed $url
     */
    public function __construct($alias, $url)
    {
        $this->alias = $alias;
//        $this->sourceurl = (!is_array($url)?$url:'array');
        if (is_array($url)) {
            $this->sourceurl = 'array';
            $this->data_stream = $url;
            $this->variables = new globalVariablesParse(globalVariablesParse::TYPE_ARRAY, $this->data_stream);
        } else {
            $this->sourceurl = $url;
        }
        $this->type = $this->identifySource($url);
    }

    public function isQueryable()
    {
        return $this->is_queryable;
    }

    public function mountSource()
    {
        return $this->parseSource();
    }

    private function parseSource()
    {
        if (!$this->is_mounted) {
            $this->loadSourceData();
            if (is_string($this->data_stream)) {
                $this->variables = new globalVariablesParse(globalVariablesParse::TYPE_TEXT, $this->data_stream);
            }
            $this->is_mounted = true;
        }
        return $this->is_mounted;
    }

    private function getLanguageKey($key, $language = false)
    {
        if (!$language) {
            return $key;
        }

        $key_language_extended = $key . '.' . $language;

        if ($this->variables && $this->variables->has($key) && $this->variables->has($key_language_extended)) {
            return $key_language_extended;
        } elseif ($this->variables && $this->variables->has($key) && !$this->variables->has($key_language_extended)) {
            return $key;
        }
        return $key;
    }

    public function hasKey($key, $language = false)
    {
        $this->parseSource();

        $key = $this->getLanguageKey($key, $language);

        if ($this->variables) {
            return $this->variables->has($key);
        }
    }

    public function getKey($key, $language = false)
    {
        $this->parseSource();

        $key = $this->getLanguageKey($key, $language);

        if ($this->variables) {
            return $this->variables->get($key);
        }
    }

    public function queryKey($key, $get = array(), $post = array())
    {
        if (!$this->is_queryable) {
            return $this->getKey($key, $post["_language"]);
        }

        $get = http_build_query($get);
        $data = http_build_query($post);

        $context_options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n"
                . "Content-Length: " . strlen($data) . "\r\n",
                'content' => $data
            )
        );

        $context = stream_context_create($context_options);
        $returnvalue = file_get_contents($this->sourceurl . ($get ? '?' . $get : ''), false, $context);
        return $returnvalue;
    }

    /**
     *
     * @param mixed $url
     * @return const SOURCETYPE
     */
    private function identifySource($url)
    {
        if (is_numeric($url)) {
            return self::SOURCETYPE_JOOMLA_ARTICLE;
        } elseif (is_array($url)) {
            return self::SOURCETYPE_ARRAY;
        } elseif (strtolower(substr($url, 0, 6)) == "https:") {
            $this->is_queryable = true;
            return self::SOURCETYPE_HTTP;
        } elseif (strtolower(substr($url, 0, 5)) == "http:") {
            $this->is_queryable = true;
            return self::SOURCETYPE_HTTP;
        } elseif (strtolower(substr($url, 0, 4)) == "ftp:") {
            return self::SOURCETYPE_FTP;
        } else {
            return self::SOURCETYPE_UNKNOWN;
        }
    }

    /**
     *
     * @return boolean
     */
    private function loadSourceData()
    {
        if ($this->type === self::SOURCETYPE_JOOMLA_ARTICLE) {
            $this->data_stream = $this->loadJoomlaArticle($this->sourceurl);
        } elseif ($this->type === self::SOURCETYPE_HTTP) {
            $this->data_stream = $this->loadHttpSource($this->sourceurl);
        }
        return true;
    }

    /**
     *
     * @param string $url
     * @return string
     */
    private function loadHttpSource($url)
    {
        return file_get_contents($url);
    }

    /**
     *
     * @param integer $id
     * @return string
     */
    private function loadJoomlaArticle($id)
    {
        try {
            JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_content/models', 'ContentModel');
            $model = JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request' => true));
            $appParams = JFactory::getApplication()->getParams();
            $model->setState('params', $appParams);
            $item = $model->getItem($id);
            return ($item->fulltext ? $item->fulltext : $item->introtext);
        } catch (Exception $e) {
            return "";
        }
    }

}
