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
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

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
        if ($route == 'install') {
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
     * Called on uninstallation
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     */
    public function uninstall(JAdapterInstance $adapter)
    {

    }

    /**
     * Called before any type of action
     * $adapter is the class calling this method.
     * $type is the type of change (install, update or discover_install, not uninstall).
     * preflight runs before anything else and while the extracted files are in the uploaded temp folder.
     * If preflight returns false, Joomla will abort the update and undo everything already done.
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function preflight($route, JAdapterInstance $adapter)
    {
        $jversion = new JVersion();

        // Installing component manifest file version
        $this->release = $adapter->get("manifest")->version;

        // Manifest file minimum Joomla version
        $this->minimum_joomla_release = $adapter->get("manifest")->attributes()->version;

        // Show the essential information at the install/update back-end
//        echo '<p>Installing component manifest file version = ' . $this->release;
//        echo '<br />Current manifest cache commponent version = ' . $this->getParam('version');
//        echo '<br />Installing component manifest file minimum Joomla version = ' . $this->minimum_joomla_release;
//        echo '<br />Current Joomla version = ' . $jversion->getShortVersion();
        // abort if the current Joomla release is older
        if (version_compare($jversion->getShortVersion(), $this->minimum_joomla_release, 'lt')) {
            Jerror::raiseWarning(null, 'Cannot install Global Variables in a Joomla release prior to ' . $this->minimum_joomla_release);
            return false;
        }

        // abort if the component being installed is not newer than the currently installed version
        if ($route == 'update') {
            $oldRelease = $this->getParam('version');
            $rel = $oldRelease . ' to ' . $this->release;
            if (version_compare($this->release, $oldRelease, 'lt')) {
                Jerror::raiseWarning(null, 'Cannot downgrade from ' . $rel);
                return false;
            } elseif (version_compare($this->release, $oldRelease, 'eq')) {
                Jerror::raiseWarning(null, $this->release . ' is already installed.');
                return false;
            }
        } else {
            $rel = $this->release;
        }

        echo '<p>' . JText::_('PLG_GLOBALVARIABLES_PREFLIGHT_' . strtoupper($route), $rel) . ' ' . $rel . '</p>';
    }

    /**
     * Called on installation
     * $adapter is the class calling this method.
     * install runs after the database scripts are executed.
     * If the extension is new, the install method is run.
     * If install returns false, Joomla will abort the install and undo everything already done.
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function install(JAdapterInstance $adapter)
    {
//        echo '<p>' . JText::_('COM_DEMOCOMPUPDATE_INSTALL to ' . $this->release) . '</p>';
        // You can have the backend jump directly to the newly installed component configuration page
        // $adapter->getParent()->setRedirectURL('index.php?option=com_democompupdate');
    }

    /**
     * Called on update
     * $adapter is the class calling this method.
     * update runs after the database scripts are executed.
     * If the extension exists, then the update method is run.
     * If this returns false, Joomla will abort the update and undo everything already done.
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function update(JAdapterInstance $adapter)
    {
//        echo '<p>' . JText::_('COM_DEMOCOMPUPDATE_UPDATE_ to ' . $this->release) . '</p>';
        // You can have the backend jump directly to the newly updated component configuration page
        // $adapter->getParent()->setRedirectURL('index.php?option=com_democompupdate');
    }

    /*
     * get a variable from the manifest file (actually, from the manifest cache).
     */

    function getParam($name)
    {
        $db = JFactory::getDbo();
        $db->setQuery('SELECT manifest_cache FROM #__extensions WHERE element = "globalvariables" AND type = "plugin"');
        $manifest = json_decode($db->loadResult(), true);
        return $manifest[$name];
    }

    /*
     * sets parameter values in the component's row of the extension table
     */

    function setParams($param_array)
    {
        if (count($param_array) > 0) {
            // read the existing component value(s)
            $db = JFactory::getDbo();
            $db->setQuery('SELECT params FROM #__extensions WHERE element = "globalvariables" AND type = "plugin"');
            $params = json_decode($db->loadResult(), true);
            // add the new variable(s) to the existing one(s)
            foreach ($param_array as $name => $value) {
                $params[(string) $name] = (string) $value;
            }
            // store the combined new and existing values back as a JSON string
            $paramsString = json_encode($params);
            $db->setQuery('UPDATE #__extensions SET params = ' .
                    $db->quote($paramsString) .
                    ' WHERE element = "globalvariables" AND type = "plugin"');
            $db->query();
        }
    }

}
