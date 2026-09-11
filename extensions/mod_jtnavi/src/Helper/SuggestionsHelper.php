<?php
/** @package JT Navi @copyright (C) 2026 JoomTheme. @license GPL-2.0-or-later */
namespace JoomTheme\Module\JtNavi\Site\Helper;
defined('_JEXEC') or die;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
final class SuggestionsHelper
{
    public static function getSuggestions(Registry $params, bool $starters): array
    {
        $mode = $params->get('suggestions_mode', 'default');
        if ($mode === 'none') { return []; }
        if ($mode !== 'custom') {
            return $starters ? array_map(static fn ($topic) => [
                'label' => Text::_('MOD_JTNAVI_TOPIC_' . $topic),
                'query' => Text::_('MOD_JTNAVI_QUERY_' . $topic),
            ], ['INSTALL', 'REQUIREMENTS', 'DOCS']) : [];
        }
        $rows = $params->get('suggestions', []);
        if (is_string($rows)) { $rows = json_decode($rows, true) ?? []; }
        $result = [];
        foreach (array_slice((array) $rows, 0, 8) as $row) {
            $row = (array) $row;
            $label = trim(strip_tags((string) ($row['label'] ?? '')));
            $query = trim(strip_tags((string) ($row['query'] ?? '')));
            if ($label !== '' && mb_strlen($query) >= 2) {
                $result[] = ['label' => mb_substr($label, 0, 80), 'query' => mb_substr($query, 0, 500)];
            }
        }
        return $result;
    }
}
