<?php

/*
 * Installation script
 * 26/08/26 CB Created
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

class Com_Ra_treasurerInstallerScript {

    private $component;
    private $minimumJoomlaVersion = '4.0';
    private $minimumPHPVersion = JOOMLA_MINIMUM_PHP;
    private $reconfigure_message;
    private $required_version;

    private function fail(string $message): bool {
        Factory::getApplication()->enqueueMessage($message, 'error');
        Log::add($message, Log::ERROR, 'jerror');

        return false;
    }

    function buildButton($url, $text, $newWindow = 0, $colour = '') {
        if ($colour == '') {
            $colour = 'sunrise';
        }
        $class = 'link-button ' . $colour;
        //       echo "colour=$colour, code=$code, class=$class<br>";
        $q = chr(34);
        $out = "<a class=" . $q . $class . $q;
        $out .= " href=" . $q . $url . $q;
        $out .= " target =" . $q . "_self" . $q;
        $out .= ">";
        $out .= $text;
        $out .= "</a>";
        return $out;
    }

    function checkColumn($table, $column, $mode, $details = '') {
//  $mode = A: add the field, using data supplied in $details
//  $mode = U: update the field (keeping name the same), using $details
//  $mode = D: delete the field

        $count = $this->checkColumnExists($table, $column);
        $table_name = $this->dbPrefix . $table;
//        echo 'mode=' . $mode . ': Seeking ' . $table_name . '/' . $column . ', count=' . $count . "<br>";
        if (($mode == 'A') AND ($count == 1)
                OR ($mode == 'D') AND ($count == 0)) {
            return true;
        }
        if (($mode == 'U') AND ($count == 0)) {
            return $this->fail('Installer could not update missing field ' . $table_name . '.' . $column . '.');
        }

        $sql = 'ALTER TABLE ' . $table_name . ' ';
        if ($mode == 'A') {
            $sql .= 'ADD ' . $column . ' ';
            $sql .= $details;
        } elseif ($mode == 'D') {
            $sql .= 'DROP ' . $column;
        } elseif ($mode == 'U') {
            $sql .= 'CHANGE ' . $column . ' ' . $column . ' ';
            $sql .= $details;
        }
        echo "$sql<br>";
        $response = $this->executeCommand($sql);
        if ($response) {
            echo 'Success';
        } else {
            echo 'Failure';
        }
        echo ' for ' . $table_name . '<br>';
        return $count;
    }

    private function checkColumnExists($table, $column) {
        $config = JFactory::getConfig();
        $database = $config->get('db');
        $this->dbPrefix = $config->get('dbprefix');

        $table_name = $this->dbPrefix . $table;
        $sql = 'SELECT COUNT(COLUMN_NAME) ';
        $sql .= "FROM information_schema.COLUMNS ";
        $sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $this->dbPrefix . $table . "' ";
        $sql .= "AND COLUMN_NAME='" . $column . "'";
//    echo "$sql<br>";

        return $this->getValue($sql);
    }

    function checkTable($table, $details, $details2 = '') {

        $config = JFactory::getConfig();
        $database = $config->get('db');
        $this->dbPrefix = $config->get('dbprefix');

        $table_name = $this->dbPrefix . $table;
        $sql = 'SELECT COUNT(COLUMN_NAME) ';
        $sql .= "FROM information_schema.COLUMNS ";
        $sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $table_name . "' ";
//        echo "$sql<br>";

        $count = $this->getValue($sql);
        echo 'Seeking ' . $table_name . ', count=' . $count . "<br>";
        if ($count > 0) {
            return $count;
        }
        $sql = 'CREATE TABLE ' . $table_name . ' ' . $details;
        echo "$sql<br>";
        $response = $this->executeCommand($sql);
        if ($response) {
            echo 'Table created OK<br>';
        } else {
            return $this->fail('Installer failed to create database table ' . $table_name . '.');
        }
        if ($details2 != '') {
            $sql = 'ALTER TABLE ' . $table_name . ' ' . $details2;
            $response = $this->executeCommand($sql);
            if ($response) {
                echo 'Table altered OK<br>';
            } else {
                return $this->fail('Installer failed to alter database table ' . $table_name . '.');
            }
        }
    }

    private function deleteFile($target) {
// Not needed, could use a built in function (if details were known!)
        $file = JPATH_ROOT . $target;
        if (file_exists($file)) {
            echo 'File ' . $file . ' found,';
            File::delete($file);
            if (file_exists($file)) {
                echo ' deleted<br>';
            } else {
                echo ' but unable to delete<br>';
            }
        } else {
            echo "Unable to delete $file: file not found<br>";
        }
    }

    private function deleteFolder($target) {
// created 08/10/24 - does not seem to work
        $folder = JPATH_ROOT . $target;
        if (file_exists($folder)) {
            echo 'Folder ' . $folder . ' found,';
            Folder::delete($folder);
            if (file_exists($folder)) {
                echo ' deleted<br>';
            } else {
                echo ' but unable to delete<br>';
            }
        } else {
            echo 'Unable to delete ' . $folder . ': folder not found<br>';
        }
    }

    private function executeCommand($sql) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        return $db->execute();
    }

    public function getDatabaseVersion($component = 'com_ra_treasurer') {
// Get the extension ID
        $db = JFactory::getDbo();
        $eid = $this->getExtensionId($component);

        if ($eid != null) {
// Get the schema version
            $query = $db->getQuery(true);
            $query->select('manifest_cache')
                    ->from('#__extensions')
                    ->where('extension_id = ' . $db->quote($eid));
            $db->setQuery($query);
            $json = $db->loadResult();
            $values = json_decode($json->manifest_cache);
            return $version;
        }
        return null;
    }

    public function getDbVersion($component = 'com_ra_treasurer') {
        $sql = 'SELECT s.version_id ';
        $sql .= 'FROM #__extensions as e ';
        $sql .= 'LEFT JOIN #__schemas AS s ON s.extension_id = e.extension_id ';
        $sql .= 'WHERE e.element="' . $component . '"';
        return $this->getValue($sql);
    }

    public function getVersion($component = 'com_ra_treasurer') {
        // This returns the version as display by System / Manage extensions
        $sql = 'SELECT manifest_cache ';
        $sql .= 'FROM  #__extensions  ';
        $sql .= 'WHERE element="' . $component . '"';
        $json = $this->getValue($sql);

        if (empty($json)) {
            return null;
        }

        $data = json_decode($json);

        return (is_object($data) && isset($data->version)) ? (string) $data->version : null;
    }

    /**
     *     returns details of the component version and the database version
     *
     * @return  CMSObject
     *
     */
    public function getVersions($component = 'com_ra_treasurer') {
        // Returns an object with two values:
        //  ->component
        //  ->db_version
        $versions = new CMSObject;
        $sql = 'SELECT e.manifest_cache, s.version_id AS db_version ';
        $sql .= 'FROM #__extensions as e ';
        $sql .= 'LEFT JOIN #__schemas AS s ON s.extension_id = e.extension_id ';
        $sql .= 'WHERE element="' . $component . '"';

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        $db->execute();
        $item = $db->loadObject();
        if ($item == false) {
            $this->fail('Installer could not find version information for ' . $component . '.');
            return false;
        } else {
            $values = json_decode($item->manifest_cache);
            $versions->component = $values->version;
            $versions->db_version = $item->db_version;
        }

        return $versions;
    }

    /**
     * Loads the ID of the extension from the database
     *
     * @return mixed
     */
    public function getExtensionId($component = 'com_ra_treasurer') {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true);
        $query->select('extension_id')
                ->from('#__extensions')
                ->where($db->qn('element') . ' = ' . $db->q($component) . ' AND type=' . $db->q('component'));
        $db->setQuery($query);
        $eid = $db->loadResult();
//        echo $db->replacePrefix($query) . '<br>';
        return $eid;
    }

    private function getValue($sql) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        return $db->loadResult();
    }

    public function install($parent): bool {
        echo '<p>Installing RA Treasurer (com_ra_treasurer) ' . '</p>';
        if (!empty($this->minimumPHPVersion) && version_compare(PHP_VERSION, $this->minimumPHPVersion, '<')) {
            return $this->fail(Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPHPVersion));
        }
        if (!empty($this->minimumJoomlaVersion) && version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
            return $this->fail(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomlaVersion));
        }

        if (ComponentHelper::isEnabled('com_ra_tools', true)) {
            $tools_version = $this->getVersion('com_ra_tools');
            $tools_required = '4.0.13';
            echo '<p>Version ' . $tools_required . ' of com_ra_tools required<br>';
            if (version_compare($tools_version, $tools_required, 'ge')) {
                echo '<p>Version ' . $tools_version . ' of com_ra_tools found</p>';
            } else {
                return $this->fail('RA Treasurer requires com_ra_tools version ' . $tools_required
                                . ' or later; found ' . ($tools_version ?: 'no readable version') . '.');
            }
        } else {
            return $this->fail('RA Treasurer requires that com_ra_tools is installed and enabled.');
        }

        if (!ComponentHelper::isEnabled('com_ra_events', true)) {
            return $this->fail('RA Treasurer requires that com_ra_events is installed and enabled.');
        }
        /*
          Preview SQL
          ALTER TABLE `j5_ra_bookings` ADD `amount_paid` DECIMAL((7,2)) NULL DEFAULT NULL AFTER `custom2`,
         * ADD `date_paid` DATE NULL DEFAULT NULL AFTER `amount_paid`, ADD `payment_created` INT NULL DEFAULT NULL AFTER `date_paid`,
         * ADD `payment_created_by` INT NULL DEFAULT NULL AFTER `payment_created`,
         * ADD `payment_modified` DATE NULL DEFAULT NULL AFTER `payment_created_by`,
         * ADD `payment_modified_by` INT NULL DEFAULT NULL AFTER `payment_modified`;
         */
        $this->checkColumn('ra_bookings', 'amount_paid', 'A', 'DECIMAL(7,2) NULL DEFAULT NULL AFTER custom2; ');
        $this->checkColumn('ra_bookings', 'date_paid', 'A', 'DATE NULL DEFAULT NULL AFTER amount_paid; ');
        $this->checkColumn('ra_bookings', 'payment_created', 'A', 'DATE NULL DEFAULT NULL AFTER date_paid; ');
        $this->checkColumn('ra_bookings', 'payment_created_by', 'A', 'INT NULL DEFAULT NULL AFTER payment_created; ');
        $this->checkColumn('ra_bookings', 'payment_modified', 'A', 'DATE NULL DEFAULT NULL AFTER payment_created_by; ');
        $this->checkColumn('ra_bookings', 'payment_modified_by', 'A', 'INT NULL DEFAULT NULL AFTER payment_modified; ');
        return true;
    }

    public function red($text) {
        echo '<p><span style="color: #ff0000;"><strong>';
        echo $text;
        echo '</strong></span></p>';
    }

    public function uninstall($parent): bool {
        echo '<p>Uninstalling RA Treasurer (com_ra_treasurer)<br>';
        $versions = $this->getVersions();
        echo '<p>Version ' . $versions->component;
        echo ', database version ' . $versions->db_version . '</p>';
        return true;
    }

    public function update($parent): bool {
        echo '<p>Updating RA Treasurer (com_ra_treasurer)</p>';
//return true;
// You can have the backend jump directly to the newly updated component configuration page
// $parent->getParent()->setRedirectURL('index.php?option=com_ra_treasurer');
        return true;
    }

    public function postflight($type, $parent) {
        echo 'Postflight RA Treasurer (com_ra_treasurer)<br>';
        if ($type == 'uninstall') {
            return true;
        }
        echo '<b>Useful links</b><br>';
        echo $this->buildButton('index.php?option=com_ra_tools&view=dashboard', 'Dashboard', 'granite') . '<br>';
        echo $this->buildButton('index.php?option=com_config&view=component&component=com_ra_treasurer', 'Configure');
        return true;
    }

    public function preflight($type, $parent): bool {
        echo 'Preflight RA Treasurer (type=' . $type . ')<br>';
        if ($type == 'uninstall') {
            return true;
        }
        if ($type == 'install') {
            echo 'No action required by preflight on install<br>';
            return true;
        }

        if (ComponentHelper::isEnabled('com_ra_treasurer', true)) {
            $this->current_version = $this->getVersion();
            echo 'com_ra_treasurer already present, version=' . $this->getVersion();
            echo ', DB version=' . $this->getDbVersion() . '<br>';
        }
        if (!ComponentHelper::isEnabled('com_ra_tools', true)) {
            return $this->fail('RA Treasurer requires the enabled component com_ra_tools.');
        }
        if (!ComponentHelper::isEnabled('com_ra_mailman', true)) {
            return $this->fail('RA Treasurer requires the enabled component com_ra_mailman.');
        }

        $mailman_required = '5.0.18';
        $mailman_version = $this->getVersion('com_ra_mailman');

        if (!version_compare($mailman_version, $mailman_required, 'ge')) {
            return $this->fail('RA Treasurer requires com_ra_mailman version ' . $mailman_required
                            . ' or later; found ' . ($mailman_version ?: 'no readable version') . '.');
        }

        $tools_required = '4.0.13';
        $tools_version = $this->getVersion('com_ra_tools');
        echo '<p>Version ' . $tools_required . ' of com_ra_tools required<br>';
        if (version_compare($tools_version, $tools_required, 'ge')) {
            echo 'Version ' . $tools_version . ' of com_ra_tools found</p>';
        } else {
            return $this->fail('RA Treasurer requires com_ra_tools version ' . $tools_required
                            . ' or later; found ' . ($tools_version ?: 'no readable version') . '.');
        }

        $this->version_required = '1.1.0';

        if (version_compare($this->current_version, $this->version_required, 'ge')) {
            echo 'Current version is ' . $this->current_version . ', no additional processing required</p>';
            return true;
        } else {
            echo '<p>Version is currently ' . $this->current_version . ', ';
            echo 'Requires version >= ' . $this->version_required . '</p>';
        }
        if (version_compare($this->current_version, '1.2', 'le')) {
//            $this->checkColumn('ra_organisations', 'notes', 'A', 'MEDIUMTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL AFTER details; ');
        }
        return true;
    }

}
