<?php
/**
 * @package     JT Navi
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace JoomTheme\Component\JtNavi\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->getView('guide', 'html')->display();
        return $this;
    }
}
