<?php
/**
 * @package     JT Navi
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
            Factory::getApplication()->enqueueMessage(Text::_('PKG_JTNAVI_REQUIREMENTS'), 'error');
            return false;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        if (!in_array($db->getServerType(), ['mysql'], true)) {
            Factory::getApplication()->enqueueMessage(Text::_('PKG_JTNAVI_DATABASE'), 'error');
            return false;
        }
        return true;
    }
    public function install(InstallerAdapter $parent): bool { return true; }
    public function update(InstallerAdapter $parent): bool { return true; }
    public function uninstall(InstallerAdapter $parent): bool { return true; }
    public function postflight(string $type, InstallerAdapter $parent): bool { return true; }
};
