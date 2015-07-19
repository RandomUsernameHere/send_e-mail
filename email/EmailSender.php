<?php
    namespace web136\email;


    class EmailSender {

        protected $emailTo;

        protected $emailTheme;

        protected $emailContentType = "text/html";

        protected $emailFrom;

        protected $hiddenCopy;

        protected $emailReplyTo;

        protected $emailEncoding = 'utf8';

        protected $message;

        protected $boundary;

        public  function __construct(){

            $this->generateBoundary();

        }

        protected function generateBoundary(){
            $this->boundary = "--".md5(uniqid(time()));
        }

        protected function generateMessageHeaders(){
            $headers = "MIME-Version: 1.0;\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"\r\n";

            if(!empty($this->emailFrom)){
                $headers .= "From: {$this->emailFrom} <{$this->emailFrom}>\r\n";
            }

            if(!empty($this->emailReplyTo)){
                $headers .= "Reply-To: {$this->emailReplyTo}\r\n";
            }
        }

        protected function generateMessageText(){
            $emailText = "--{$this->boundary}\r\n";
            $emailText .= "Content-Type: {$this->emailContentType}; charset={$this->emailEncoding}\r\n";
            $emailText .= "Content-Transfer-Encoding: base64\r\n";
            $emailText .= "\r\n";
            $emailText .= chunk_split(base64_encode($this->message));

            return $emailText;
        }



    }