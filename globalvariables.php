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
     * Starts the replaceing for a string or an array (array is not done yet)
     * @param string/array $data
     * @return inputtype $data
     */
    function replace_array(&$data)
    {
        try {
            jimport('joomla.document.html.html');
            if (is_array($data) || is_object($data)) {
                array_walk($data, array($this, 'replace_array'));
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
//        echo "<pre>";
//        echo "Step1:\n";
//        print_r(htmlentities($string));
//        echo "</pre>";
        $variable = array();
        $plain = strip_tags($string);
//        echo "<pre>";
//        echo "Step2:\n";
//        print_r($string);
//        echo "</pre>";
//        $breaks_rx = array(' ', '\r\n', '<br', '</p');
//        $break_rx = "(?:" . implode("|", $breaks_rx) . ")";
        $match = array();
        preg_match_all("#(?:^|[\b\r\n])([a-z][^= ]*)[ \r\n]*?=[ \r\n]*?([\"'])([^\\2]*?)\\2;?#is", $plain, $match, PREG_SET_ORDER);
//		echo "<pre>";
//        echo "Step3:\n";
//		print_r($match);
//		echo "</pre>";
        if ($match) {
            foreach ($match as $set) {
                $variable[$set[1]] = $set[3];
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
        //echo $string;
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
        $replaceValue = "";

        while (preg_match("#var_([0-9a-z_.+-]+?)\(\)|\{(global|vartext)\}(.*?)\{/(?:\\2)\}#is", $string, $match)) {
            //print_r($match);
            $matchString = $match[0];
            $replaceValue = $this->get_variable_by_match($match);
            $string = str_replace($matchString, $replaceValue, $string);
            if (($looped++) > 500) {
                break;
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
                return $this->lookup_variable_vault($identified->key);
            case "curly":
                return $this->lookup_variable_vault($identified->key);
        }
    }

    /**
     * Looks up the variable store and returns the variable value
     * @param string $variable_key
     * @return string
     */
    private function lookup_variable_vault(&$variable_key)
    {
        if (isset($this->variables[$variable_key])) {
            return $this->variables[$variable_key];
        }
        return false;
    }

    /**
     * Identifies the RegEx match and nomalizes the match
     * @param array $match
     * @return \stdClass
     */
    private function identify_match_type(&$match)
    {
        $identified = new stdClass();
        $identified->type = false;
        $identified->key = false;
        $identified->marker = false;

        if ($match[1]) {
            $identified->type = 'legacy';
            $identified->key = $match[1];
            $identified->marker = "";
        } elseif ($match[2] && $match[3]) {
            $identified->type = 'curly';
            $identified->key = $match[3];
            $identified->marker = $match[2];
        }

        return $identified;
    }

    private function get_article_content_by_id($id)
    {
        JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_content/models', 'ContentModel');
        $model = JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request' => true));
        $appParams = JFactory::getApplication()->getParams();
        $model->setState('params', $appParams);

        $item = $model->getItem($id);
        return ($item->fulltext ? $item->fulltext : $item->introtext);
    }

    private function InjectHeadCode()
    {
        //Currently no use and just for remind purposes still in here
        return;
    }

}
