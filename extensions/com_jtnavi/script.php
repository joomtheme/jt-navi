<?php
/**
 * @package     JT Navi
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

return new class implements InstallerScriptInterface {
    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }
        if (version_compare(PHP_VERSION, '8.3.0', '<') || version_compare(JVERSION, '6.0.0', '<')
            || version_compare(JVERSION, '7.0.0', '>=')) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_JTNAVI_REQUIREMENTS'), 'error');
            return false;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        if (!in_array($db->getServerType(), ['mysql'], true)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_JTNAVI_DATABASE'), 'error');
            return false;
        }
        return true;
    }
    public function install(InstallerAdapter $parent): bool { return true; }
    public function update(InstallerAdapter $parent): bool { return true; }
    public function uninstall(InstallerAdapter $parent): bool { return true; }
    public function postflight(string $type, InstallerAdapter $parent): bool { return true; }
};
