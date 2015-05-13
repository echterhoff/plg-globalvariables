<?php

/**
 * @version  3.4
 * @Project  GLOABL VARIABLES
 * @author   Lars Echterhoff
 * @package
 * @copyright Copyright (C) 2015 Lars Echterhoff
 * @license  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
 * @description Installation script
 */
class plgContentGlobalVariablesInstallerScript
{

    /**
     * Constructor
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     */
    public function __construct(JAdapterInstance $adapter)
    {

    }

    /**
     * Called before any type of action
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function preflight($route, JAdapterInstance $adapter)
    {

    }

    /**
     * Called after any type of action
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function postflight($route, JAdapterInstance $adapter)
    {
        // We only need to perform this if the extension is being installed, not updated
        if ($type == 'install') {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            $fields = array(
                $db->quoteName('enabled') . ' = ' . 1,
                $db->quoteName('ordering') . ' = ' . 9999
            );

            $conditions = array(
                $db->quoteName('element') . ' = ' . $db->quote('globalvariables'),
                $db->quoteName('type') . ' = ' . $db->quote('plugin')
            );

            $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);

            $db->setQuery($query);
            $db->execute();
        }
    }

    /**
     * Called on installation
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function install(JAdapterInstance $adapter)
    {

    }

    /**
     * Called on update
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function update(JAdapterInstance $adapter)
    {

    }

    /**
     * Called on uninstallation
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     */
    public function uninstall(JAdapterInstance $adapter)
    {

    }

}
