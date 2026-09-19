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
namespace JoomTheme\Component\JtNavi\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DashboardModel extends BaseDatabaseModel
{
    public function getOverview(): array
    {
        $params = ComponentHelper::getParams('com_jtnavi');
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select($db->quoteName(['day', 'requests']))
            ->from($db->quoteName('#__jtnavi_usage'))->where($db->quoteName('id') . ' = 1');
        $usage = $db->setQuery($query)->loadAssoc() ?: [];
        $diagnostic = null;
        try {
            $check = $db->getQuery(true)->select($db->quoteName(['result_code', 'http_status', 'checked_at']))
                ->from($db->quoteName('#__jtnavi_diagnostics'))->where($db->quoteName('id') . ' = 1');
            $diagnostic = $db->setQuery($check)->loadAssoc() ?: null;
        } catch (\Throwable $error) {
            $diagnostic = ['result_code' => 'COM_JTNAVI_DIAG_STORAGE', 'http_status' => 0, 'checked_at' => ''];
        }
        return [
            'diagnostic' => $diagnostic,
            'counterReady' => !empty($usage),
            'enabled' => (bool) $params->get('enabled', 1),
            'ai' => (bool) $params->get('ai_enabled', 0),
            'key' => trim((string) (getenv('JTNAVI_OPENAI_API_KEY') ?: $params->get('api_key', ''))) !== '',
            'sources' => count((array) $params->get('sources', [])),
            'requests' => ($usage['day'] ?? '') === gmdate('Y-m-d') ? (int) ($usage['requests'] ?? 0) : 0,
            'limit' => max(0, min(10000, (int) $params->get('daily_limit', 50))),
        ];
    }
}
