<?php
/**
 * This is a simple example layout for a letter with address box and title.
 *
 * The content for address and title (optional) must be supplied in clips:
 *
 *      address : content of the address box
 *      title   : letter headline (optional)
 */
?>
<?php $this->beginContent('/layouts/pdf'); ?>

    <div id="addressbox">
        <div class="sender">Bad Designs, Ugly street 25, DE-12345 Horror, Germany</div>
        <div class="address">
            <?php echo $this->clips['address'] ?>
        </div>
    </div>

    <div id="main" class="letter">

        <?php if(isset($this->clips['title'])): ?>
            <div id="title">
                <?php echo $this->clips['title'] ?>
            </div>
        <?php endif; ?>

        <?php echo $content ?>

    </div>

<?php $this->endContent(); ?>
