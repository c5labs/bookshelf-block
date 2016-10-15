<?php
defined('C5_EXECUTE') or die('Access Denied.');

/*
 * This file is part of Bookshelf.
 *
 * (c) Oliver Green <oliver@c5dev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

?>
<div id="blockBoilerplateForm">

    <div class="form-group" id="fileSets">
        <?php echo $form->label('selected_file_set_ids', t('File Set(s)')); ?>
        <?php echo $form->selectMultiple(
            $view->field('selected_file_set_ids'),
            $available_file_sets,
            (is_array($selected_file_set_ids))  ? $selected_file_set_ids : null,
            array('style' => 'border: 1px solid #ccc')
        ); ?>
        <script>
            $(function () {
                $('select[name="<?php echo $view->field("selected_file_set_ids"); ?>[]"]').select2();
            });
        </script>
    </div>

    <div class="form-group" id="fileSets">
        <?php echo $form->label('numPerRow', t('Number per row')); ?>
        <?php echo $form->text('numPerRow', $this->controller->numPerRow, 'form-control'); ?>
    </div>

</div>