<?php
/**
 * XTabView displays contents in multiple tabs.
 *
 * Only difference between XTabView and CTabView is that
 * XTabView adds html element id to tab links
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTabView extends CTabView
{
	/**
	 * Renders the header part.
	 */
	protected function renderHeader()
	{
			$widgetId=$this->getId();
			echo "<ul class=\"tabs\">\n";
			foreach($this->tabs as $id=>$tab)
			{
					$title=isset($tab['title'])?$tab['title']:'undefined';
					$active=$id===$this->activeTab?' class="active"' : '';
					$url=isset($tab['url'])?$tab['url']:"#{$id}";
					echo "<li><a id=\"{$widgetId}_{$id}\" href=\"{$url}\"{$active}>{$title}</a></li>\n";
			}
			echo "</ul>\n";
	}
}