<?php
/*
 * Package Installation script
 * 20/10/26 CB Created
 * 24/10/26 CB Refactored in line with Component script
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Language\Text;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class pkg_ra_treasurerInstallerScript
{
    private const MINIMUM_EVENTS_VERSION = '2.15.1';
    private const MINIMUM_TOOLS_VERSION = '4.0.16';
    private $toolsHelper;

    private function getInstalledVersion(string $element = 'com_ra_treasurer'): ?string
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName('manifest_cache'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element));
        $db->setQuery($query);

        $manifestCache = $db->loadResult();

        if ($manifestCache === null) {
            return null;
        }

        $manifest = json_decode((string) $manifestCache, true);
        return is_array($manifest) && isset($manifest['version']) ? (string)$manifest['version'] : null;
    }

    private function fail(string $message): bool
    {
        Factory::getApplication()->enqueueMessage($message, 'error');
        Log::add($message, Log::ERROR, 'jerror');
        // echo also helps surface the message in some installers
        $this->message('<p style="color:#c00;"><strong>' . htmlspecialchars($message). '</strong></p>');
        return false;
    }

    private function message(string $message): void {
        Factory::getApplication()->enqueueMessage($message, 'message');
    }

    public function postflight($type, $parent) {
        if ($type == 'uninstall') {
            return true;
        }
        /*
        Should this be here or in the component script?
        $new_version =$this->getInstalledVersion();
        if ($this->original_version !== $new_version) {
            echo '<p>com_ra_treasurer has been upgraded from ' . $this->original_version . ' to ' . $new_version . '</p>';
        } else {
            echo '<p>com_ra_treasurer is unchanged at ' . $new_version . '</p>';
        }
        if ($this->reconfigure_message == true) {
            $this->message('Please review and update the configuration settings for com_ra_treasurer.');
        }
*/
        echo $this->toolsHelper->buildButton('index.php?option=com_ra_tools&view=dashboard', 'Dashboard', false, 'granite') . '<br>';
        echo $this->toolsHelper->buildButton('index.php?option=com_config&view=component&component=com_ra_treasurer', 'Configure') . '<br>';
        return true;
    }

    public function preflight($type, $parent): bool
    {
        $this->message('Preflight RA Treasurer package(type=' . $type . ').');
        if ($type === 'uninstall') {
            return true;
        }

        // Check com_ra_tools 
        $toolsVersion = $this->getInstalledVersion('com_ra_tools');
        if ($toolsVersion === null || version_compare($toolsVersion, self::MINIMUM_TOOLS_VERSION, '<')) {
            return $this->fail('RA Treasurer requires com_ra_tools ' . self::MINIMUM_TOOLS_VERSION . ' or later; found ' . ($toolsVersion ?: 'not installed') . '.');
        }

        // Check com_ra_events
        $eventsVersion = $this->getInstalledVersion('com_ra_events');
        if ($eventsVersion === null) {
            return $this->fail('RA Treasurer requires com_ra_events ' . self::MINIMUM_EVENTS_VERSION . ' or later; com_ra_events is not installed.');
        }
//        if (version_compare($eventsVersion, self::MINIMUM_EVENTS_VERSION, '<')) {
//            return $this->fail('RA Treasurer requires com_ra_events ' . self::MINIMUM_EVENTS_VERSION . ' or later; found ' . $eventsVersion . '. Please upgrade com_ra_events first.');
//        }

        if (!class_exists(ToolsHelper::class)) {
            return $this->fail('RA Treasurer could not load com_ra_tools helpers.');
        }
        $this->toolsHelper = new ToolsHelper();

        // All good
        return true;
    }
}