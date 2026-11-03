<?php

/*
 * Component Installation script
 * 26/08/26 CB Created
 * 14/11/26 CB Do not abort if information not available for previous installation
 * 24/10/26 CB used SchemaHelper and ToolsHelper for database and UI operations
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Exception\FilesystemException;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Ramblers\Component\Ra_tools\Site\Helpers\SchemaHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class Com_Ra_treasurerInstallerScript {

    private const COMPONENT_USERGROUP = 'com_ra_treasurer';
    private const USERGROUP_CHECK_VERSION = '1.1.2';

    private $component;
    private $minimumJoomlaVersion = '4.0';
    private $minimumPHPVersion = JOOMLA_MINIMUM_PHP;
    private $reconfigure_message;
    private $original_version;  
    private $schemaHelper;
    private $toolsHelper;

    private function getToolsHelper(): ToolsHelper {
        if ($this->toolsHelper === null) {
            $this->toolsHelper = new ToolsHelper();
        }

        return $this->toolsHelper;
    }

    private function getSchemaHelper(): SchemaHelper {
        if ($this->schemaHelper === null) {
            $this->schemaHelper = new SchemaHelper();
        }

        return $this->schemaHelper;
    }

    function buildButton($url, $text, $newWindow = 0, $colour = '') {
        return $this->getToolsHelper()->buildButton($url, $text, $newWindow, $colour);
    }

    function checkColumn($table, $column, $mode, $details = '') {
        return $this->getSchemaHelper()->checkColumn($table, $column, $mode, $details);
    }

    function checkTable($table, $details, $details2 = '') {
        return $this->getSchemaHelper()->checkTable($table, $details, $details2);
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

    private function ensureClaimTypesTable(): void {
        $this->checkTable(
                'ra_claim_types',
                '(`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,'
                . ' `description` VARCHAR(50) NOT NULL,'
                . ' `ordering` INT(11) NOT NULL DEFAULT 0,'
                . ' `state` TINYINT(1) NOT NULL DEFAULT 1,'
                . ' PRIMARY KEY (`id`),'
                . ' KEY `idx_state` (`state`),'
                . ' KEY `idx_ordering` (`ordering`))'
                . ' DEFAULT COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function ensureComponentUsergroup(string $component): bool {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $sql = 'SELECT id FROM #__usergroups WHERE title = '. $db->q($component)  ;
        $groupId = $this->toolsHelper->getValue($sql);
//        $query = $db->getQuery(true)
//                ->select($db->quoteName('id'))
//                ->from($db->quoteName('#__usergroups'))
//                ->where($db->quoteName('title') . ' = :title')
//                ->bind(':title', self::COMPONENT_USERGROUP, ParameterType::STRING);
//        $db->setQuery($query);
//        $groupId = (int) $db->loadResult();

        if ($groupId > 0) {
            $this->message('Usergroup ' . $component . ' exists (id=' . $groupId . ').');
            return true;
        }

        $table = Table::getInstance('Usergroup', 'JTable', []);

        if (!$table || !$table->save([
            'title' => $component,
            'parent_id' => 1,
        ])) {
            $error = $table ? (string) $table->getError() : 'Unable to initialise Usergroup table';
            return $this->fail('Failed to create usergroup ' . $component . ': ' . $error);
        }

        $this->message('Created usergroup ' . $component . ' (id=' . (int) $table->id . ').');
        return true;
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

    public function install($parent): bool {
        $this->message('Installing RA Treasurer (com_ra_treasurer).');
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
        $this->ensureClaimTypesTable();
        return true;
    }

    private function message(string $message): void {
        Factory::getApplication()->enqueueMessage($message, 'message');
    }

    public function postflight($type, $parent) {
        if ($type == 'uninstall') {
            return true;
        }
        $new_version = $this->getInstalledComponentVersion();
        if ($this->original_version !== $new_version) {
            echo '<p>com_ra_treasurer has been upgraded from ' . $this->original_version . ' to ' . $new_version . '</p>';
        } else {
            echo '<p>com_ra_treasurer is unchanged at ' . $new_version . '</p>';
        }
        if ($this->reconfigure_message == true) {
            $this->red('Please review and update the configuration settings for com_ra_treasurer.');
        }

        echo '<b>Useful links</b><br>';
        echo $this->buildButton('index.php?option=com_ra_tools&view=dashboard', 'Dashboard', false, 'granite') . '<br>';
        echo $this->buildButton('index.php?option=com_config&view=component&component=com_ra_treasurer', 'Configure');
        return true;
    }

    public function preflight($type, $parent): bool {
        $this->message('Preflight RA Treasurer component(type=' . $type . ').');
        if ($type == 'uninstall') {
            return true;
        }
        if (!ComponentHelper::isEnabled('com_ra_tools', true)) {
            return $this->fail('RA Treasurer requires com_ra_tools to be installed and enabled.');
        }

        if (!class_exists(ToolsHelper::class)) {
            return $this->fail('RA Treasurer could not load com_ra_tools helpers.');
        }
        if (!class_exists(SchemaHelper::class)) {
            return $this->fail('RA Treasurer could not load com_ra_tools schema helper.');
        }

        $this->getToolsHelper();
        $this->getSchemaHelper();

        if ($type == 'install') {
            $this->original_version = '0.0.0';
        } elseif (ComponentHelper::isEnabled('com_ra_treasurer', true)) {
            try {
                $this->original_version = $this->getInstalledComponentVersion();
            } catch (\RuntimeException $exception) {
                return $this->fail($exception->getMessage());
            }
            if ($this->original_version === null) {
                $this->message('Installer could not find readable version information for com_ra_treasurer.');
            } else {
                $this->message('com_ra_treasurer already present, version=' . $this->original_version . '.');
            }
        } else {
            $this->message('Installer could not find the existing com_ra_treasurer installation.');
        }

        $currentVersion = $this->original_version ?? '0.0.0';
        if (version_compare($currentVersion, self::USERGROUP_CHECK_VERSION, '<')) {
            if (!$this->ensureComponentUsergroup(self::COMPONENT_USERGROUP)) {
                $this->fail('Usergroup check for ' . self::COMPONENT_USERGROUP . ' failed.', 'error');
                return false;
            }
        }

        if ($type == 'install') {
            $this->message('No action required by preflight on install.');
            return true;
        }

        if (version_compare($this->original_version, '1.0.4', 'le')) {

            // table ra_guest_bookings
            $details = '(
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `booking_id` INT  NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;';
            $this->checkTable('ra_guest_bookings', $details);

            $this->checkColumn('ra_events', 'requires_payment', 'A', 'INT DEFAULT "0" AFTER bookable; ');
            $this->checkColumn('ra_events', 'requires_ice', 'A', 'INT DEFAULT "0" AFTER requires_payment; ');

            $this->checkColumn('ra_events', 'max_bookings', 'A', 'INT NULL AFTER num_bookings; ');
            $this->checkColumn('ra_events', 'max_guests', 'A', 'INT DEFAULT "0" AFTER max_bookings; ');
            $this->checkColumn('ra_events', 'waiting_list_enabled', 'A', 'INT DEFAULT "0" AFTER max_guests; ');
            $this->checkColumn('ra_events', 'multi_guest_enabled', 'A', 'INT DEFAULT "0" AFTER waiting_list_enabled; ');
            $this->checkColumn('ra_events', 'multi_bookings_enabled', 'A', 'INT DEFAULT "0" AFTER multi_guest_enabled; ');       

            $this->checkColumn('ra_mail_shots', 'send_after', 'A', 'DATETIME NULL AFTER processing_started; ');
            $this->checkColumn('ra_mail_shots', 'is_scheduled', 'A', 'TINYINT DEFAULT "0" AFTER send_after; ');
            $this->checkColumn('ra_mail_shots', 'cancelled', 'A', 'TINYINT DEFAULT "0" AFTER is_scheduled; ');
            $this->checkColumn('ra_mail_shots', 'reply_to', 'A', 'VARCHAR(255) NULL AFTER cancelled; ');

            $this->checkColumn('ra_profiles', 'created', 'U', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP; ');
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
        $this->ensureClaimTypesTable();
        return true;
    }

}