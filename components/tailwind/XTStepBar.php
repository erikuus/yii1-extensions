<?php
/**
 * XTStepBar class file
 *
 * XTStepBar displays progressbar of labeled steps using Tailwind CSS.
 *
 * The following example shows how to use XTStepBar:
 * <pre>
 * $this->widget('ext.components.tailwind.XTStepBar', array(
 *     'steps'=>array(
 *         array('label'=>'fill application', 'active'=>true),
 *         array('label'=>'submit application'),
 *         array('label'=>'application is being processed'),
 *     ),
 * ));
 * </pre>
 *
 * @link http://kodhus.com/newsite/step-progress-bar-css-only/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.1
 */
class XTStepBar extends CWidget
{
	/**
	 * @var array list of steps. Each step is specified as an array of name-value pairs.
	 * Possible option names include the following:
	 * <ul>
	 * <li>label: string, required, specifies the step label.</li>
	 * <li>active: boolean, optional, whether this step is active. Defaults to false.</li>
	 * </ul>
	 */
	public $steps=array();
	/**
	 * @var string the CSS class for the mobile widget container. Defaults to 'block md:hidden mb-6'.
	 */
	public $mobileContainerCssClass='block md:hidden mb-6';
	/**
	 * @var string the CSS class for the desktop widget container. Defaults to 'hidden md:block mb-12 md:mx-6'.
	 */
	public $desktopContainerCssClass='hidden md:block mb-12 md:mx-6';
	/**
	 * @var string the CSS class for the desktop widget numbers container. Defaults to 'flex items-center justify-between mx-20'.
	 */
	public $desktopNumbersContainerCssClass='flex items-center justify-between mx-20';
	/**
	 * @var string the CSS class for the desktop widget labels container. Defaults to 'flex justify-between mx-1'.
	 */
	public $desktopLabelsContainerCssClass='flex justify-between mx-1';
	/**
	 * @var boolean whether the widget is visible. Defaults to true.
	 */
	public $visible=true;

	/**
	 * Calls {@link renderStepBar} to render the menu.
	 */
	public function run()
	{
		if($this->visible)
		{
			$this->renderMobileStepBar($this->steps);
			$this->renderDesktopStepBar($this->steps);
		}
	}

	/**
	 * Renders the mobile stepbar.
	 * @param array steps.
	 */
	protected function renderMobileStepBar($steps)
	{
		if(count($steps))
		{
			echo "<ul class=\"{$this->mobileContainerCssClass}\">\n";
			$this->renderMobileSteps($steps);
			echo "</ul>\n";
		}
	}

	/**
	 * Recursively renders mobile stepbar steps.
	 * @param array the steps to be rendered recursively
	 */
	protected function renderMobileSteps($steps)
	{
		$lastStep=end($steps);

		foreach($steps as $step)
		{
			if(!isset($step['visible']) || $step['visible'])
			{
				echo "<li class=\"relative flex gap-x-2\">\n";

					$class=($step==$lastStep) ? 'h-6' : '-bottom-6';

					echo "<div class=\"absolute mt-2 left-0 top-0 flex w-6 justify-center $class\">\n";
						echo "<div class=\"w-px bg-gray-200\"></div>\n";
					echo "</div>\n";

					$bullet=(isset($step['active']) && $step['active']) ? '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-green-500"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>' : '<div class="h-1.5 w-1.5 rounded-full bg-gray-100 ring-1 ring-gray-200"></div>';

					echo "<div class=\"relative flex mt-2 h-6 w-6 flex-none items-center justify-center bg-white\">\n";
						echo $bullet;
					echo "</div>\n";

					$color=(isset($step['active']) && $step['active']) ? 'text-green-500' : 'text-gray-200';

					echo "<div class=\"flex-auto leading-5\">\n";
						echo "<div class=\"block mt-3 ml-0.5 rounded-md font-bold text-sm uppercase $color\">\n";
							echo $step['label'];
						echo "</div>\n";
					echo "</div>\n";

				echo "</li>\n";
			}
		}
	}

	/**
	 * Renders the desktop stepbar.
	 * @param array steps.
	 */
	protected function renderDesktopStepBar($steps)
	{
		if(count($steps))
		{
			echo "<div class=\"{$this->desktopContainerCssClass}\">\n";
				echo "<div class=\"{$this->desktopNumbersContainerCssClass}\">\n";
					$this->renderDesktopStepsNumbers($steps);
				echo "</div>\n";
				echo "<div class=\"{$this->desktopLabelsContainerCssClass}\">\n";
					$this->renderDesktopStepsLabels($steps);
				echo "</div>\n";
			echo "</div>\n";
		}
	}

	/**
	 * Recursively renders desktop stepbar steps numbers.
	 * @param array the steps to be rendered recursively
	 */
	protected function renderDesktopStepsNumbers($steps)
	{
		$lastStep=end($steps);
		$stepNumber=1;
		foreach($steps as $step)
		{
			if(!isset($step['visible']) || $step['visible'])
			{
				$circleColor=(isset($step['active']) && $step['active']) ? 'bg-green-500 text-white' : 'text-gray-200 border-2 border-gray-200';

				echo "<div class=\"w-10 h-10 flex items-center justify-center rounded-full $circleColor\">\n";
					echo $stepNumber;
				echo "</div>\n";

				if($step!=$lastStep)
				{
					$lineColor=(isset($step['active']) && $step['active']) ? 'border-green-500' : 'border-gray-200';

					echo "<div class=\"flex-auto border-t-2 $lineColor\"></div>\n";
				}

				$stepNumber++;
			}

		}
	}

	/**
	 * Recursively renders desktop stepbar steps labels.
	 * @param array the steps to be rendered recursively
	 */
	protected function renderDesktopStepsLabels($steps)
	{
		foreach($steps as $step)
		{
			if(!isset($step['visible']) || $step['visible'])
			{
				$color=(isset($step['active']) && $step['active']) ? 'text-green-500' : 'text-gray-200';

				echo "<span class=\"w-48 text-center font-bold text-sm uppercase mt-3 $color\">\n";
					echo $step['label'];
				echo "</span>\n";
			}
		}
	}
}