<?php
defined('C5_EXECUTE') or die('Access Denied.');

/*
 * This file is part of Bookshelf.
 *
 * (c) Oliver Green <oliver@c5labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

?>
<?php if (count($files) > 0): ?>
<div class="bookshelf-block">
    <?php if (extension_loaded('imagick')): ?>
        <?php foreach ($files as $row): ?>
            <div class="row">
            <?php foreach (array_pad($row, $numPerRow, null) as $file): ?>
                <div class="col-sm-<?php echo (12 / $numPerRow); ?>">
                <?php if ($file): ?>
                    <a href="<?php echo $file['version']->getDownloadURL(); ?>" class="bookshelf-item-wrapper">
                        <figure class="bookshelf-item <?php echo ($file['cover_exists'] ? '' : 'no-cover'); ?>">
                            <img src="<?php echo $file['cover']; ?>">
                            <div class="no-cover-image">No cover image</div>
                        </figure>
                        <?php if ($showTitles) { ?><span class="bookshelf-item-title"><?php echo $file['version']->getTitle(); ?></span><?php } ?>
                    </a>
                <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <?php echo t('Please install the Imagick PHP extension before using this block.'); ?>
    <?php endif; ?>
</div>
<?php else: ?>
    <div class="bookshelf-block no-results">
        <span>There are no files in the selected sets.</span>
    </div>
<?php endif; ?>