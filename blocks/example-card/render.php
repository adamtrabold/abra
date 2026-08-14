<?php
declare(strict_types=1);
/**
 * Example Card block.
 * Duplicate /blocks/example-card/ to create a new ACF block.
 * Register fields in ACF admin — they auto-save to acf-json/.
 *
 * @var array $block Block attributes from block.json.
 */

$heading = get_field('heading');
$body    = get_field('body');
$anchor  = !empty($block['anchor']) ? ' id="' . esc_attr($block['anchor']) . '"' : '';
?>
<div class="block-example-card"<?= $anchor ?>>
	<?php if ($heading) : ?>
		<h2><?= esc_html($heading) ?></h2>
	<?php endif; ?>
	<?php if ($body) : ?>
		<p><?= esc_html($body) ?></p>
	<?php endif; ?>
	<?php if (!$heading && !$body) : ?>
		<p><em>Add your fields in ACF admin, then edit this template in blocks/example-card/render.php</em></p>
	<?php endif; ?>
</div>
