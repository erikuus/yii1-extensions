Transliteration
=======================

Transliteration transliterate into Latin characters of Cyrillic characters.
Use the [international standard ISO 9](http://en.wikipedia.org/wiki/ISO_9).

Transliteration can be used as either a widget or a controller filter.

Usage as a class:
------------
~~~
$translator = new Transliteration();
$translator->standard = Transliteration::GOST_779A;
$text = $translator->transliterate($text);
~~~

Usage as validation rule:
------------
~~~
array('text', 'filter', 'filter' => array($obj = new Transliteration(), 'transliterate')),
~~~


Usage as widget:
------------
~~~
$this->beginWidget('Transliteration');
echo $model->content;
$this->endWidget('Transliteration');
~~~
