<?php
namespace dp\NutgramConversation\conversation\userinput\file;
use dp\NutgramConversation\conversation, dp\NutgramConversation\exception;
use SergiX44\Nutgram\Telegram\Types as TGTypes;


abstract class CollectionAbstract extends conversation\userinput\BaseAbstract
   {
      /** @var model\File[]      Acquired Files by name */
      protected array   $files = [];
      /** @var model\File[]      Fallback or previously acquired files */
      protected array   $filesDefault = [];
      
      protected string  $saveDir;
      
      
      
      /**
       * @param string        $saveDir
       * @param model\File[]  $filesDefault
       * @throws exception\UnexpectedValueException
       * @throws exception\InvalidArgumentException
       */
      public function __construct(string $saveDir, iterable $filesDefault=[])
         {
            parent::__construct();
         
            if(!is_dir($saveDir) || !is_writable($saveDir))
               throw new exception\UnexpectedValueException('Given saveDir is not a dir or not writable');
            
            $this->saveDir = $saveDir;
            
            foreach($filesDefault as $key => $file)
               {
                  if(!($file instanceof model\File)) throw new exception\InvalidArgumentException(
                     'Invalid files default collection entry at ['.$key.']: not an instance of File'
                  );
                  $this->filesDefault[$file->getName()] = $file;
               }
            $this->loadDefaultCollection();
         }
      
      final protected function loadDefaultCollection(): void
         {
            foreach($this->filesDefault as $file)
               {
                  $this->prepareDefaultFile($file);
                  $this->files[$file->getName()] = $file;
               }
         }
      
      protected function prepareDefaultFile(model\File $file): void
         {
            if(!empty($req=$this->getSourceRequest($file)))
               {
                  //there could have been changes in request data,
                  //particularly in cases where filenames are based on userinput
                  $file->setTargetFilename(
                     $this->sanitizeLocalPath(
                        $req->formatFilename($file->getName()),
                        $file->getExtension()
                     )
                  );
               }
         }
      
      
      /** @return model\File[] */
      public function getValue(): array
         {
            if(!$this->isEnded()) throw new exception\BadMethodCallException('Can not get value from unfinished UserInput collector');
            return $this->files;
         }
      
      public function reset(): void
         {
            parent::reset();
            $this->files = [];
            $this->loadDefaultCollection();
         }
      
      
      
      protected function stepRequest(): void
         {
            $this->requestFile($this->getCurrentRequest());
            $this->next('stepAcquire');
         }
      
      protected function stepAcquire(): void
         {
            if(!empty($file=$this->getSentFile()))
               {
                  $req = $this->getCurrentRequest();
                  $name = $this->getCurrentFileName();
                  
                  $ext = array_reverse(explode('.', $file->file_path))[0];
                  $localPath = $this->saveDir.DIRECTORY_SEPARATOR.$this->sanitizeLocalPath(
                     $name.'_'.preg_replace('/^0\./u', '', microtime()), //ensuring uniqueness
                     $ext
                  );
                  
                  try
                     {
                        if(!$this->bot->downloadFile($file, $localPath))
                           throw new exception\RuntimeException($this->__t('Failed to download file %s', $name));
                        
                        if(!empty($this->files[$name]))
                           $this->files[$name]->delete(); //deleting old file if exists
                        
                        $this->files[$name] = $this->newAcquiredFile(
                           $file->file_id,
                           $name,
                           $localPath,
                           $this->sanitizeLocalPath($req->formatFilename($name), $ext)
                        );
                        
                        $this->onFileAcquired($this->files[$name]);
                        $this->invokeNextStep(self::STEP_START);
                     }
                  catch(\Exception $ex)
                     {
                        $this->sendMessage(
                           $this->__t("Failed to save file %s: %s\nTry again please", $name, $ex->getMessage()),
                           $req->isOptional()? [
                              'reply_markup' => TGTypes\Keyboard\InlineKeyboardMarkup::make()->addRow(
                                 $this->buildInlineButtonStep($this->__t('Skip'), 'stepSkip')
                              )
                           ] : []
                        );
                     }
               }
            else
               {
                  $this->sendMessage($this->__t('No files of expected type were uploaded'));
                  $this->invokeNextStep('stepRequest');
               }
         }
      
      protected function onFileAcquired(model\File $file): void
         {
         
         }
      
      
      abstract protected function stepSkip(): void;
      
      abstract protected function requestFile(model\Request $req): void;
      abstract protected function newAcquiredFile(string $id, string $name, string $localPath, ?string $trgFilename): model\File;
      
      abstract protected function getSentFile(): ?TGTypes\Media\File;
      abstract protected function getSourceRequest(model\File $forFile): ?model\Request;
      abstract protected function getCurrentRequest(): model\Request;
      abstract protected function getCurrentFileName(): string;
      
      
      protected function sanitizeLocalPath(string $fName, ?string $ext=null): string
         {
            if($ext !== null) $ext = preg_replace('/[^a-z\d]/ui', '', $ext);
         
            return
               preg_replace('/[^\w\-.]/ui', '_', $fName).
               (empty($ext)? '' : '.'.$ext);
         }
   }
