<?php
/**
 * This is a simple example layout for a page with header and footer.
 *
 * The content for the optional title must be supplied in clips:
 *
 *      title   : page headline (optional)
 */
?>
<?php $this->beginContent('/layouts/pdf'); ?>
    <div id="main">

        <?php if(isset($this->clips['title'])): ?>
            <div id="title">
                <?php echo $this->clips['title'] ?>
            </div>
        <?php endif; ?>

        <?php echo $content; ?>

    </div>
<?php $this->endContent(); ?>
