Dokobit Documents Gateway Extensions for Yii PHP framework
=========================================================

Allows to integrate Dokobit Documents Gateway into PHP application based on Yii 1.1 framework

_Note that this extension does not support all requests available in Dokobit Documents Gateway API. If you need to consult the full API documentation, please refer to https://gateway-sandbox.dokobit.com/api/doc_

Class files
-----------

Dokobit Documents Gateway Extensions for Yii PHP framework consists of following class files:

- **XDokobitDocuments** is an application component that enables to request Dokobit Documents Gateway API.

- **XDokobitDownloadAction** downloads signed file from Dokobit server and passes downloaded file data to callback function.

- **XDokobitIframeWidget** embeds Dokobit Documents Gateway iframe and javascript that allow to sign documents without leaving website.

Configuration
-------------

The following shows how to set up all these classes to integrate Dokobit Documents Gateway into Yii application.

**Application component**

First configure dokobit component.

```php
'components'=>array(
    'dokobitDocuments'=>array(
        'class'=>'ext.components.dokobit.documents.XDokobitDocuments',
        'apiAccessToken'=>'testgw_AabBcdEFgGhIJjKKlmOPrstuv',
        'baseUrl'=>'https://gateway-sandbox.dokobit.com'
    )
)
```

**Signing action**

Then code action that initializes signing.

```php
public function actionSign()
{
    $application=$this->loadModel($_GET['id']);

    // upload files to dokobit server
    $uploadedFiles=Yii::app()->dokobitDocuments->uploadFiles($application->filePaths);

    if($uploadedFiles)
    {
        // set session user as signer
        $signer=array(
            'id'=>Yii::app()->user->id,
            'name'=>Yii::app()->user->firstname,
            'surname'=> Yii::app()->user->lastname
        );

        // create signing
        $signingResponse=Yii::app()->dokobitDocuments->createSigning(array(
            'type'=>'asice',
            'name'=>$application->filename,
            'files'=>$uploadedFiles,
            'signers'=>array($signer),
            'language'=>Yii::app()->language
        ));

        if($signingResponse['status']=='ok')
        {
            // get signing token and url
            $signingToken=$signingResponse['token'];
            $signerAccessToken=$signingResponse['signers'][Yii::app()->user->id];
            $signingUrl=Yii::app()->dokobitDocuments->getSigningUrl($signingToken, $signerAccessToken);

            // set callback url to dokobit download action that we will define later
            $callbackUrl=>$this->createUrl('dokobitDownload');

            // set callback token if you need to pass some data to callback functions after download
            $callbackToken=>$this->generateToken();

            // render sign action
            $this->render('sign', array(
                'signingUrl'=>$signingUrl,
                'signingToken'=>$signingToken,
                'callbackUrl'=>$callbackUrl,
                'callbackToken'=>$callbackToken
            ));
        }
    }
}
```

**Signing widget**

Inside 'sign' view embed widget.

```php
$this->widget('ext.components.dokobit.documents.XDokobitIframeWidget', array(
    'signingUrl'=>$signingUrl,
    'signingToken'=>$signingToken,
    'callbackUrl'=>$callbackUrl,
    'callbackToken'=>$callbackToken
));
```

This widget creates
- iframe that displays Dokobit Documents Gateway page where application user can sign document;
- javascript that posts ajax request to download action after successful signing within iframe.

**Download action**

Define download action that will be requested after successful signing.

```php
public function actions()
{
    return array(
        'dokobitDownload'=>array(
            'class'=>'ext.components.dokobit.documents.XDokobitDownloadAction',
            'successCallback'=>'handleDownloadSuccess',
            'failureCallback'=>'handleDownloadFailure'
        )
    );
}
```

This action downloads signed document file from Dokobit Documents Gateway server and passes file contents to callback function. Every application must handle download success and failure according to it's specific requirements, but in most cases success callback function saves file contents to filesystem or database, failure callback function displays error to user.

Following is an example of success callback function.

```php
public function handleDownloadSuccess($callbackToken, $data)
{
    // validate callback token
    if(!$this->validateToken($callbackToken))
        throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');

    // find application
    $application=Application::model()->findbyPk($this->getIdFromToken($callbackToken));
    if($application===null)
        throw new CHttpException(404,'The requested page does not exist.');

    // save file
    if(file_put_contents($application->destination, $data))
    {
        // insert filename to database
        $file=new ApplicationFile;
        $file->application_id=$application->id;
        $file->name=$application->filename;

        if($file->save())
            echo 'Application successfully submitted!';
        else
            echo 'Signing was successful, but saving file info into database failed!';
    }
    else
        echo 'Signing was successful, but saving file contents failed!';
}
```