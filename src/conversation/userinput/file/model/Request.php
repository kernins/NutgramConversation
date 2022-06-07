<?php
namespace dp\NutgramConversation\conversation\userinput\file\model;


class Request implements \Stringable
   {
      protected string  $name;
      protected string  $text;
      protected ?string $textAlt = null;
      
      protected bool    $required;
      
      protected ?string $fnFmt = null;
      
      
      
      public function __construct(string $name, string $text, bool $required=true)
         {
            $this->name = $name;
            $this->text = $text;
            $this->setRequired($required);
         }
      
      public function setTextAlt(string $text): static
         {
            $this->textAlt = $text;
            return $this;
         }
      
      public function setRequired(bool $req): static
         {
            $this->required = $req;
            return $this;
         }
      
      public function setFilenameFormat(string $fmt): static
         {
            $this->fnFmt = $fmt;
            return $this;
         }
      
      
      public function getName(): string
         {
            return $this->name;
         }
      
      public function getText(bool $alt=false): string
         {
            return $alt && !empty($this->textAlt)?
               $this->textAlt :
               $this->text;
         }
      
      public function isOptional(): bool
         {
            return !$this->required;
         }
      
      public function formatFilename(string $name): string
         {
            return empty($this->fnFmt)? $name : sprintf($this->fnFmt, $name);
         }
      
      
      public function __toString(): string
         {
            return $this->text;
         }
   }
