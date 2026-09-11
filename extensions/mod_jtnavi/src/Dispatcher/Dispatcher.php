<?php
/**
 * @package     JT Navi
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
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
