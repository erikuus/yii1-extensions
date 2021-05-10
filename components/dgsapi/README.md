Yii 1 raamistiku laiendus DGS API kasutamiseks
==========================================================

API dokumentatsioon
-----------------------
https://www.ra.ee/dgs-api/doc/index

Paigaldus
-----------------------
Laadi laienduse failid Githubist alla ja salvesta need **extensions/components/dgsapi/** kausta.

Seadistus
--------------------
Lisa konfiguratsioonifailis komponentide hulka **XDgsApi**:

```php
'components'=>array(
    'dgs'=> array(
        'class'=>'ext.components.dgsapi.XDgsApi',
        'url'=>'https://www.ra.ee/dgs-api/api/v1/dip/'
    )
)
```

Kasutamine
--------------------
```php
Yii::app()->dgs->request('get-file-path', array(
    'uid'=>'b1bc1876-d34b-57d1-a7a5-7563ef53333b'
);
```