<?php
/**
 * @package     JT Navi
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

if (!$available) { return; }
$wa = $app->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('mod_jtnavi');
$wa->useStyle('mod_jtnavi.ui')->useScript('mod_jtnavi.ui');
$e = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$id = 'jtnavi-' . (int) $module->id;
$mode = (string) $params->get('display_mode', 'inline');
if (!in_array($mode, ['inline', 'panel', 'compact'], true)) { $mode = 'inline'; }
$buttonLabel = trim((string) $params->get('button_label', '')) ?: Text::_('MOD_JTNAVI_OPEN');
$strings = [];
foreach (['LOADING', 'ERROR', 'AI_RESULT', 'LOCAL_RESULT', 'SOURCES', 'TIMEOUT', 'SHOW_MORE', 'SHOW_LESS'] as $key) {
    $strings[strtolower($key)] = Text::_('MOD_JTNAVI_' . $key);
}
?>
<section class="jtnavi jtnavi--<?php echo $e($mode); ?>" id="<?php echo $e($id); ?>"
    data-endpoint="<?php echo $e($endpoint); ?>" data-language="<?php echo $e($languageTag); ?>"
    data-strings="<?php echo $e(json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)); ?>"
    aria-label="JT Navi">
    <?php if ($mode === 'panel') : ?>
        <button class="jtnavi__toggle" type="button" aria-expanded="false" aria-controls="<?php echo $e($id); ?>-body"><?php echo $e($buttonLabel); ?></button>
    <?php endif; ?>
    <div class="jtnavi__body" id="<?php echo $e($id); ?>-body" <?php echo $mode === 'panel' ? 'hidden' : ''; ?>>
        <div class="jtnavi__eyebrow">JT NAVI</div>
        <h2 class="jtnavi__heading"><?php echo $e($heading); ?></h2>
        <p class="jtnavi__intro"><?php echo Text::_('MOD_JTNAVI_INTRO'); ?></p>
        <form class="jtnavi__form">
            <label class="jtnavi__sr-only" for="<?php echo $e($id); ?>-question"><?php echo Text::_('MOD_JTNAVI_QUESTION'); ?></label>
            <div class="jtnavi__input-row">
                <input id="<?php echo $e($id); ?>-question" name="question" type="text" minlength="2" maxlength="500" required autocomplete="off" placeholder="<?php echo $e(Text::_('MOD_JTNAVI_PLACEHOLDER')); ?>">
                <button class="jtnavi__submit" type="submit"><?php echo Text::_('MOD_JTNAVI_SEND'); ?></button>
            </div>
            <?php if ($aiEnabled) : ?>
                <label class="jtnavi__consent"><input type="checkbox" name="ai" value="1"> <span><?php echo Text::_('MOD_JTNAVI_CONSENT'); ?></span></label>
            <?php endif; ?>
        </form>
        <?php if ($suggestions) : ?>
            <div class="jtnavi__suggestions" aria-label="<?php echo $e(Text::_('MOD_JTNAVI_SUGGESTIONS')); ?>">
                <?php foreach ($suggestions as $suggestion) : ?>
                    <button type="button" class="jtnavi__chip" data-query="<?php echo $e($suggestion['query']); ?>"><?php echo $e($suggestion['label']); ?></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="jtnavi__status" role="status" aria-live="polite" aria-atomic="true"></div>
        <div class="jtnavi__results" tabindex="-1" hidden></div>
        <noscript><p><?php echo Text::_('MOD_JTNAVI_NOSCRIPT'); ?></p></noscript>
    </div>
</section>
