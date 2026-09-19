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
namespace JoomTheme\Component\JtNavi\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

class AssistantController extends BaseController
{
    /** Get a fresh session token even when the page or module came from a cache. */
    public function session(): void
    {
        if (!$this->enabled()) { $this->respond(null, Text::_('COM_JTNAVI_DISABLED'), 403); }
        $this->respond(['token' => Session::getFormToken()]);
    }

    public function ask(): void
    {
        $lang = $this->input->post->getString('language', $this->app->getLanguage()->getTag());
        if (!preg_match('/^[a-z]{2,3}-[A-Z]{2}$/D', $lang)) { $lang = 'en-GB'; }
        $this->app->getLanguage()->load('com_jtnavi', JPATH_SITE, $lang, true, true);
        if (strtoupper($this->input->getMethod()) !== 'POST') { $this->respond(null, Text::_('COM_JTNAVI_POST_ONLY'), 405); }
        if (!Session::checkToken('post')) { $this->respond(null, Text::_('JINVALID_TOKEN'), 403); }
        if (!$this->enabled()) { $this->respond(null, Text::_('COM_JTNAVI_DISABLED'), 403); }
        $question = trim($this->input->post->getString('question', ''));
        if (mb_strlen($question, 'UTF-8') < 2 || mb_strlen($question, 'UTF-8') > 500) {
            $this->respond(null, Text::_('COM_JTNAVI_QUESTION_LENGTH'), 400);
        }
        $session = $this->app->getSession();
        $now = time();
        $hits = array_values(array_filter((array) $session->get('jtnavi.hits', []), static fn ($t) => (int) $t > $now - 60));
        if (count($hits) >= 20) { $this->respond(null, Text::_('COM_JTNAVI_RATE_LIMIT'), 429); }
        $hits[] = $now;
        $session->set('jtnavi.hits', $hits);
        try {
            $model = $this->getModel('Assistant', 'Site', ['ignore_request' => true]);
            $data = $model->answer($question, $this->input->post->getBool('ai', false), $lang);
            $this->respond($data);
        } catch (\Throwable $error) {
            $this->respond(null, Text::_('COM_JTNAVI_SERVER_ERROR'), 500);
        }
    }
    private function enabled(): bool
    {
        return (bool) ComponentHelper::getParams('com_jtnavi')->get('enabled', 1);
    }
    private function respond($data, ?string $message = null, int $status = 200): void
    {
        $this->app->setHeader('status', (string) $status, true);
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->setHeader('Cache-Control', 'no-store, private', true);
        $this->app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $this->app->sendHeaders();
        echo new JsonResponse($data, $message, $status >= 400, true);
        $this->app->close();
    }
}
