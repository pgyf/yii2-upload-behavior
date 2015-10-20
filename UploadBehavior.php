<?php

namespace liyunfang\file;


use Closure;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * UploadBehavior automatically uploads file and fills the specified attribute
 * with a value of the name of the uploaded file.
 *
 * To use UploadBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 * use liyunfang\file\UploadBehavior;
 *
 * function behaviors()
 * {
 *     return [
 *         [
 *             'class' => UploadBehavior::className(),
 *             'attributes' => [  
 *                                [ 
 *                                    'attribute' => 'file1',
 *                                    'path' => '@webroot/upload/file/file1/{id}',
 *                                    'url' => '@web/upload/file/file1/{id}',
 *                                    //'scenarios' => ['insert', 'update'],
 *                                    //'multiple' => true,
 *                                    //'multipleSeparator' => '|',
 *                                    //'nullValue' => '',
 *                                    //'instanceByName' => false;
 *                                    //'generateNewName' => true,
 *                                    //'unlinkOnSave' => true,
 *                                    //'deleteTempFile' => true,
 *                               ] 
 *                               [
 *                                 ...
 *                               ]
 *                           ] 
 *             'scenarios' => ['insert', 'update'], 
 *             //'multipleSeparator' => '|',
 *             //'nullValue' => '',
 *             //'instanceByName' => false;
 *             //'generateNewName' => true,
 *             //'unlinkOnSave' => true,
 *             //'deleteTempFile' => true,
 *         ],
 *     ];
 * }
 * ```
 *
 * @author liyunfang <381296986@qq.com>
 */
class UploadBehavior extends \yii\base\Behavior
{
    
    /**
     * @event Event an event that is triggered after a file is uploaded.
     */
    const EVENT_AFTER_UPLOAD = 'afterUpload';

    /**
     * @var string the attribute which holds the attachment.
     */
    public $attributes;
    /**
     * @var array the scenarios in which the behavior will be triggered
     *       Scenarios configuration for each attribute
     */
    public $scenarios = [];
    /**
     * @var bool Getting file instance by name
     */
    public $instanceByName = false;
    /**
     * @var boolean|callable generate a new unique name for the file
     * set true or anonymous function takes the old filename and returns a new name.
     * @see self::generateFileName()
     */
    public $generateNewName = true;
    /**
     * @var boolean If `true` current attribute file will be deleted
     */
    public $unlinkOnSave = true;
    /**
     * @var boolean If `true` current attribute file will be deleted after model deletion.
     */
    public $unlinkOnDelete = true;
    /**
     * @var boolean $deleteTempFile whether to delete the temporary file after saving.
     */
    public $deleteTempFile = true;

   /**
     * multiple File name separator
     */
    public $multipleSeparator = '|';
    
    /**
     * When the value of null
     */
    public $nullValue = '';
    

    /**
     * @var UploadedFile the uploaded file instance.
     */
    protected $files;
    /**
     * Delete fileName
     */
    protected $deleteFileName = [];


    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        if ($this->attributes === null) {
            throw new InvalidConfigException('The "attribute" property must be set.');
        }
        if(!is_array($this->attributes)){
            throw new InvalidConfigException('"attribute" expects the array');
        }
        $this->configInit();
    }
    
    /**
     * Initialization configuration
     * @throws InvalidConfigException
     */
    private function configInit(){
        $attributes = [];
        foreach ($this->attributes as $k => $v) {
            $path = ArrayHelper::getValue($v, 'path');
            if ($path === null) {
                throw new InvalidConfigException('The "path" property must be set.');
            }
            $url = ArrayHelper::getValue($v, 'url');
            if ($url === null) {
                throw new InvalidConfigException('The "url" property must be set.');
            }
            $attribute = ArrayHelper::remove($v, 'attribute');
            if($attribute){
                $attributes[$attribute] = $v;
            }
            else{
                throw new InvalidConfigException('Array must contain the key : attribute .');
            }
        }
        $this->attributes = $attributes;
    }

    
    
    
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            BaseActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }
    
    
    /**
     * Gets the attribute configuration, does not exist then gets the global configuration
     * @param string $attribute
     * @param string $key
     * @return mixed
     */
    protected function getAttributeConfig($attribute , $key){
        
        if(is_array($attribute)){
            $attributeConfig = $attribute;
        }
        else{
            $attributeConfig = $this->attributes[$attribute];
        }
        if($key){
            if(isset($attributeConfig[$key])){
                return $attributeConfig[$key];
            }
            else{
                if(property_exists(static::className(), $key)){
                    return $this->$key;
                }
            }
        }
        return null;
    }

    /**
     * Whether there has current scenario
     * @param string $attribute
     * @return boolean
     */
    protected function hasScenario($attribute){
        if(is_array($attribute)){
            $attributeConfig = $attribute;
        }
        else{
            $attributeConfig = $this->attributes[$attribute];
        }
        $model = $this->owner;
        $scenario = $this->getAttributeConfig($attributeConfig, 'scenarios');
        if(in_array($model->scenario, $scenario)){
            return true;
        }
        return false;
    }


    /**
     * Returns attribute value
     * @param string $attribute
     * @param boolean $old
     * @return string
     */
    protected function getAttributeValue($attribute, $old = false)
    {
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        return ($old === true) ? $model->getOldAttribute($attribute) : $model->$attribute;
    }

 
 
    /**
     * This method is invoked before validation starts.
     */
    public function beforeValidate()
    {
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        foreach ($this->attributes as $attribute => $attributeConfig) {
            if($this->hasScenario($attributeConfig)){
                $file = $this->getAttributeValue($attribute);
                if (!$this->validateFile($file)) {
                    $file = $this->getUploadInstance($attribute);
                }
                if(!isset($this->files[$attribute]) && $this->validateFile($file)){
                    //$model->setAttribute($attribute, $file);
                    $this->files[$attribute] = $file;
                }
                else{
                    if($model->getAttribute($attribute) == null){
                        $nullValue = $this->getAttributeConfig($attribute, 'nullValue');
                        $model->setAttribute($attribute, $nullValue);
                    }
                }
            }
        }
    }
    

    /**
     * This method is called at the beginning of inserting or updating a record.
     */
    public function beforeSave()
    {
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        foreach ($this->attributes as $attribute => $attributeConfig) {
            if($this->hasScenario($attributeConfig)){
                if(isset($this->files[$attribute]) && $this->validateFile($this->files[$attribute])){
                    if (!$model->getIsNewRecord() && $model->isAttributeChanged($attribute)) {
                        if ($this->getAttributeConfig($attributeConfig,'unlinkOnSave') === true) {
                            $this->deleteFileName[$attribute] = $this->resolveFileName($attribute, true);
                        }
                    }
                }
                else{
                    // Protect attribute
                    unset($model->$attribute);
                }
            }
            else{
                if(!$model->getIsNewRecord()  && $model->isAttributeChanged($attribute)){
                    if($this->getAttributeConfig($attributeConfig,'unlinkOnSave')){
                        $this->deleteFileName[$attribute] = $this->resolveFileName($attribute, true);
                    }
                }
            }
        }
        $this->fileSave();
    }
    
    
    /**
     * This method is called at the end of inserting or updating a record.
     * @throws \yii\base\InvalidParamException
     */
    public function afterSave(){
        $this->unlinkOnSave();
    }
    
    /**
     * Save file
     * @throws InvalidParamException
     */
    public function fileSave()
    {
        if($this->files){
            foreach ($this->files as $attribute => $file) {
                if($this->validateFile($file)){
                    $basePath = $this->getUploaddirectory($attribute);
                    if (is_string($basePath) && FileHelper::createDirectory($basePath)) {
                        $this->save($attribute,$file, $basePath);
                        $this->afterUpload();
                    } else {
                        throw new InvalidParamException("Directory specified in 'path' attribute doesn't exist or cannot be created.");
                    }
                }
            }
        }
    }
    
    /**
     * After delete file
     * @param string $attribute
     * @param string $singleFileName
     */
    public function afterUnlink($attribute, $singleFileName){
        
    }

    /**
     * Delete file
     */
    public function unlinkOnSave(){
        if($this->deleteFileName){
            foreach ($this->deleteFileName as $attribute => $fileName) {
                if($fileName){
                    $fileNames = [];
                    if(is_array($fileName)){
                        $fileNames = $fileName;
                    }
                    else{
                        $fileNames[] = $fileName;
                    }
                    $basePath = $this->getUploaddirectory($attribute);
                    foreach ($fileNames as $fn) {
                        $path = $basePath.'/'.$fn;
                        if(is_file($path)){
                            @unlink($path);
                        }
                        $this->afterUnlink($attribute, $fn);
                    }
                }
            }
        }
    }


    /**
     * This method is invoked before deleting a record.
     */
    public function afterDelete()
    {
        foreach ($this->attributes as $attribute => $attributeConfig) {
            $unlinkOnDelete = $this->getAttributeConfig($attributeConfig, 'unlinkOnDelete');
            if($unlinkOnDelete){
                $this->delete($attribute);
            }
        }
    }
    
    
    
    /**
     * Returns file path for the attribute.
     * @param string $attribute
     * @param boolean $old
     * @return string|array|null the file path.
     */
    public function getUploadPath($attribute, $old = false)
    {
        $fileName = $this->resolveFileName($attribute, $old);
        if(!$fileName){
            return $fileName;
        }
        $basePath = $this->getUploaddirectory($attribute);
        if(is_array($fileName)){
            foreach ($fileName as $k => $fn) {
                $fileName[$k] = $basePath . '/' . $fn;
            }
            return $fileName ? $fileName : null;;
        }
        return $fileName ? $basePath . '/' . $fileName : "";
    }
    
   /**
    * Returns file url for the attribute.
    * @param string $attribute
    * @return string|array|null the file url.
    */
    public function getUploadUrl($attribute)
    {
        $fileName = $this->resolveFileName($attribute, true);
        if(!$fileName){
            return $fileName;
        }
        $multiple = $this->getAttributeConfig($attribute,'multiple');
        $url = $this->getAttributeConfig($attribute , 'url');
        $url =  Yii::getAlias($this->resolvePath($url));
        if(is_array($fileName)){
            foreach ($fileName as $k => $fn) {
                if($fn){
                   $fileName[$k] = $url . '/' . $fn;
                }
            }
            return $fileName ? $fileName : null;
        }
        if($fileName){
            $fileName = $url . '/' . $fileName;
            if($multiple){
                return [$fileName];
            }
            else{
                return $fileName;
            }
        }
        return null;
    }
    

    /**
     * Replaces all placeholders in path variable with corresponding values.
     */
    protected function resolvePath($path)
    {
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        return preg_replace_callback('/{([^}]+)}/', function ($matches) use ($model) {
            $name = $matches[1];
            $attribute = ArrayHelper::getValue($model, $name);
            if (is_string($attribute) || is_numeric($attribute)) {
                return $attribute;
            } else {
                return $matches[0];
            }
        }, $path);
    }

 

   /**
     * Saves the uploaded file.
     * @param UploadedFile $file the uploaded file instance
     * @param string $path the file path used to save the uploaded file
     * @return boolean true whether the file is saved successfully
     */
    protected function save($attribute,$file, $path)
    {
        $model = $this->owner;
        $model->$attribute = '';
        try {
            $deleteTempFile = $this->getAttributeConfig($attribute, 'deleteTempFile');
            $multipleSeparator = $this->getAttributeConfig($attribute, 'multipleSeparator');
            if(is_array($file)){
                foreach ($file as $f) {
                    $fileName = $this->getFileName($attribute, $f);
                    $f->saveAs($path. '/' .$fileName, $deleteTempFile);
                    $model->$attribute .= $fileName.$multipleSeparator;
                }
                $model->$attribute = trim($model->$attribute,$multipleSeparator);
            }
            else{
                $fileName = $this->getFileName($attribute,$file);
                $file->saveAs($path. '/' . $fileName, $deleteTempFile);
                $model->$attribute = $fileName;
            }
        } catch (\Exception $exc) {
            throw $exc;//new \Exception('File save exception');
        }
        return true;
    }
    

    /**
     * Deletes old file.
     * @param string $attribute
     * @param boolean $old
     */
    protected function delete($attribute, $old = false)
    {
        $fileName = $this->resolveFileName($attribute, $old);
        if($fileName){
            $fileNames = [];
            if(is_array($fileName)){
                $fileNames = $fileName;
            }
            else{
                $fileNames[] = $fileName;
            }
            $basePath = $this->getUploaddirectory($attribute);
            foreach ($fileNames as $fn) {
                $filePath = $basePath . '/' . $fn;
                if (is_file($filePath)) {
                    @unlink($filePath);
                    $this->afterUnlink($attribute, $fn);
                }
            }
        }
    }
 
    
    
   /**
     * Get the UploadedFile
     * @param string $attribute
     * @return UploadedFile|array
     */
    protected function getUploadInstance($attribute){
        $model = $this->owner;
        $multiple = $this->getAttributeConfig($attribute,'multiple');
        $instanceByName = $this->getAttributeConfig($attribute,'instanceByName');
        if ($instanceByName === true) {
            if($multiple){
                $file = UploadedFile::getInstancesByName($attribute);
            }
            else{
                $file = UploadedFile::getInstanceByName($attribute);
            }
        } else {
            if($multiple){
                $file = UploadedFile::getInstances($model,$attribute);
            }
            else{
                $file = UploadedFile::getInstance($model,$attribute);
            }
        }
        return $file;
    }

    /**
     * Verification file
     * @param UploadedFile|array $file
     * @return boolean
     */
    protected function validateFile($file){
        $files = [];
        if(is_array($file)){
            $files = $file;
        }
        else{
            $files[] = $file;
        }
        if(!$files){
            return false;
        }
        foreach ($files as $f) {
            if(!($f instanceof UploadedFile)){
                return false;
            }
        }
        return true;
    }

    /**
     * Get upload directory
     * @return string
     */
    protected function getUploaddirectory($attribute){
        $path = $this->getAttributeConfig($attribute, 'path');
        $path = $this->resolvePath($path);
        return Yii::getAlias($path);
    }




    
    /**
     * resolve file name
     * @param string $attribute
     * @param boolean $old
     * @return string|array
     */
    protected function resolveFileName($attribute, $old = false){
        $multiple = $this->getAttributeConfig($attribute, 'multiple');
        $multipleSeparator = $this->getAttributeConfig($attribute, 'multipleSeparator');
        $fileName = $this->getAttributeValue($attribute , $old);
        $fileName = trim($fileName, $multipleSeparator);
        if($fileName){
            if($multiple){
                if(false !== strpos($fileName,$multipleSeparator)){
                    return explode($multipleSeparator, $fileName);
                }
                else{
                    return [$fileName];
                }
            }
            else{
                return $fileName;
            }
        }
        return null;
    }

   
    /**
     * @param UploadedFile $file
     * @return string
     */
    protected function getFileName($attribute,$file)
    {
        $generateNewName = $this->getAttributeConfig($attribute, 'generateNewName');
        if ($generateNewName) {
            return $generateNewName instanceof Closure
                ? call_user_func($generateNewName, $file)
                : $this->generateFileName($file);
        } else {
            return $this->sanitize($file->name);
        }
    }

    /**
     * Replaces characters in strings that are illegal/unsafe for filename.
     *
     * #my*  unsaf<e>&file:name?".png
     *
     * @param string $filename the source filename to be "sanitized"
     * @return boolean string the sanitized filename
     */
    public static function sanitize($filename)
    {
        return str_replace([' ', '"', '\'', '&', '/', '\\', '?', '#'], '-', $filename);
    }

    /**
     * Generates random filename.
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFileName($file)
    {
        return uniqid() . '.' . $file->extension;
    }

    /**
     * This method is invoked after uploading a file.
     * The default implementation raises the [[EVENT_AFTER_UPLOAD]] event.
     * You may override this method to do postprocessing after the file is uploaded.
     * Make sure you call the parent implementation so that the event is raised properly.
     */
    protected function afterUpload()
    {
        $this->owner->trigger(self::EVENT_AFTER_UPLOAD);
    }    
}
