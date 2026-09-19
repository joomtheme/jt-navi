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
namespace JoomTheme\Component\JtNavi\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use JoomTheme\Component\JtNavi\Site\Service\AiService;
use JoomTheme\Component\JtNavi\Site\Service\SourceService;

class AssistantModel extends BaseDatabaseModel
{
    public function answer(string $question, bool $consent, string $language): array
    {
        $params = ComponentHelper::getParams('com_jtnavi');
        $sourceService = new SourceService($params, $this->getDatabase(), $language);
        $choices = $sourceService->journey($question);
        $guide = $sourceService->installationGuide($question);
        $sources = $choices ? [] : ($guide ? $guide['sources'] : $sourceService->search($question));
        $data = ['answer' => Text::_($choices ? 'COM_JTNAVI_CHOOSE_ENV' : ($sources ? 'COM_JTNAVI_FOUND' : 'COM_JTNAVI_NOT_FOUND')),
            'sources' => $sources, 'choices' => $choices, 'mode' => 'local', 'notice' => ''];
        if ($guide) {
            $data['answer'] = $guide['intro'];
            $data['guide'] = ['title' => $guide['title'], 'steps' => $guide['steps']];
        }
        // Built-in branching and installation instructions make no paid API call.
        if ($consent && $params->get('ai_enabled', 0) && $sources && !$choices && !$guide) {
            $ai = new AiService($params);
            if (!$ai->hasKey()) {
                $data['notice'] = Text::_('COM_JTNAVI_AI_UNAVAILABLE');
                $this->recordAiDiagnostic('COM_JTNAVI_DIAG_KEY_MISSING', 0);
            } elseif (($reservationError = $this->reserveAiRequest()) !== '') {
                $data['notice'] = Text::_($reservationError);
            } else {
                try {
                    $data['answer'] = $ai->answer($question, $sources, $language);
                    $data['mode'] = 'ai';
                    $this->recordAiDiagnostic('COM_JTNAVI_DIAG_SUCCESS', 200);
                } catch (\Throwable $error) {
                    // Only a fixed diagnostic category is stored; raw exception text is discarded.
                    $this->recordAiDiagnostic($error->getMessage(), (int) $error->getCode());
                    $data['notice'] = Text::_('COM_JTNAVI_AI_UNAVAILABLE');
                }
            }
        }
        return $data;
    }

    private function recordAiDiagnostic(string $category, int $status): void
    {
        $allowed = ['SUCCESS', 'KEY_MISSING', 'MODEL_CONFIG', 'INTERNAL', 'NETWORK', 'TLS', 'DNS', 'TIMEOUT',
            'AUTH', 'QUOTA', 'RATE', 'CREDIT', 'PROJECT_SPEND', 'ORG_SPEND', 'ORG_USAGE', '429', 'ACCESS', 'MODEL', 'REQUEST', 'PROVIDER', 'HTTP', 'RESPONSE', 'EMPTY'];
        $allowed = array_map(static fn ($name) => 'COM_JTNAVI_DIAG_' . $name, $allowed);
        if (!in_array($category, $allowed, true)) { $category = 'COM_JTNAVI_DIAG_INTERNAL'; }
        $status = $status >= 100 && $status <= 599 ? $status : 0;
        try {
            $db = $this->getDatabase();
            $columns = $db->quoteName(['id', 'result_code', 'http_status', 'checked_at']);
            $query = 'INSERT INTO ' . $db->quoteName('#__jtnavi_diagnostics')
                . ' (' . implode(', ', $columns) . ') VALUES (1, ' . $db->quote($category) . ', ' . $status
                . ', ' . $db->quote(gmdate('Y-m-d H:i:s')) . ') ON DUPLICATE KEY UPDATE '
                . $db->quoteName('result_code') . ' = VALUES(' . $db->quoteName('result_code') . '), '
                . $db->quoteName('http_status') . ' = VALUES(' . $db->quoteName('http_status') . '), '
                . $db->quoteName('checked_at') . ' = VALUES(' . $db->quoteName('checked_at') . ')';
            $db->setQuery($query)->execute();
        } catch (\Throwable $error) {
            // Diagnostics must never turn a successful AI response into a failure.
        }
    }

    private function reserveAiRequest(): string
    {
        $params = ComponentHelper::getParams('com_jtnavi');
        $app = Factory::getApplication();
        $session = $app->getSession();
        $today = gmdate('Y-m-d');
        $state = (array) $session->get('jtnavi.ai', []);
        $count = ($state['day'] ?? '') === $today ? (int) ($state['count'] ?? 0) : 0;
        $limit = max(0, min(10000, (int) $params->get('daily_limit', 50)));
        if ($limit === 0) { return 'COM_JTNAVI_AI_DISABLED_LIMIT'; }
        if ($count >= max(1, min(100, (int) $params->get('session_limit', 10)))) {
            return 'COM_JTNAVI_AI_SESSION_LIMIT';
        }
        $db = $this->getDatabase();
        try {
            // Repair a missing singleton after a table-only maintenance repair.
            // INSERT IGNORE preserves the date and budget of an existing row.
            $db->setQuery('INSERT IGNORE INTO ' . $db->quoteName('#__jtnavi_usage')
                . ' (' . $db->quoteName('id') . ', ' . $db->quoteName('day') . ', ' . $db->quoteName('requests') . ')'
                . ' VALUES (1, ' . $db->quote($today) . ', 0)')->execute();
        // A single conditional UPDATE reserves budget atomically across all visitors.
        // Assignment order is intentional: requests compares the OLD day before day changes.
        $query = $db->getQuery(true)->update($db->quoteName('#__jtnavi_usage'))
            ->set($db->quoteName('requests') . ' = CASE WHEN ' . $db->quoteName('day') . ' = ' . $db->quote($today)
                . ' THEN ' . $db->quoteName('requests') . ' + 1 ELSE 1 END')
            ->set($db->quoteName('day') . ' = ' . $db->quote($today))
            ->where($db->quoteName('id') . ' = 1')
            ->where('(' . $db->quoteName('day') . ' <> ' . $db->quote($today) . ' OR ' . $db->quoteName('requests') . ' < ' . $limit . ')');
        $db->setQuery($query)->execute();
        if ((int) $db->getAffectedRows() !== 1) {
            $check = $db->getQuery(true)->select($db->quoteName(['day', 'requests']))
                ->from($db->quoteName('#__jtnavi_usage'))->where($db->quoteName('id') . ' = 1');
            $row = $db->setQuery($check)->loadAssoc();
            return $row && $row['day'] === $today && (int) $row['requests'] >= $limit
                ? 'COM_JTNAVI_AI_LIMIT' : 'COM_JTNAVI_AI_COUNTER_ERROR';
        }
        } catch (\Throwable $error) {
            return 'COM_JTNAVI_AI_COUNTER_ERROR';
        }
        $session->set('jtnavi.ai', ['day' => $today, 'count' => $count + 1]);
        return '';
    }
}
