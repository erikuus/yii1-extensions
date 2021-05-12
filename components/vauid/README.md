Yii 1 raamistiku laiendus VauID versiooni 2.0 kasutamiseks
==========================================================

https://github.com/erikuus/yii1-extensions/tree/master/components/vauid

Paigaldus
-----------------------

Laadi laienduse failid Githubist alla ja salvesta need **extensions/components/vauid/** kausta.

Minimaalne seadistus
--------------------
*Selle seadistuse puhul ei vaja rakendus eraldi kasutaja mudelit ja tabelit*

Lisa konfiguratsioonifailis komponentide hulka **XVauSecurityManager**, kus **###** asemel on salajane võti:

```
'components'=>array(
    'vauSecurityManager'=> array(
        'class'=>'ext.components.vauid.XVauSecurityManager',
        'validationKey'=>'###'
    )
)
```

Seadista **SiteController::actions()** järgmiselt:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction'
        )
    );
}
```

Suuna **SiteController::actionLogin** VauID sisselogimise teenuse aadressile, määrates **remoteUrl** väärtuseks eelnevalt defineeritud aktsiooni **SiteController::vauLogin**:

```
public function actionLogin()
{
    $vauUrl='https://www.ra.ee/vau/index.php/site/login?v=2&s=user_role&remoteUrl=';
    $remoteUrl=$this->createAbsoluteUrl('site/vauLogin', array(), 'https');
    $this->redirect($vauUrl.$remoteUrl);
}
```

Suuna väljalogimise link VauID väljalogimise teenuse aadressile, määrates **remoteUrl** väärtuseks **SiteController::actionLogout**:

```
$vauUrl='https://www.ra.ee/vau/index.php/site/logout?remoteUrl=';
$remoteUrl=Yii::app()->createAbsoluteUrl('site/logout', array(), 'https');
echo CHtml::link('Logout', $vauUrl.$remoteUrl);
```

Sellise seadistuse puhul loob laiendus pärast edukat VAU kaudu sisselogimist rakenduses sessiooni, kus:

- **Yii::app()->user->id** on kasutaja id VAU-s
- **Yii::app()->user->name** on kasutaja täisnimi VAU-s

Juurdepääsu piiramine
---------------------
*Spetsiaalse parameetri kaudu saab piirata, kes ja kuidas võivad VAU kaudu rakendusse siseneda*

Kui **authOptions['accessRules']['safelogin'] === true**, siis autoriseeritakse ainult kasutajad, kes autentisid ennast VAU-s ID-kaardi, Mobiil-ID või Smart-ID kaudu:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'accessRules'=>array(
                    'safelogin'=>true
                )
            )
        )
    );
}
```

Kui **authOptions['accessRules']['safehost'] === true**, siis autoriseeritakse ainult kasutajad, kes autentisid ennast arhiivi sisevõrgust:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'accessRules'=>array(
                    'safehost'=>true
                )
            )
        )
    );
}
```

Kui **authOptions['accessRules']['safe'] === true**, siis autoriseeritakse ainult kasutajad, kes autentisid ennast ID-kaardi, Mobiil-ID, Smart-ID kaudu
või arhiivi sisevõrgust:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'accessRules'=>array(
                    'safe'=>true
                )
            )
        )
    );
}
```

Kui **authOptions['accessRules']['employee'] === true**, siis autoriseeritakse ainult kasutajad, kellele on VAU-s antud töötaja õigused:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'accessRules'=>array(
                    'employee'=true
                )
            )
        )
    );
}
```

Kui on defineeritud **authOptions['accessRules']['roles']**, siis autoriseeritakse ainult kasutajad, kellele on VAU-s määratud mõni neist rollidest:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'accessRules'=>array(
                    'roles'=>array(
                        'ClientManager',
                        'EnquiryManager'
                    )
                )
            )
        )
    );
}
```

Tavaline seadistus
------------------
*Rakenduses on kasutaja mudel ja tabel, mille andmeid sünkroonitakse VAU andmetega*

Et näide oleks võimalikult selge, oletame, et rakendus hoiab kasutajate andmeid tabelis, mille tulpade nimed on eestikeelsed:

```
CREATE TABLE kasutaja
(
    kood serial NOT NULL,
    eesnimi character varying(64),
    perekonnanimi character varying(64),
    epost character varying(128),
    telefon character varying(64),
    CONSTRAINT pk_kasutaja PRIMARY KEY (kood)
)
```

Rakenduses on sellest tabelist genereeritud **class Kasutaja extends CActiveRecord**.

Sarnaselt minimaalse seadistusega lisa konfiguratsioonifailis komponentide hulka **XVauSecurityManager**, kus **###** asemel on salajane võti:

```
'components'=>array(
    'vauSecurityManager'=> array(
        'class'=>'ext.components.vauid.XVauSecurityManager',
        'validationKey'=>'###'
    )
)
```

Suuna **SiteController::actionLogin** VauID sisselogimise teenuse aadressile, määrates **remoteUrl** väärtuseks aktsiooni **SiteController::vauLogin**:

```
public function actionLogin()
{
    $vauUrl='https://www.ra.ee/vau/index.php/site/login?v=2&s=user_role&remoteUrl=';
    $remoteUrl=$this->createAbsoluteUrl('site/vauLogin', array(), 'https');
    $this->redirect($vauUrl.$remoteUrl);
}
```

Suuna väljalogimise link VauID väljalogimise teenuse aadressile, määrates **remoteUrl** väärtuseks **SiteController::actionLogout**:

```
$vauUrl='https://www.ra.ee/vau/index.php/site/logout?remoteUrl=';
$remoteUrl=Yii::app()->createAbsoluteUrl('site/logout', array(), 'https');
echo CHtml::link('Logout', $vauUrl.$remoteUrl);
```

Nüüd, kui me soovime teha nii, et rakenduse kasutajad on vastavuses VAU kasutajatega ja rakendusse sisselogimine käib VAU kaudu, siis peame kõigepealt lisama tabelisse uue tulba VAU kasutaja ID jaoks:

```
CREATE TABLE kasutaja
(
    kood serial NOT NULL,
    eesnimi character varying(64),
    perekonnanimi character varying(64),
    epost character varying(128),
    telefon character varying(64),
    vau_kood integer, -- uus tulp VAU kasutaja ID jaoks
    CONSTRAINT pk_kasutaja PRIMARY KEY (kood)
)
```

Alustame kõige lihtsamast kasutusjuhust. Loome seose rakenduse kasutaja ja VAU kasutaja vahele käsitsi, lisades väljale **vau_kood** kasutaja ID VAU andmebaasis. Kui see on tehtud, määrame **XVauLoginAction** parameetri **dataMapping** järgmiselt:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'dataMapping'=>array(
                    'model'=>'Kasutaja',
                    'id'=>'vau_kood'
                )
            )  
        )
    );
}
```

Sellise seadistuse puhul õnnestub VAU kaudu rakendusse sisse logida ainult neil VAU kasutajatel, kelle ID leidub tabeli **kasutaja** väljal **vau_kood**.
Rakenduses käivitatakse sessioon, kus:

- **Yii::app()->user->id** on kasutaja id rakenduses (mitte kasutaja id VAU-s),
- **Yii::app()->user->name** on kasutaja täisnimi VAU-s.

Kui me soovime, et kasutaja nimi sessioonis **Yii::app()->user->name** ei ole VAU kasutaja täisnimi, vaid tabeli kasutaja välja eesnimi väärtus, defineerime **authOptions['dataMapping']['name']**.

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'dataMapping'=>array(
                    'model'=>'Kasutaja',
                    'id'=>'vau_kood',
                    'name'=>'eesnimi'
                )
            )
        )
    );
}
```

Kui me soovime, et kasutaja andmed rakenduses oleksid sünkroonitud kasutaja andmetega VAU-s, lülitame sisse **authOptions['dataMapping']['update']** ja kaardistame seosed VAU ja rakenduse andmete vahel **authOptions['dataMapping']['attributes']** abil. Sellise seadistuse korral kirjutatakse rakenduse andmed üle VAU andmetega iga kord, kui kasutaja VAU kaudu rakendusse siseneb:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'dataMapping'=>array(
                    'model'=>'Kasutaja',
                    'id'=>'vau_kood',
                    'name'=>'eesnimi',
                    'update'=>true,
                    'attributes'=>array(
                        'firstname'=>'eesnimi',
                        'lastname'=>'perekonnanimi',
                        'email'=>'epost',
                        'phone'=>'telefon'
                    )
                )
            ) 
        )
    );
}
```

Pane tähele, et kui sa määrad seose ka **roles** jaoks, on väärtuse tüüp **array**. Mõistagi ei saa seda otse andmebaasi salvestada. Küll aga saab selle väärtusega manipuleerida **Kasutaja** klassis vastavalt vajadusele.

Kõik ülaltoodud seadistused lubavad rakendusse siseneda ainult neil VAU kasutajatel, kelle VAU ID on juba rakenduse andmebaasis kirjas. Lülitades sisse **authOptions['dataMapping']['create']** lubame siseneda ka uutel kasutajatel: kui tabelist **kasutaja** ei leita rida, kus **vau_kood** võrdub VAU kasutaja ID-ga, luuakse tabelisse VAU andmete alusel uus rida, uus kasutaja:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'dataMapping'=>array(
                    'model'=>'Kasutaja',
                    'id'=>'vau_kood',
                    'name'=>'eesnimi',
                    'update'=>true,
                    'create'=>true,
                    'attributes'=>array(
                        'firstname'=>'eesnimi',
                        'lastname'=>'perekonnanimi',
                        'email'=>'epost',
                        'phone'=>'telefon'
                    )
                )
            ) 
        )
    );
}
```

Lõpuks on võimalik määrata ka **authOptions['dataMapping']['scenario']** abil stsenaarium VAU andmete salvestamiseks rakenduses:

```
public function actions()
{
    return array(
        'vauLogin'=>array(
            'class'=>'ext.components.vauid.XVauLoginAction',
            'authOptions'=>array(
                'dataMapping'=>array(
                    'model'=>'Kasutaja',
                    'scenario'=>'vau',
                    'id'=>'vau_kood',
                    'name'=>'eesnimi',
                    'update'=>true,
                    'create'=>true,
                    'attributes'=>array(
                        'firstname'=>'eesnimi',
                        'lastname'=>'perekonnanimi',
                        'email'=>'epost',
                        'phone'=>'telefon'
                    )
                )
            )
        )
    );
}
```
