<?php
/**
/**
 * XTActiveForm class file.
 *
 * XTFlagColumn extends XFlagColumn adding SVG icons
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
Yii::import('ext.widgets.grid.flagcolumn.XFlagColumn');
class XTFlagColumn extends XFlagColumn
{
	public $flagClasses=' flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full hover:bg-green-100';

	public $yesFlag = '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="m424-312 282-282-56-56-226 226-114-114-56 56 170 170ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Z"/></svg>';

	public $noFlag = '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Z"/></svg>';
}