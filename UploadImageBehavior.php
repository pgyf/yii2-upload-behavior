<?php

namespace liyunfang\file;

use Imagine\Image\ManipulatorInterface;
use Yii;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\imagine\Image;
use yii\helpers\Html;

/**
 * UploadImageBehavior automatically uploads image, creates thumbnails and fills
 * the specified attribute with a value of the name of the uploaded image.
 *
 * To use UploadImageBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 * use liyunfang\file\UploadImageBehavior;
 *
 * function behaviors()
 * {
 *     return [
 *         [
 *             'class' => UploadBehavior::className(),
 *             'attributes' => [  
 *                                [ 
 *                                    'attribute' => 'file1',
 *                                    'path' => '@webroot/upload/images/file1/{id}',
 *                                    'url' => '@web/upload/images/file1/{id}',
 *                                    //'scenarios' => ['insert', 'update'],
 *                                    //'multiple' => true,
 *                                    //'multipleSeparator' => '|',
 *                                    //'nullValue' => '',
 *                                    //'instanceByName' => false;
 *                                    //'generateNewName' => true,
 *                                    //'unlinkOnSave' => true,
 *                                    //'deleteTempFile' => true,
 *                                    'createThumbsOnSave' => true,
 *                                    'createThumbsOnRequest' => true,
 *                                    'thumbs' => [
 *                                            'thumb' => ['width' => 400, 'height' => 400,'quality' => 90],
 *                                            'preview' => ['width' => 200, 'height' => 200],
 *                                     ]
 *                                    'placeholder' => '@app/modules/user/assets/images/userpic.jpg',
 *                                    //'thumbPath' => '@webroot/upload/images/file1/{id}/thumb',
 *                                    //'thumbUrl' => '@web/upload/images/file1/{id}/thumb',
 *                               ] 
 *                               [
 *                                ...
 *                               ]
 *                           ] 
 *             'scenarios' => ['insert', 'update'], 
 *             //'multipleSeparator' => '|',
 *             //'nullValue' => '',
 *             //'instanceByName' => false;
 *             //'generateNewName' => true,
 *             //'unlinkOnSave' => true,
 *             //'deleteTempFile' => true,
 *             //'createThumbsOnSave' => true,
 *             //'createThumbsOnRequest' => false,
 *             //'thumbs' => [
 *             //      'thumb' => ['width' => 400, 'height' => 400,'quality' => 90],
 *             //      'preview' => ['width' => 200, 'height' => 200],
 *             //   ]
 *         ],
 *     ];
 * }
 * ```
 *
 * @author liyunfang <381296986@qq.com>
 */
class UploadImageBehavior extends UploadBehavior
{

    /**
     * @var boolean
     */
    public $createThumbsOnSave = true;
    /**
     * @var boolean
     */
    public $createThumbsOnRequest = false;
  

    /**
     * @inheritdoc
     */
    protected function afterUpload()
    {
        parent::afterUpload();
        $this->createThumbs();
    }

   /**
     * @throws \yii\base\InvalidParamException
     */
    protected function createThumbs($createAction = 'createThumbsOnSave')
    {
        foreach ($this->attributes as $attribute => $attributeConfig) {
            $createThumbsOnSave =  $this->getAttributeConfig($attributeConfig, 'createThumbsOnSave');
            $thumbs =  $this->getAttributeConfig($attributeConfig, 'thumbs');
            if($createThumbsOnSave && $thumbs){
                foreach ($thumbs as $profile => $config) {
                    $thumbPaths = $this->getOriginalThumbsPath($attribute, $profile);
                    if ($thumbPaths !== null) {
                        foreach ($thumbPaths as $path => $thumbPath) {
                            if (!FileHelper::createDirectory(dirname($thumbPath))) {
                                throw new InvalidParamException("Directory specified in 'thumbPath' attribute doesn't exist or cannot be created.");
                            }
                            if (is_file($path) && !is_file($thumbPath)) {
                                $this->generateImageThumb($config, $path, $thumbPath);
                            }
                        }
                    }
                }
            }
        }
    }
    
    
    
    /**
     * Get the relationship between the original path and path thumbnail
     * @param string $attribute
     * @param string $profile
     * @param boolean $old
     * @return array|null
     */
    protected function getOriginalThumbsPath($attribute, $profile = 'thumb', $old = false){
        $fileName = $this->resolveFileName($attribute,$old);
        if($fileName){
            $pathList = [];
            if(is_array($fileName)){
                foreach ($fileName as $k => $fn) {
                    $path = $this->getSingleUploadPath($attribute,$fn);
                    if($path){
                         $pathList[$path] = $this->getSingleThumbUploadPath($attribute, $fn, $profile);;
                    }
                }
            }
            else{
                $path = $this->getSingleUploadPath($attribute,$fileName);
                if($path){
                    $pathList[$path] = $this->getSingleThumbUploadPath($attribute,$fileName, $profile);
                }
            }
            return $pathList;
        }
        return  null;
    }


   /**
     * Get the relationship between the original URL and URL thumbnail
     * @param string $attribute
     * @param string $profile
     * @param boolean $old
     * @return array|null
     */
    protected function getOriginalThumbsUrl($attribute, $profile = 'thumb', $old = false){
        $fileName = $this->resolveFileName($attribute,$old);
        if($fileName){
            $urlList = [];
            if(is_array($fileName)){
                foreach ($fileName as $k => $fn) {
                    $url = $this->getSingleUploadUrl($attribute,$fn);
                    if($url){
                        $urlList[$url] = $this->getSingleThumbUploadUrl($attribute, $fn, $profile);
                    }
                }
            }
            else{
                $url = $this->getSingleUploadUrl($attribute,$fileName);
                if($url){
                    $pathList[$url] = $this->getSingleThumbUploadUrl($attribute,$fileName, $profile);
                }
            }
            return $pathList;
        }
        return  null;
    }
    
   /**
     * Get the relationship between the original path and thumbnail URL
     * @param string $attribute
     * @param string $profile
     * @param boolean $old
     * @return array|null
     */
    protected function getOriginalPathThumbsUrl($attribute, $profile = 'thumb', $old = false){
        $fileName = $this->resolveFileName($attribute,$old);
        if($fileName){
            $pathList = [];
            if(is_array($fileName)){
                foreach ($fileName as $k => $fn) {
                    $path = $this->getSingleUploadPath($attribute,$fn);
                    if($path){
                         $pathList[$path] = $this->getSingleThumbUploadUrl($attribute,$fn, $profile);
                    }
                }
            }
            else{
                 $path = $this->getSingleUploadPath($attribute, $fileName);
                 if($path){
                     $pathList[$path] = $this->getSingleThumbUploadUrl($attribute,$fileName, $profile);
                 }
            }
            return $pathList;
        }
        return  null;
    }
    
    
    /**
     * Get single file path
     * @param string $fileName
     * @return string|null
     */
    public function getSingleUploadPath($attribute, $fileName)
    {
        if($fileName){
            $path = $this->getAttributeConfig($attribute, 'path');
            $path = $this->resolvePath($path);
            return Yii::getAlias($path . '/' . $fileName);
        }
        return null;
    }
    
    /**
     * Get single thumbnail file path
     * @param string $fileName
     * @param string $profile
     * @return string|null
     */
    public function getSingleThumbUploadPath($attribute,$fileName, $profile = 'thumb')
    {
        if($fileName){
            $thumbPath = $this->getAttributeConfig($attribute, 'thumbPath');
            if(!$thumbPath){
                $thumbPath = $this->getAttributeConfig($attribute, 'path');
            }
            $path = $this->resolvePath($thumbPath);
            $fileName = $this->getThumbFileName($fileName, $profile);
            return Yii::getAlias($path . '/' . $fileName);
        }
        return  null;
    }
    
    /**
     * Get single file URL
     * @param string $fileName
     * @return string
     */
    public function getSingleUploadUrl($attribute, $fileName)
    {
        if($fileName){
            $url = $this->getAttributeConfig($attribute, 'url');
            $url = $this->resolvePath($url);
            return Yii::getAlias($url . '/' . $fileName);
        }
        return  '';
    }
    
    /**
     * Get a single thumbnail URL
     * @param string $fileName
     * @param string $profile
     * @return string
     */
    public function getSingleThumbUploadUrl($attribute,$fileName, $profile = 'thumb')
    {
        if($fileName){
            $thumbUrl = $this->getAttributeConfig($attribute, 'thumbUrl');
            if(!$thumbUrl){
                $thumbUrl = $this->getAttributeConfig($attribute, 'url');
            }
            $url = $this->resolvePath($thumbUrl);
            $fileName = $this->getThumbFileName($fileName, $profile);
            return Yii::getAlias($url . '/' . $fileName);
        }
        return  '';
    }
    

    /**
     * Get the URL collection of the thumbnail
     * @param string $attribute
     * @param string $profile
     * @return array|null
     */
    public function getThumbUploadUrl($attribute, $profile = 'thumb')
    {
        $fileName = $this->getAttributeValue($attribute, true);
        $createThumbsOnRequest = $this->getAttributeConfig($attribute, 'createThumbsOnRequest');
        if ($fileName && $createThumbsOnRequest) {
            $this->createThumbs('createThumbsOnRequest');
        }
        $multiple = $this->getAttributeConfig($attribute, 'multiple');
        $placeholder = $this->getAttributeConfig($attribute, 'placeholder');
        $thumbUploadUrl = [];
        $thumbUrls = $this->getOriginalPathThumbsUrl($attribute, $profile ,true);
        if($thumbUrls){
            foreach ($thumbUrls as $path => $thumbUrl) {
                if (is_file($path)) {
                    $thumbUploadUrl[] = $thumbUrl;
                } elseif ($placeholder) {
                    $thumbUploadUrl[] = $this->getPlaceholderUrl($attribute,$profile);
                }
            }
        }
        if($multiple){
            return $thumbUploadUrl ? $thumbUploadUrl : null;
        }
        else{
            return $thumbUploadUrl ? $thumbUploadUrl[0] : null;
        }
    }

    /**
     * Get URL Placeholder
     * @param string $attribute
     * @param string $profile
     * @return string
     */
    protected function getPlaceholderUrl($attribute,$profile)
    {
        $placeholder =  $this->getAttributeConfig($attribute, 'placeholder');
        $thumbs = $this->getAttributeConfig($attribute, 'thumbs');
        if($placeholder){
            list ($path, $url) = Yii::$app->assetManager->publish($placeholder);
            $filename = basename($path);
            $thumb = $this->getThumbFileName($filename, $profile);
            $thumbPath = dirname($path) . DIRECTORY_SEPARATOR . $thumb;
            $thumbUrl = dirname($url) . '/' . $thumb;
            if (!is_file($thumbPath)) {
                $this->generateImageThumb($thumbs[$profile], $path, $thumbPath);
            }
            return $thumbUrl;
        }
        return '';
    }

    
    /**
     * 
     * @param string $attribute
     * @param string $singleFileName
     */
    public function afterUnlink($attribute, $singleFileName){
        $thumbs = $this->getAttributeConfig($attribute, 'thumbs');
        if($singleFileName && $thumbs){
            foreach ($thumbs as $profile => $config) {
                $path = $this->getSingleThumbUploadPath($attribute, $singleFileName , $profile);
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }
    

    /**
     * @param $filename
     * @param string $profile
     * @return string
     */
    protected function getThumbFileName($filename, $profile = 'thumb')
    {
        return $profile . '-' . $filename;
    }

    /**
     * @param $config
     * @param $path
     * @param $thumbPath
     */
    protected function generateImageThumb($config, $path, $thumbPath)
    {
        $width = ArrayHelper::getValue($config, 'width');
        $height = ArrayHelper::getValue($config, 'height');
        $quality = ArrayHelper::getValue($config, 'quality', 100);
        $mode = ArrayHelper::getValue($config, 'mode', ManipulatorInterface::THUMBNAIL_INSET);

        if (!$width || !$height) {
            $image = Image::getImagine()->open($path);
            $ratio = $image->getSize()->getWidth() / $image->getSize()->getHeight();
            if ($width) {
                $height = ceil($width / $ratio);
            } else {
                $width = ceil($height * $ratio);
            }
        }

        // Fix error "PHP GD Allowed memory size exhausted".
        ini_set('memory_limit', '512M');
        Image::thumbnail($path, $width, $height, $mode)->save($thumbPath, ['quality' => $quality]);
    }
    
    /**
     * Preview image
     * @param type $option
     * @return type
     */
    public function createPreview($option){
        $model = $this->owner;
        $attribute = $option['attribute'];
        $attrLabel = $model->getAttributeLabel($attribute);
        //'style' => 'max-width:160px;max-height:160px;'
        $imgOptions = ArrayHelper::getValue($option, 'imgOptions', ['class'=>'file-preview-image']);
        $imgOptions = array_merge(['alt'=> $attrLabel, 'title'=> $attrLabel],$imgOptions);
        $profile = ArrayHelper::getValue($option, 'profile','preview');
        if($profile){
            $imgUrl = $this->getThumbUploadUrl($attribute,$profile);
        }
        else{
            $imgUrl = $this->getUploadUrl($attribute);
        }
        $previewList = [];
        if($imgUrl){
            if(is_array($imgUrl)){
                foreach ($imgUrl as $imgSrc) {
                    $previewList[] = Html::img($imgSrc ,$imgOptions);
                }
            }
            else{
                $previewList[] = Html::img($imgUrl ,$imgOptions);
            }
        }
        return $previewList;
    }
    
}
