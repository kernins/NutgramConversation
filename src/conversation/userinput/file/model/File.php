<?php
namespace dp\NutgramConversation\conversation\userinput\file\model;
use dp\NutgramConversation\exception;


abstract class File
   {
      /** @var string   TG file_id (e.g. for resending to another chat) */
      protected string  $id;
      /** @var string   Human-friendly name */
      protected string  $name;
      
      /** @var string   Current full local path to downloaded file */
      protected string  $localPath;
      /** @var string   Target filename (will be used in postponed moveTo()) */
      protected ?string $targetFilename = null;
      
      /** @var bool     The file is possibly invalidated and needs to be confirmed (or replaced) by user */
      protected bool    $needsConfirmation = false;
      
      
      
      final public function __construct(string $id, string $name, string $localPath, ?string $trgFilename=null)
         {
            $this->id = $id;
            $this->name = $name;
            
            $this->localPath = rtrim($localPath, DIRECTORY_SEPARATOR);
            if($trgFilename !== null) $this->setTargetFilename($trgFilename);
         }
      
      public function setTargetFilename(string $fn): static
         {
            $this->targetFilename = trim($fn, DIRECTORY_SEPARATOR);
            return $this;
         }
      
      public function setNeedsConfirmation(bool $nc): static
         {
            $this->needsConfirmation = $nc;
            return $this;
         }
      
      
      public function moveToTarget(?string $dir=null): static
         {
            $dir ??= dirname($this->localPath);
            if(!is_dir($dir) || !is_writable($dir)) throw new exception\RuntimeException(
               'Target dir ['.$dir.'] doesn\'t exist or not writable'
            );
            
            $trgPath = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.($this->targetFilename ?? basename($this->localPath));
            if($this->localPath != $trgPath)
               {
                  if(!rename($this->localPath, $trgPath)) throw new exception\RuntimeException(
                     'Failed to move/rename file "'.$this->name.'" from ['.$this->localPath.'] to ['.$trgPath.']'
                  );
                  $this->localPath = $trgPath;
               }
            
            return $this;
         }
      
      public function delete(): static
         {
            if(file_exists($this->localPath))
               {
                  if(!unlink($this->localPath)) throw new exception\RuntimeException(
                     'Failed to delete file ['.$this->name.']'
                  );
               }
            return $this;
         }
      
      
      abstract public static function getTGType(): string;
      
      public function getTGId(): string
         {
            return $this->id;
         }
      
      
      public function getName(): string
         {
            return $this->name;
         }
      
      public function getLocalPath(): string
         {
            return $this->localPath;
         }
         
      public function getExtension(): ?string
         {
            $parts = explode('.', basename($this->localPath));
            return count($parts) > 1? array_reverse($parts)[0] : null;
         }
      
      
      public function getNeedsConfirmation(): bool
         {
            return $this->needsConfirmation;
         }
   }
