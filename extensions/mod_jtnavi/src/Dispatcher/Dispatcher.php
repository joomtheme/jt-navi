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
namespace JoomTheme\Module\JtNavi\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use JoomTheme\Module\JtNavi\Site\Helper\SuggestionsHelper;

class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $config = ComponentHelper::getParams('com_jtnavi');
        $data['available'] = ComponentHelper::isEnabled('com_jtnavi') && (bool) $config->get('enabled', 1);
        $data['aiEnabled'] = (bool) $config->get('ai_enabled', 0);
        $data['starters'] = (bool) $config->get('use_starters', 1);
        $data['suggestions'] = SuggestionsHelper::getSuggestions($data['params'], $data['starters']);
        $data['endpoint'] = Uri::root(true) . '/index.php?option=com_jtnavi&task=assistant.ask&format=json';
        $data['languageTag'] = $this->app->getLanguage()->getTag();
        $data['heading'] = trim((string) $data['params']->get('heading', '')) ?: Text::_('MOD_JTNAVI_HEADING');
        return $data;
    }
}
