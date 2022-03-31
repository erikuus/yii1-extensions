<?php Yii::app()->clientScript->registerCss('codeBlocks', <<<CSS
.hl-code {
    padding: 10px;
    margin: 10px 0;
    background: #EEEEF0;
}
CSS
); ?>
<h1>PDFable Demo Page</h1>

<p>This demo requires that the <tt>wkhtmltopdf</tt> binary is installed on your system!</p>

<ul>
    <li>
        <?php echo CHtml::link('Single Page PDF', array('single')); ?>
        (
            <?php echo CHtml::link('HTML preview', array('single','preview'=>1)); ?> |
            <?php echo CHtml::link('Download', array('single','download'=>1)); ?>
        )
        <?php $this->beginWidget('CMarkdown'); ?>
~~~
[php]
    <?php echo "    <?php\n"; ?>
    public function actionSingle($preview=0, $download=0)
    {
        $this->layout = '/layouts/pdf';

        if($preview)
            $this->render('invoice');
        else
            $this->renderPdf('invoice', array(), array(), $download ? 'invoice.pdf' : null);
    }
~~~
        <?php $this->endWidget(); ?>
    </li>

    <li>
        <?php echo CHtml::link('Multi Page PDF', array('multi')); ?>
        (
            <?php echo CHtml::link('HTML preview', array('multi','preview'=>1)); ?> |
            <?php echo CHtml::link('Download', array('multi','download'=>1)); ?>
        )
        <p>Note, that the multi page HTML preview is just a demo. It may not really work well, as
        the generated HTML contains several <tt>&lt;body&gt;</tt> tags.</p>
        <?php $this->beginWidget('CMarkdown'); ?>
~~~
[php]
    <?php echo "    <?php\n"; ?>
    public function actionMulti($preview=0, $download=0)
    {
        if($preview)
        {
            $this->render('page1');
            $this->render('page2');
            $this->render('page3');
        }
        else
        {
            $pdf = $this->createPdf();
            $pdf->renderPage('page1');
            $pdf->renderPage('page2');
            $pdf->renderPage('page3');

            $pdf->send($download ? 'multi.pdf' : null);
        }
    }
~~~
        <?php $this->endWidget(); ?>
    </li>
</ul>
