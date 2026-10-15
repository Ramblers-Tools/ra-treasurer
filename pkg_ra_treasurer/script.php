<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Language\Text;

class pkg_ra_treasurerInstallerScript
{
    private const MINIMUM_EVENTS_VERSION = '5.0.18';
    private const MINIMUM_TOOLS_VERSION = '4.0.12';

    private function getInstalledVersion(string $element): ?string
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
        echo '<p style="color:#c00;"><strong>' . htmlspecialchars($message) . '</strong></p>';
        return false;
    }

    public function preflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        // Check com_ra_events
        $eventsVersion = $this->getInstalledVersion('com_ra_events');
        if ($eventsVersion === null) {
            return $this->fail('RA Treasurer requires com_ra_events ' . self::MINIMUM_EVENTS_VERSION . ' or later; com_ra_events is not installed.');
        }
//        if (version_compare($mailmanVersion, self::MINIMUM_MAILMAN_VERSION, '<')) {
//            return $this->fail('RA Treasurer requires com_ra_events ' . self::MINIMUM_EVENTS_VERSION . ' or later; found ' . $mailmanVersion . '. Please upgrade com_ra_events first.');
//        }

        // Check com_ra_tools (example)
        $toolsVersion = $this->getInstalledVersion('com_ra_tools');
        if ($toolsVersion === null || version_compare($toolsVersion, self::MINIMUM_TOOLS_VERSION, '<')) {
            return $this->fail('RA Treasurer requires com_ra_tools ' . self::MINIMUM_TOOLS_VERSION . ' or later; found ' . ($toolsVersion ?: 'not installed') . '.');
        }

        // All good
        return true;
    }
}