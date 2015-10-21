Upload behavior for Yii 2    [中文](https://github.com/liyunfang/yii2-upload-behavior/blob/master/README-ZH-CN.md)  
===========================


Note: Reference project https://github.com/mongosoft/yii2-upload-behavior

This behavior automatically uploads file and fills the specified attribute with a value of the name of the uploaded file.


![Effect picture 1](https://github.com/liyunfang/wr/blob/master/images/UploadBehavior1.png "Effect picture 1")  


Installation
------------

The preferred way to install this extension via [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist liyunfang/yii2-upload-behavior "*"
```

or add this code line to the `require` section of your `composer.json` file:

```json
"liyunfang/yii2-upload-behavior": "*"
```

Usage
-----

### Upload file

Attach the behavior in your model:

```php
class Document extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['attachment', 'file','maxFiles' => 2, 'extensions' => 'doc, docx, pdf', 'on' => ['insert', 'update']],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), [ 'id' => 'id_category' ]);
    }

    /**
     * @inheritdoc
     */
    function behaviors()
    {
        return [
            [
                'class' => UploadBehavior::className(),
                'attributes' => [
                    [
                        'attribute' => 'attachment',
                        'path' => '@webroot/upload/docs/{category.id}',
                        'url' => '@web/upload/docs/{category.id}',
                        //'multiple' => true,
                        //'multipleSeparator' => '|',
                        //'nullValue' => '',
                        //'instanceByName' => false,
                        //'generateNewName' => true,
                        //'unlinkOnSave' => true,
                        //'deleteTempFile' => true,
                        //'scenarios' => ['insert', 'update'],
                    ],
                    [
                        ....
                    ]
                ],
                //'multipleSeparator' => '|',
                //'nullValue' => '',
                //'instanceByName' => false,
                //'generateNewName' => true,
                //'unlinkOnSave' => true,
                //'deleteTempFile' => true,
                'scenarios' => ['insert', 'update'],
            ],
        ];
    }
}
```

Example view single file:

```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'attachment')->fileInput() ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```

Example view multiple file:

```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'attachment')->fileInput([
        'name' => Html::getInputName($model, 'attachment').'[]',
        'multiple' => true,
    ]) ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```

### Upload image and create thumbnails

Attach the behavior in your model:

```php
class User extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['image', 'image', 'extensions' => 'jpg, jpeg, gif, png', 'on' => ['insert', 'update']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => \liyunfang\file\UploadImageBehavior::className(),
                'attributes' => [
                    [
                        'attribute' => 'image',
                        'path' => '@webroot/upload/user/{id}',
                        'url' => '@web/upload/user/{id}',
                        //'multiple' => true,
                        //'multipleSeparator' => '|',
                        //'nullValue' => '',
                        //'instanceByName' => false,
                        //'generateNewName' => true,
                        //'unlinkOnSave' => true,
                        //'deleteTempFile' => true,
                        //'scenarios' => ['insert', 'update'],
                        //'createThumbsOnSave' => true,
                        //'createThumbsOnRequest' => true,
                        // 'thumbs' => [
                        //    'thumb' => ['width' => 400, 'height' => 400,'quality' => 90],
                        //    'preview' => ['width' => 200, 'height' => 200],
                        //],
                        //'placeholder' => '@app/modules/user/assets/images/userpic.jpg',
                        //'thumbPath' => '@webroot/upload/user/{id}/thumb',
                        //'thumbUrl' => '@web/upload/user/{id}/thumb',
                    ],
                    [
                        ....
                    ]
                ],
                'scenarios' => ['insert', 'update'],
                //'multipleSeparator' => '|',
                //'nullValue' => '',
                //'instanceByName' => false,
                //'generateNewName' => true,
                //'unlinkOnSave' => true,
                //'deleteTempFile' => true,
                //'createThumbsOnSave' => true,
                //'createThumbsOnRequest' => false,
                // 'thumbs' => [
                //    'thumb' => ['width' => 400, 'height' => 400,'quality' => 90],
                //    'preview' => ['width' => 200, 'height' => 200],
                //],
            ],
        ];
    }
}
```

Example view  single file:

```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <div class="form-group">
        <div class="row">
            <div class="col-lg-6">
                <!-- Original image -->
                <?= Html::img($model->getUploadUrl('image'), ['class' => 'img-thumbnail']) ?>
            </div>
            <div class="col-lg-4">
                <!-- Thumb 1 (thumb profile) -->
                <?= Html::img($model->getThumbUploadUrl('image'), ['class' => 'img-thumbnail']) ?>
            </div>
            <div class="col-lg-2">
                <!-- Thumb 2 (preview profile) -->
                <?= Html::img($model->getThumbUploadUrl('image', 'preview'), ['class' => 'img-thumbnail']) ?>
            </div>
        </div>
    </div>
    <?= $form->field($model, 'image')->fileInput(['accept' => 'image/*']) ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```


Example view  multiple file:

```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
      <?php
            $imageUrls = $model->getUploadUrl('image');
            //$imageUrls = $model->getThumbUploadUrl('image');
            //$imageUrls = $model->getThumbUploadUrl('image', 'preview');
            foreach ($imageUrls as $imgUrl) {
                ...
            }
      
      ?>
<?php ActiveForm::end(); ?>
```

if you install  https://github.com/kartik-v/yii2-widget-fileinput

```php
      $form->field($model, 'image')->widget(FileInput::classname(), [
          'options' => [
                'accept' => 'image/*',
                'multiple' => true,
                'name' => Html::getInputName($model, 'image').'[]',
      ],
      'pluginOptions' => [
          'initialPreview'=> !$model->image ? [] : $model->createPreview(['attribute' => 'image', 'profile' => 'preview']),
          'overwriteInitial'=> !$model->image ? false: true,
```


Behavior Options
-------

* attributes - The attributes which Hold collection for the attachment
* scenarios - The scenarios in which the behavior will be triggered
* instanceByName - Getting file instance by name, If you use UploadBehavior in `RESTfull` application and you do not need a prefix of the model name, set the property `instanceByName = false`, default value is `false`
* path - the base path or path alias to the directory in which to save files.
* url - the base URL or path alias for this file
* generateNewName - Set true or anonymous function takes the old filename and returns a new name, default value is `true`
* unlinkOnSave - If `true` current attribute file will be deleted, default value is `true`
* unlinkOnDelete - If `true` current attribute file will be deleted after model deletion.

### Attention!

It is prefered to use immutable placeholder in `url` and `path` options, other words try don't use related attributes that can be changed. There's bad practice. For example:

```
class Track extends ActiveRecord
{
    public function getArtist()
    {
        return $this->hasOne(Artist::className(), [ 'id' => 'id_artist' ]);
    }

    public function behaviors()
    {
        return [
            [
                'class' => UploadBehavior::className(),
                'attribute' => 'image',
                'scenarios' => ['default'],
                'path' => '@webroot/uploads/{artist.slug}',
                'url' => '@web/uploads/{artist.slug}',
            ],
        ];
    }
}
```

If related model attribute `slug` will change, you must change folders' names too, otherwise behavior will works not correctly. 
