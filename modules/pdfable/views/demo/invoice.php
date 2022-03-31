<?php $this->layout='letter'; ?>

<?php $this->beginClip('address'); ?>
    Whos Sostupid<br />
    Braindeadstreet 7<br />
    12345 Moron
<?php $this->endClip(); ?>

<?php $this->clips['title'] = 'Invoice R11223344' ?>

<p>We have no idea why you hired us, because we hate design jobs. No, seriously, we really do.
But here's your invoice anyway.</p>

<table id="items">
    <tr>
        <th>Pos</th>
        <th>Description</th>
        <th>Amount</th>
    </tr>
    <tr>
        <td>1</td>
        <td>
            <b>PDF letter design</b><br />
            Very ugly letters
        </td>
        <td class="amount">
            <?php echo Yii::app()->numberFormatter->formatCurrency(1234.56,'EUR'); ?>
        </td>
    </tr>
    <tr>
        <td>2</td>
        <td>
            <b>Extra charges</b><br />
            We hated this job, so here are some extra charges
        </td>
        <td class="amount">
            <?php echo Yii::app()->numberFormatter->formatCurrency(2345.56,'EUR'); ?>
        </td>
    </tr>
    <tr class="total">
        <td></td>
        <td>
            <b>Total:</b>
        </td>
        <td class="amount">
            <b><?php echo Yii::app()->numberFormatter->formatCurrency(4567.89,'EUR'); ?>
        </td>
    </tr>
</table>
