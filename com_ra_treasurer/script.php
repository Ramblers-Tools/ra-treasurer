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
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class Com_Ra_treasurerInstallerScript {

    private const MINIMUM_MAILMAN_VERSION = '5.0.18';
    private const MINIMUM_TOOLS_VERSION = '4.0.12';

    private $component;
    private $minimumJoomlaVersion = '4.0';
    private $minimumPHPVersion = JOOMLA_MINIMUM_PHP;
    private $reconfigure_message;

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
        $config = Factory::getConfig();
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

    private function checkMinimumComponentVersion(string $component, string $requiredVersion): bool {
        try {
            $installedVersion = $this->getInstalledComponentVersion($component);
        } catch (\RuntimeException $exception) {
            Log::add($exception->getMessage(), Log::ERROR, 'jerror');
            return $this->fail('RA Treasurer could not read the installed version of ' . $component . '.');
        }

        if ($installedVersion !== null && version_compare($installedVersion, $requiredVersion, 'ge')) {
            $this->message('Version ' . $requiredVersion . ' of ' . $component
                    . ' required; version ' . $installedVersion . ' found.');
            return true;
        }

        return $this->fail('RA Treasurer requires ' . $component . ' version ' . $requiredVersion
                        . ' or later; found ' . ($installedVersion ?: 'no readable version') . '.');
    }

    function checkTable($table, $details, $details2 = '') {

        $config = Factory::getConfig();
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

    private function ensurePaymentColumns(): void {
        $this->checkColumn('ra_bookings', 'amount_paid', 'A', 'DECIMAL(7,2) NULL DEFAULT NULL AFTER custom2; ');
        $this->checkColumn('ra_bookings', 'date_paid', 'A', 'DATE NULL DEFAULT NULL AFTER amount_paid; ');
        $this->checkColumn('ra_bookings', 'payment_created', 'A', 'DATETIME NULL DEFAULT NULL AFTER date_paid; ');
        $this->checkColumn('ra_bookings', 'payment_created_by', 'A', 'INT NULL DEFAULT NULL AFTER payment_created; ');
        $this->checkColumn('ra_bookings', 'payment_modified', 'A', 'DATETIME NULL DEFAULT NULL AFTER payment_created_by; ');
        $this->checkColumn('ra_bookings', 'payment_modified_by', 'A', 'INT NULL DEFAULT NULL AFTER payment_modified; ');
    }

    private function executeCommand($sql) {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        return $db->execute();
    }

    private function fail(string $message): bool {
        Factory::getApplication()->enqueueMessage($message, 'error');
        Log::add($message, Log::ERROR, 'jerror');

        return false;
    }

    /**
     * Return the installed manifest version for a component.
     */
    private function getInstalledComponentVersion(string $component = 'com_ra_treasurer'): ?string {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $extensionType = 'component';

        $query->select($db->quoteName('e.manifest_cache'))
                ->from($db->quoteName('#__extensions', 'e'))
                ->where($db->quoteName('e.element') . ' = :component')
                ->where($db->quoteName('e.type') . ' = :extensionType')
                ->bind(':component', $component, ParameterType::STRING)
                ->bind(':extensionType', $extensionType, ParameterType::STRING);

        $db->setQuery($query);
        $manifestCache = $db->loadResult();

        if ($manifestCache === null) {
            return null;
        }

        try {
            $manifest = json_decode((string) $manifestCache, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(
                            'Installer could not decode version information for ' . $component . '.',
                            0,
                            $exception
            );
        }

        if (!is_array($manifest)) {
            throw new \RuntimeException('Installer found invalid version information for ' . $component . '.');
        }

        $installedVersion = $manifest['version'] ?? null;

        return is_scalar($installedVersion) ? (string) $installedVersion : null;
    }

    private function getValue($sql) {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        return $db->loadResult();
    }

    public function install($parent): bool {
        $this->message('Installing RA Treasurer (com_ra_treasurer).');
        if (!empty($this->minimumPHPVersion) && version_compare(PHP_VERSION, $this->minimumPHPVersion, '<')) {
            return $this->fail(Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPHPVersion));
        }
        if (!empty($this->minimumJoomlaVersion) && version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
            return $this->fail(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomlaVersion));
        }

        if (ComponentHelper::isEnabled('com_ra_tools', true)) {
            if (!$this->checkMinimumComponentVersion('com_ra_tools', self::MINIMUM_TOOLS_VERSION)) {
                return false;
            }
        } else {
            return $this->fail('RA Treasurer requires that com_ra_tools is installed and enabled.');
        }

        if (!ComponentHelper::isEnabled('com_ra_events', true)) {
            return $this->fail('RA Treasurer requires that com_ra_events is installed and enabled.');
        }
        /*
          Preview SQL
          ALTER TABLE `j5_ra_bookings` ADD `amount_paid` DECIMAL(7,2) NULL DEFAULT NULL AFTER `custom2`,
         * ADD `date_paid` DATETIME NULL DEFAULT NULL AFTER `amount_paid`,
         * ADD `payment_created` DATETIME NULL DEFAULT NULL AFTER `date_paid`,
         * ADD `payment_created_by` INT NULL DEFAULT NULL AFTER `payment_created`,
         * ADD `payment_modified` DATETIME NULL DEFAULT NULL AFTER `payment_created_by`,
         * ADD `payment_modified_by` INT NULL DEFAULT NULL AFTER `payment_modified`;
         */
        $this->ensurePaymentColumns();
        return true;
    }

    private function message(string $message): void {
        Factory::getApplication()->enqueueMessage($message, 'message');
    }

    public function postflight($type, $parent) {
        $this->message('Postflight RA Treasurer (com_ra_treasurer).');
        if ($type == 'uninstall') {
            return true;
        }
        echo '<b>Useful links</b><br>';
        echo $this->buildButton('index.php?option=com_ra_tools&view=dashboard', 'Dashboard', 'granite') . '<br>';
        echo $this->buildButton('index.php?option=com_config&view=component&component=com_ra_treasurer', 'Configure');
        return true;
    }

    public function preflight($type, $parent): bool {
        $this->message('Preflight RA Treasurer (type=' . $type . ').');
        if ($type == 'uninstall') {
            return true;
        }
        if ($type == 'install') {
            $this->message('No action required by preflight on install.');
            return true;
        }

        if (ComponentHelper::isEnabled('com_ra_treasurer', true)) {
            try {
                $currentVersion = $this->getInstalledComponentVersion();
            } catch (\RuntimeException $exception) {
                return $this->fail($exception->getMessage());
            }

            if ($currentVersion === null) {
                return $this->fail('Installer could not find readable version information for com_ra_treasurer.');
            }

            $this->message('com_ra_treasurer already present, version=' . $currentVersion . '.');
        } else {
            return $this->fail('Installer could not find the existing com_ra_treasurer installation.');
        }
        if (!ComponentHelper::isEnabled('com_ra_tools', true)) {
            return $this->fail('RA Treasurer requires the enabled component com_ra_tools.');
        }
        if (!ComponentHelper::isEnabled('com_ra_mailman', true)) {
            return $this->fail('RA Treasurer requires the enabled component com_ra_mailman.');
        }

        if (!$this->checkMinimumComponentVersion('com_ra_mailman', self::MINIMUM_MAILMAN_VERSION)) {
            return false;
        }

        if (!$this->checkMinimumComponentVersion('com_ra_tools', self::MINIMUM_TOOLS_VERSION)) {
            return false;
        }
        return true;
    }

    public function red($text) {
        echo '<p><span style="color: #ff0000;"><strong>';
        echo $text;
        echo '</strong></span></p>';
    }

    public function uninstall($parent): bool {
        echo '<p>Uninstalling RA Treasurer (com_ra_treasurer)<br>';
        try {
            $installedVersion = $this->getInstalledComponentVersion();
        } catch (\RuntimeException $exception) {
            Log::add($exception->getMessage(), Log::WARNING, 'jerror');
            $installedVersion = null;
        }

        if ($installedVersion === null) {
            echo '<p>Version information not available</p>';
        } else {
            echo '<p>Version ' . $installedVersion . '</p>';
        }
        return true;
    }

    public function update($parent): bool {
        echo '<p>Updating RA Treasurer (com_ra_treasurer)</p>';
        $this->ensurePaymentColumns();
        return true;
    }

}
