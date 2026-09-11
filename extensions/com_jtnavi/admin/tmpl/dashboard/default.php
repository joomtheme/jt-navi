<?php
/**
 * @package     JT Navi
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="container-fluid">
    <div class="card mb-4"><div class="card-body">
        <h2>JT Navi <span class="badge bg-secondary">1.0.0</span></h2>
        <p class="lead"><?php echo Text::_('COM_JTNAVI_WELCOME'); ?></p>
        <p><?php echo Text::_('COM_JTNAVI_ALPHA'); ?></p>
        <a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_modules&view=modules&client_id=0&filter[module]=mod_jtnavi'); ?>"><?php echo Text::_('COM_JTNAVI_OPEN_MODULES'); ?></a>
    </div></div>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><h3><?php echo Text::_('COM_JTNAVI_STATUS'); ?></h3><p><?php echo Text::_($this->overview['enabled'] ? 'JENABLED' : 'JDISABLED'); ?></p><p><?php echo Text::_($this->overview['ai'] && $this->overview['key'] ? 'COM_JTNAVI_AI_READY' : 'COM_JTNAVI_LOCAL_READY'); ?></p></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><h3><?php echo Text::_('COM_JTNAVI_SOURCES'); ?></h3><p class="fs-2"><?php echo (int) $this->overview['sources']; ?></p><p><?php echo Text::_('COM_JTNAVI_CUSTOM_COUNT'); ?></p></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><h3><?php echo Text::_('COM_JTNAVI_USAGE'); ?></h3><p class="fs-2"><?php echo (int) $this->overview['requests']; ?> / <?php echo (int) $this->overview['limit']; ?></p><p><?php echo Text::_('COM_JTNAVI_USAGE_DESC'); ?></p><?php if (!$this->overview['counterReady']) : ?><p class="text-warning"><?php echo Text::_('COM_JTNAVI_COUNTER_MISSING'); ?></p><?php endif; ?></div></div></div>
    </div>
    <div class="card mb-4"><div class="card-body">
        <h3><?php echo Text::_('COM_JTNAVI_DIAG_TITLE'); ?></h3>
        <?php if ($this->overview['diagnostic']) : ?>
            <?php $diagnostic = $this->overview['diagnostic']; ?>
            <p><?php echo htmlspecialchars(Text::_((string) $diagnostic['result_code']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
            <p><code><?php echo htmlspecialchars((string) $diagnostic['result_code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></code></p>
            <?php if ((int) $diagnostic['http_status'] > 0) : ?><p>HTTP <?php echo (int) $diagnostic['http_status']; ?></p><?php endif; ?>
            <p><?php echo htmlspecialchars((string) $diagnostic['checked_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> UTC</p>
        <?php else : ?>
            <p><?php echo Text::_('COM_JTNAVI_DIAG_NONE'); ?></p>
        <?php endif; ?>
        <p><?php echo Text::_('COM_JTNAVI_DIAG_PRIVACY'); ?></p>
    </div></div>
    <div class="card"><div class="card-body"><h3><?php echo Text::_('COM_JTNAVI_SETUP'); ?></h3>
        <ol><li><?php echo Text::_('COM_JTNAVI_SETUP_1'); ?></li><li><?php echo Text::_('COM_JTNAVI_SETUP_2'); ?></li><li><?php echo Text::_('COM_JTNAVI_SETUP_3'); ?></li></ol>
        <p><?php echo Text::_('COM_JTNAVI_PRIVACY'); ?></p>
        <p><a href="https://joomtheme.com" target="_blank" rel="noopener noreferrer">JoomTheme</a> · <a href="mailto:support@joomtheme.com">support@joomtheme.com</a></p>
    </div></div>
</div>
