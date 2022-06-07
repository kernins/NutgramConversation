<?php
namespace dp\NutgramConversation\conversation;
use dp\NutgramConversation\exception;


final class Intent implements \Stringable
   {
      private const SEPARATOR_LVL   = '/';
      private const SEPARATOR_NS    = ':';
      private const ACTION_DEFAULT  = '__default__';
   
      
      //TODO: declare readonly php 8.1
      public string     $action;
      public ?string    $namespace;
      
      private ?self     $_parent = null;
      
      
      
      public function __construct(string $action, ?string $namespace=null, ?self $parent=null)
         {
            $partValidatorRegExp = '/^[a-z\d_]+$/i'; //MUST NOT contain self::SEPARATOR_* chars
            
            if(!preg_match($partValidatorRegExp, $action)) throw new exception\UnexpectedValueException(
               'Given action name ['.$action.'] contains invalid chars'
            );
            $this->action = $action;
            
            if($namespace !== null)
               {
                  if(!preg_match($partValidatorRegExp, $namespace)) throw new exception\UnexpectedValueException(
                     'Given namespace ['.$namespace.'] contains invalid chars'
                  );
               }
            $this->namespace = $namespace;
               
            if(!empty($parent)) $this->setParent($parent);
         }
      
      
      public static function newInstanceOrDefault(?string $action, ?string $namespace=null, ?self $parent=null): self
         {
            return $action===null? self::newInstanceDefault($namespace, $parent) : new self($action, $namespace, $parent);
         }
      
      public static function newInstanceDefault(?string $namespace=null, ?self $parent=null): self
         {
            return new self(self::ACTION_DEFAULT, $namespace, $parent);
         }
      
      public static function newInstanceFromString(string $def, ?Intent $parent=null): self
         {
            $defsByLvl = explode(self::SEPARATOR_LVL, $def);
            $topDef = array_pop($defsByLvl);
            
            $action = explode(self::SEPARATOR_NS, $topDef, 2);
            if(count($action) == 1) $action = [null, $action[0]];
            
            return new self(
               $action[1],
               $action[0],
               empty($defsByLvl)? $parent : self::newInstanceFromString(
                  implode(self::SEPARATOR_LVL, $defsByLvl),
                  $parent
               )
            );
         }
      
      
         
      public function setParent(self $parent): self
         {
            $this->_parent = $parent;
            return $this;
         }
      
      /**
       * @return self[]    [rootIntent, ?nestedIntent] 
       */
      public function split(): array
         {
            $defsByLvl = explode(self::SEPARATOR_LVL, (string)$this, 2);
            
            return [
               self::newInstanceFromString($defsByLvl[0]),
               isset($defsByLvl[1])? self::newInstanceFromString($defsByLvl[1]) : null
            ];
         }
      
      public function isNested(): bool
         {
            return !empty($this->_parent);
         }
      
      
      public function equalsTo(string $intent): bool
         {
            return $intent === (string)$this;
         }
      
      public function isDefault(): bool
         {
            return $this->action === self::ACTION_DEFAULT;
         }
      
      
      public function __toString(): string
         {
            return
               (empty($this->_parent)? '' : (string)$this->_parent.self::SEPARATOR_LVL).
               (empty($this->namespace)? '' : $this->namespace.self::SEPARATOR_NS).
               $this->action;
         }
   }
