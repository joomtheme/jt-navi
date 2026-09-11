<?php
/**
 * @package     JT Navi
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace JoomTheme\Component\JtNavi\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public array $overview = [];
    public function display($tpl = null)
    {
        $this->overview = $this->getModel()->getOverview();
        ToolbarHelper::title(Text::_('COM_JTNAVI'), 'compass');
        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_jtnavi')) {
            ToolbarHelper::preferences('com_jtnavi');
        }
        parent::display($tpl);
    }
}
