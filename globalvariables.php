<?php

/**
 * @version  1.4
 * @Project  GLOABL VARIABLES
 * @author   Lars Echterhoff
 * @package
 * @copyright Copyright (C) 2014 Echterhoff Medientechnik
 * @license  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
 * @description This plugin gives you the possibility to decrease information fragmentation. Store often used information as variable and reuse it in what ever content within joomla you like.
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
jimport('joomla.plugin.plugin');

define("GLOBAL_VARS_DEV", true);

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

class plgContentGlobalVariables extends JPlugin
{

    /**
     * Contains the variable vault
     * @var array
     */
    private $variables = array();
    private $article;

    /**
     * Plugin constructor
     *
     * @param object $subject
     * @param object $params
     */
    function plgContentGlobalVariables(&$subject, $params)
    {
        parent::__construct($subject, $params);
        $this->_plugin = JPluginHelper::getPlugin('content', 'globalvariables');
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
            if (is_array($data) || is_object($data)) {
//                array_walk($data, array($this, 'replace_array'));
                $data->title = $this->start_replace($data->title);
                $data->introtext = $this->start_replace($data->introtext);
                $data->text = $this->start_replace($data->text);
            } elseif (is_string($data)) {
                $data = $this->start_replace($data);
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
     * Function to get the current variables from a given source
     */
    function load_variables()
    {
        $gv_params = $this->params;

        $id = $gv_params->get("variablesource");

        if ($id) {
            $this->variables = $this->parse_variables_article_vault($this->get_article_content_by_id($id));
        } else {
            $this->variables = $this->parse_variable_ini_vault($gv_params->get("variabledefinition"));
        }
    }

    private function parse_variables_article_vault($string)
    {
        dd("Step 1:", htmlentities($string));

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
                dd("Step 2a (Heredoc):", $match);
                $string = str_replace($match[$rx_data_full][$rx_content], $match[$rx_data_first_esc_character][$rx_content] . $match[$rx_data_varname][$rx_content] . '="' . $escaped_heredoc_block . '";', $string);
            }
        } else {
            dd("Step 2a (Heredoc):", "Nothing to process");
        }

        dd("Step 2b:", $string);
        $match = array();

        $strEsc = preg_quote("'\"");
//        $rx_parse_var_str = '#' . $var_declaration . '([' . $strEsc . '])([^\\' . $rx_data_quote . ']*?)\\' . $rx_data_quote . ';?#is';
        $rx_parse_var_str = '#' . $var_declaration . '([' . $strEsc . '])((?:<[^>]*>|\\\\\\' . $rx_data_quote . '|[^\\' . $rx_data_quote . '])*?)\\' . $rx_data_quote . ';?#is';
//        dd("rx_parse_var_str", $rx_parse_var_str);
        preg_match_all($rx_parse_var_str, $string, $match, PREG_SET_ORDER);

        dd("Step 3:", $match);
        if ($match) {
            foreach ($match as $set) {
                $variable[$set[$rx_data_varname]] = stripcslashes(html_entity_decode($set[$rx_data_content]));
//                dd($set[$rx_data_varname], stripcslashes(html_entity_decode($set[$rx_data_content])));
            }
        }
        return $variable;
    }

    /**
     * Parse the variables from a string content
     * @param string $string
     * @throws Exception
     */
    private function parse_variable_ini_vault($string)
    {
        $variables = @parse_ini_string($string);
        if ($variables === false) {
            throw new Exception("Error while parsing variable definition. Please review your variable definition for syntax problems!");
        }
        return $variables;
    }

    /**
     * Start replacing the defined variables
     * @param string $string
     * @return string
     */
    function start_replace(&$string)
    {
        if (!$this->variables) {
            $this->load_variables();
        }
        $looped = 0;
        $match = array();
        $replaceValue = null;
        $not_set_variables = array();

        $varmatch_rx = '#(var_)([0-9a-z_.+-]+?)\(\)|\{(global|vartext)\}(.*?)\{/(?:\\3)\}|([$_]{1})([0-9a-z_.+-]+)|\{\{(.*)\}\}#is';
        $replace = array();

        for ($recurse = 0; $recurse <= 10; $recurse++) {
            $replace['offset'] = 0;
            while (preg_match($varmatch_rx, $string, $matches, PREG_OFFSET_CAPTURE, $replace['offset'])) {
                $match = array();
                foreach ($matches as $id => $matched) {
                    $match[$id] = $matched[0];
                }

                $replaceValue = $this->get_variable_by_match($match);
                if ($replaceValue !== null) {
                    $replace['offset'] = $matches[0][1];
                    $replace['length'] = strlen($matches[0][0]);
                    $replace['value'] = $replaceValue->value;
                    $string = substr_replace($string, $replace['value'], $replace['offset'], $replace['length']);
                    $replace['offset']+=strlen($replaceValue->value);
                } else {
                    $replace['offset']+=strlen($matches[0][0]);
                    $not_set_variables[$matches[0][0]] = $matches;
                }

                if (($looped++) > 500) {
                    break;
                }
            }
        }

        return $string;
    }

    /**
     * Returns the variable value
     * @param array $match
     * @return string
     */
    private function get_variable_by_match($match)
    {
        $identified = $this->identify_match_type($match);
        switch ($identified->type) {
            case "legacy":
            case "curly":
            case "varescaped":
            case "doublecurly":
                $identified->value = $this->lookup_variable_vault($identified->key);
                if ($identified->value === null) {
                    return null;
                } else {
                    return $identified;
                }
        }
        return null;
    }

    /**
     * Looks up the variable store and returns the variable value
     * @param string $variable_key
     * @return string
     */
    private function lookup_variable_vault(&$variable_key)
    {
//        dd($this->variables);
        if (isset($this->variables[$variable_key])) {
            return $this->variables[$variable_key];
        }
        return null;
    }

    /**
     * Identifies the RegEx match and nomalizes the match
     * @param array $match
     * @return \stdClass
     */
    private function identify_match_type(&$match)
    {
        $identified = new stdClass();
        $identified->full_key = $match[0];
        $identified->type = false;
        $identified->key = false;
        $identified->marker = false;
        $identified->value = null;

        if ($match[1] !== "" && $match[2] !== "") {
            $identified->type = 'legacy';
            $identified->key = $match[2];
            $identified->marker = $match[1];
        } elseif ($match[3] !== "" && $match[4] !== "") {
            $identified->type = 'curly';
            $identified->key = $match[4];
            $identified->marker = $match[3];
        } elseif ($match[5] !== "" && $match[6] !== "") {
            $identified->type = 'varescaped';
            $identified->key = $match[6];
            $identified->marker = $match[5];
        } elseif ($match[7] !== "") {
            $identified->type = 'doublecurly';
            $identified->key = $match[7];
            $identified->marker = "{{}}";
        }

        return $identified;
    }

    private function get_article_content_by_id($id)
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

    private function InjectHeadCode()
    {
        //Currently no use and just for remind purposes still in here
        return;
    }

}
