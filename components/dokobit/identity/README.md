Dokobit Identity Gateway Extensions for Yii PHP framework
=========================================================

Allows to integrate Dokobit Identity Gateway into PHP application based on Yii 1.1 framework

Class files
-----------

Dokobit Identity Gateway Extensions for Yii PHP framework consists of following class files:

- **XDokobitIdentity** is an application component that enables to request Dokobit Identity Gateway API.

- **XDokobitUserIdentity** authorizes application user on the data of authenticated user returned by Dokobit Identity Gateway API.

- **XDokobitLoginAction** logs user into application on the data of authenticated user returned by Dokobit Identity Gateway API.

- **XDokobitLoginWidget** embeds Dokobit Identity Gateway UI that allows to authenticate user without leaving website.

Configuration
-------------

The following shows easy steps how to set up all these classes to integrate Dokobit Identity Gateway.

**Application component**

First configure dokobit component.

```php
'components'=>array(
    'dokobitIdentity'=> array(
        'class'=>'ext.components.dokobit.identity.XDokobitIdentity',
        'apiAccessToken'=>'testid_AabBcdEFgGhIJjKKlmOPrstuv',
        'baseUrl'=>'https://id-sandbox.dokobit.com'
    )
)
```

**Controller action**

Then define dokobit login action in controller. After successful authentication Dokobit Identity Gateway will redirect user to this action. This action authorizes and logs user into application using the data of authenticated user returned by Dokobit Identity Gateway API.

```php
public function actions()
{
    return array(
        'dokobitLogin'=>array(
            'class'=>'ext.components.dokobit.identity.XDokobitLoginAction',
            'successUrl'=>$this->createUrl('index'),
            'failureUrl'=>$this->createUrl('login')
        )
    );
}
```

Note that in the above example after successful authentication only user session will be started in the application with Yii::app()->user->id being set to code@country_code. This minimalist configuration is useful only for limited cases, where there are no user data stored in application database. In most cases you need define authOption to authorize athenticated user against database.

Example 2:

```php
public function actions()
{
    return array(
        'dokobitLogin'=>array(
            'class'=>'ext.components.dokobit.identity.XDokobitLoginAction',
            'successUrl'=>$this->createUrl('index'),
            'failureUrl'=>$this->createUrl('login')
            'authOptions'=>array(
                'modelName'=>'User',
                'codeAttributeName'=>'user_id_number',
                'countryCodeAttributeName'=>'user_country_code',
                'usernameAttributeName'=>'username',
            )
        )
    );
}
```

In the above example user is authorized against application database. "User" is the name of model that reads and writes data form and to user table. Authenticated user is authorized to log into application only if there is a row in user table where user_id_number=code and user_country_code=country_code. Yii::app()->user->id will be set to primary key value and Yii::app()->user->name will be assigned the value of username column/attribute.

However, there are cases when new user must be created in the application from the data of authenticated user returned by Dokobit Identity Gateway API. In these cases 'enableCreate' should be set to true.

Example 3:

```php
public function actions()
{
    return array(
        'dokobitLogin'=>array(
            'class'=>'ext.components.dokobit.identity.XDokobitLoginAction',
            'successUrl'=>$this->createUrl('index'),
            'failureUrl'=>$this->createUrl('login')
            'authOptions'=>array(
                'modelName'=>'User',
                'codeAttributeName'=>'user_id_number',
                'countryCodeAttributeName'=>'user_country_code',
                'usernameAttributeName'=>'username',
                'enableCreate'=>true,
                'syncAttributes'=>array(
                    'name'=>'firstname',
                    'surname'=>'lastname',
                    'phone'=>'phone'
                )
            )
        )
    );
}
```

In the above example, if there is no row in the aplication user table where user_id_number=code and user_country_code=country_code, new user is inserted based on the data of authenticated user returned by Dokobit Identity Gateway API and according to the data mapping given in 'authOptions'.

But you may need to keep application data in sync with authenticated user data that may change in some cases (for example name change in case of marriage).

Example 4:

```php
public function actions()
{
    return array(
        'dokobitLogin'=>array(
            'class'=>'ext.components.dokobit.identity.XDokobitLoginAction',
            'successUrl'=>$this->createUrl('index'),
            'failureUrl'=>$this->createUrl('login')
            'authOptions'=>array(
                'modelName'=>'User',
                'codeAttributeName'=>'user_id_number',
                'countryCodeAttributeName'=>'user_country_code',
                'usernameAttributeName'=>'username',
                'enableCreate'=>true,
                'enableUpdate'=>true,
                'syncAttributes'=>array(
                    'name'=>'firstname',
                    'surname'=>'lastname',
                    'phone'=>'phone'
                )
            )
        )
    );
}
```

In the above example, every time user is authorized and logged into application, application database (user table) is updated with the data of authenticated user returned by Dokobit Identity Gateway API and according to the data mapping given in 'authOptions'.

**Widget**

Now define controller action that starts Dokobit Identity Gateway session and passes session token to the view that embeds XDokobitLoginWidget.

```php
public function actionLogin()
{
    // create dokobit session
    $dokobitSessionData=Yii::app()->dokobitIdentity->createSession(array(
        'return_url'=>$this->createAbsoluteUrl('dokobitLogin')
    ));

    // decode data
    $dokobitSessionData=CJSON::decode($dokobitSessionData);

    // check data, get token
    $dokobitSessionToken=null;
    if($dokobitSessionData['status']=='ok')
        $dokobitSessionToken=$dokobitSessionData['session_token'];
    else
        Yii::app()->user->setFlash('failure', Yii::t('ui', 'Mobile ID, Smart Card and Smart-ID authentication methods are unavailable!'));

    $this->render('login', array(
        'dokobitSessionToken'=>$dokobitSessionToken
    ));
}
```

Inside this view call widget that displays Dokobit Identity Gateway UI.

```php
$this->widget('ext.components.dokobit.identity.XDokobitLoginWidget', array(
    'sessionToken'=>$sessionToken,
    'options'=>array(
        'locale'=>'et',
        'primaryColor'=>'#0088cc'
    )
));
```
