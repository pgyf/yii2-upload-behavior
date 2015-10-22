Upload behavior for Yii 2    [English](https://github.com/liyunfang/yii2-upload-behavior/blob/master/README.md)  
===========================

注意: 参考项目 https://github.com/mongosoft/yii2-upload-behavior

这个扩展会自动上传文件并填充指定的属性值和上传文件的名称。

![Effect picture 1](https://github.com/liyunfang/wr/blob/master/images/UploadBehavior1.png "Effect picture 1")  


安装
------------

安装该扩展的首选方式 [composer](http://getcomposer.org/download/).

编辑运行

```
composer require --prefer-dist liyunfang/yii2-upload-behavior "*"
```

或者在composer.json文件中的require部分添加如下代码:

```json
"liyunfang/yii2-upload-behavior": "*"
```

如何使用
-----

### 上传文件

在你任意的一个有上传文件的model类中:

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
                        'attribute' => 'attachment',//属性名
                        'path' => '@webroot/upload/docs/{category.id}', //保存物理路径
                        'url' => '@web/upload/docs/{category.id}',      //访问地址路径
                        //'multiple' => true, //是否多文件上传
                        //'multipleSeparator' => '|', //文件名分隔符，会将文件保存到同一个字段中  未设置默认 |
                        //'nullValue' => '',
                        //'instanceByName' => false, //是否通过自定义name获取上传文件实例  未设置默认 false
                        //'generateNewName' => true, //function($file){return uniqid().$file->name;}  //是否自动生成文件名 未设置默认 true
                        //'unlinkOnSave' => true,    //保存成功时是否删除原来的文件        未设置默认 true
                        //'deleteTempFile' => true,  //是否上传时删除临时文件              未设置默认 true
                        //'scenarios' => ['insert', 'update'], //在该情景下启用配置        
                    ],
                    [
                        ....
                    ]
                ],
                //'multipleSeparator' => '|', //如果属性中没有该配置则默认读取此配置
                //'nullValue' => '',          //如果属性中没有该配置则默认读取此配置
                //'instanceByName' => false,  //如果属性中没有该配置则默认读取此配置
                //'generateNewName' => true, //function($file){return uniqid().$file->name;} //如果属性中没有该配置则默认读取此配置
                //'unlinkOnSave' => true,     //如果属性中没有该配置则默认读取此配置
                //'deleteTempFile' => true,   //如果属性中没有该配置则默认读取此配置
                'scenarios' => ['insert', 'update'], //如果属性中没有该配置则默认读取此配置
            ],
        ];
    }
}
```

上传单个文件的view例子:

```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'attachment')->fileInput() ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```

上传多个文件的view例子:

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

### 上传图片并且创建缩略图

在你任意上传图片的实体类中:

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
                        //'generateNewName' => true,//function($file){return uniqid().$file->name;}
                        //'unlinkOnSave' => true,
                        //'deleteTempFile' => true,
                        //'scenarios' => ['insert', 'update'],
                        //'createThumbsOnSave' => true,    //是否在保存时创建缩略图 默认true
                        //'createThumbsOnRequest' => true, //是否在请求图片时创建缩略图 默认false
                        // 'thumbs' => [
                        //    'thumb' => ['width' => 400, 'height' => 400,'quality' => 90],
                        //    'preview' => ['width' => 200, 'height' => 200],
                        //    ...       
                        //],
                        //'placeholder' => '@app/modules/user/assets/images/userpic.jpg', //默认图片
                        //'thumbPath' => '@webroot/upload/user/{id}/thumb',  //缩略图保存物理路径
                        //'thumbUrl' => '@web/upload/user/{id}/thumb',   //缩略图访问地址
                    ],
                    [
                        ....
                    ]
                ],
                'scenarios' => ['insert', 'update'],
                //'multipleSeparator' => '|',
                //'nullValue' => '',
                //'instanceByName' => false,
                //'generateNewName' => true,//function($file){return uniqid().$file->name;}
                //'unlinkOnSave' => true,
                //'deleteTempFile' => true,
                //'createThumbsOnSave' => true,       //如果属性中没有该配置则默认读取此配置
                //'createThumbsOnRequest' => false,  //如果属性中没有该配置则默认读取此配置
                // 'thumbs' => [
                //    'thumb' => ['width' => 400, 'height' => 400,'quality' => 90],
                //    'preview' => ['width' => 200, 'height' => 200],
                //],    //如果属性中没有该配置则默认读取此配置
            ],
        ];
    }
}
```

显示单个图片的view例子:

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


显示多个图片的view例子:

```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
      <?php
            $imageUrls = $model->getUploadUrl(image);
            $imageUrls = $model->getThumbUploadUrl('image');
            $imageUrls = $model->getThumbUploadUrl('image', 'preview');
            foreach ($imageUrls as $imgUrl) {
                ...
            }
      
      ?>
<?php ActiveForm::end(); ?>
```

如果你安装了此扩展  https://github.com/kartik-v/yii2-widget-fileinput
配置如下

```php
      $form->field($model, 'image')->widget(FileInput::classname(), [
          'options' => [
                'accept' => 'image/*',
                'multiple' => true,
                'name' => Html::getInputName($model, 'image').'[]',
      ],
      'pluginOptions' => [
          'initialPreview'=> !$model->image ? [] : $model->createPreview(['attribute' => 'image']),
          'overwriteInitial'=> !$model->image ? false: true,
```


注意点
-------

* attributes - 需要上传文件的属性配置
* scenarios - 启用情景
* instanceByName - 从自定义name获取上传文件
* path - 保存物理路径
* url - 访问文件url
* generateNewName - 自动生成文件名
* unlinkOnSave - 如果设置为true在保存成功后将会删除原来的文件
* unlinkOnDelete - 如果设置为true 在删除数据后将删除文件

