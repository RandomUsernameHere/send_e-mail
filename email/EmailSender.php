<?php
    namespace web136\email;

    /**
     * Class EmailSender
     * @package web136\email
     * @description Class for sending e-mail with or without attachments
     */
    class EmailSender {

        /**
         * @var $emailTo string
         */
        protected $emailTo;

        /**
         * @var $emailTheme string
         */
        protected $emailTheme;

        /**
         * @var $emailContentType string
         */
        protected $emailContentType = "text/html";

        /**
         * @var $emailFrom string
         */
        protected $emailFrom;

        /**
         * @var $hiddenCopy string
         */
        protected $hiddenCopy;

        /**
         * @var string
         */
        protected $emailReplyTo;

        /**
         * @var $emailEncoding string
         */
        protected $emailEncoding = 'utf8';

        /**
         * @var $message string
         */
        protected $message;

        /**
         * @var $messageAttach string|array
         */
        protected $messageAttach;

        /**
         * @var $boundary string
         */
        protected $boundary;


        protected $structuredAttachArray;

        public  function __construct(array $params=array()){

            $this->generateBoundary();

            if(!empty($params)){
                $this->setParams($params);
            }

        }


        /**
         * @return void
         */
        protected function generateBoundary(){
            $this->boundary = "--".md5(uniqid(time()));
        }

        /**
         * @return string
         */
        protected function generateMessageHeaders(){
            $headers = "MIME-Version: 1.0;\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"\r\n";

            if(!empty($this->emailFrom)){
                $headers .= "From: {$this->emailFrom} <{$this->emailFrom}>\r\n";
            }

            if(!empty($this->emailReplyTo)){
                $headers .= "Reply-To: {$this->emailReplyTo}\r\n";
            }

            if(!empty($this->hiddenCopy)){
                $headers .= "Bcc: {$this->hiddenCopy}\r\n";
            }

            return $headers;
        }

        /**
         * @return string
         */
        protected function generateMessageText(){
            $emailText = "--{$this->boundary}\r\n";
            $emailText .= "Content-Type: {$this->emailContentType}; charset={$this->emailEncoding}\r\n";
            $emailText .= "Content-Transfer-Encoding: base64\r\n";
            $emailText .= "\r\n";
            $emailText .= chunk_split(base64_encode($this->message));

            return $emailText;
        }

        /**
         * @param $path
         * @return bool|string
         */
        protected function getFileNameFromPath($path){
            if(empty($path)){
                return false;
            }
            else{
                $tmpArray = explode(DIRECTORY_SEPARATOR, (string)$path);
                return end($tmpArray);
            }
        }

        /**
         * @param $path
         * @return \Exception|string
         */
        protected function generateMessageAttach($path){
            try{

                if(empty($path)||empty($path['PATH'])){
                    throw new \Exception('Не передан или пуст обязательный параметр $path');
                }

                $fp = fopen($path['PATH'],"r");
                if (!$fp)
                {
                    throw new \Exception("Не удается открыть файл");
                }

                $file = fread($fp, filesize($path['PATH']));
                fclose($fp);

                $filename = $path['NAME'];

                $message_part = "\r\n--{$this->boundary}\r\n";
                $message_part .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
                $message_part .= "Content-Transfer-Encoding: base64\r\n";
                $message_part .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
                $message_part .= "\r\n";
                $message_part .= chunk_split(base64_encode($file));

                return $message_part;

            }catch (\Exception $e){
                return $e;
            }
        }

        protected  function setParams($params){
            foreach($params as $param=>$value){
                if(!empty($value)){
                    switch($param){
                        case 'EMAIL_TO':
                            $this->setEmailTo($value);
                            break;

                        case 'EMAIL_THEME':
                            $this->setEmailTheme($value);
                            break;

                        case 'CONTENT_TYPE':
                            $this->setEmailContentType($value);
                            break;

                        case 'EMAIL_FROM':
                            $this->setEmailFrom($value);
                            break;

                        case 'HIDDEN_COPY':
                            $this->setHiddenCopy($value);
                            break;

                        case 'REPLY_TO':
                            $this->setEmailReplyTo($value);
                            break;

                        case 'ENCODING':
                            $this->setEmailEncoding($value);
                            break;

                        case 'MESSAGE_TEXT':
                            $this->setMessage($value);
                            break;

                        case 'FILE_ATTACH':
                            $this->setMessageAttach($value);
                            break;

                        default:
                            break;
                    }
                }
            }
        }

        protected function checkParams($params){

            try{

                if(empty($params['EMAIL_TO'])&&empty($this->emailTo)){
                    throw new \Exception('Не передан или пуст адрес получателя');
                }

                if(empty($params['EMAIL_THEME'])&&empty($this->emailTheme)){
                    throw new \Exception('Не передана или пуста тема письма');
                }

                if(empty($params['CONTENT_TYPE'])&&empty($this->emailContentType)){
                    throw new \Exception('Не передан или пуст Content-type');
                }

                if(empty($params['ENCODING'])&&empty($this->emailEncoding)){
                    throw new \Exception('Не передан или пуст параметр encoding');
                }

                return true;

            }catch (\Exception $e){
                return $e;
            }

        }

        protected function checkFileAttach($path){

            try{

                if(empty($path)){
                    throw new \Exception('Не передан или пуст обязательный параметр $path');
                }
                if(is_array($path)){

                    if(
                        (isset($path['PATH'])&&isset($path['NAME']))||
                        isset($path['PATH'])&&!isset($path['NAME'])
                    ){


                        if(empty($path['PATH'])){
                            throw new \Exception('Обязательный параметр $path передан в неправильном формате');
                        }

                        if(empty($path['NAME'])){
                            $path['NAME'] = $this->getFileNameFromPath($path['PATH']);
                        }

                        $tmpArray[] = array
                        (
                            'PATH'=>$path['PATH'],
                            'NAME'=>$path['NAME']
                        );

                        $this->structuredAttachArray = $tmpArray;

                        return $tmpArray;
                    }
                    else{
                        $tmpArray = array();

                        foreach($path as $key=>$value){
                            if(is_array($value)){
                                if(
                                    (isset($value['PATH'])&&isset($value['NAME']))||
                                    isset($value['PATH'])&&!isset($value['NAME'])
                                ) {

                                    if (empty($value['PATH'])) {
                                        throw new \Exception('Обязательный параметр $value передан в неправильном формате');
                                    }

                                    if (empty($value['NAME'])) {
                                        $value['NAME'] = $this->getFileNameFromPath($value['PATH']);
                                    }

                                    $tmpArray[] = array
                                    (
                                        'PATH' => $value['PATH'],
                                        'NAME' => $value['NAME']
                                    );
                                }
                            }
                            else{
                                $tmpArray[] = array
                                (
                                    'PATH' => $value,
                                    'NAME' => $this->getFileNameFromPath($value)
                                );
                            }

                        }

                        if(empty($tmpArray)){
                            throw new \Exception('Обязательный параметр $value передан в неправильном формате');
                        }

                        $this->structuredAttachArray = $tmpArray;

                        return $tmpArray;
                    }


                }
                else{
                    $tmpArray[] = array
                    (
                        'PATH'=>$path,
                        'NAME'=>$this->getFileNameFromPath($path)
                    );

                    $this->structuredAttachArray = $tmpArray;

                    return $tmpArray;
                }

            }catch (\Exception $e){
                return $e;
            }

        }

        /**
         * @param string $emailTo
         */
        public function setEmailTo($emailTo) {
            $this->emailTo = $emailTo;
        }

        /**
         * @param mixed $messageAttach
         * @return bool|\Exception
         */
        public function setMessageAttach($messageAttach) {
            return $this->checkFileAttach($messageAttach);
        }

        /**
         * @param string $emailTheme
         */
        public function setEmailTheme($emailTheme) {
            $this->emailTheme = $emailTheme;
        }

        /**
         * @param string $emailContentType
         */
        public function setEmailContentType($emailContentType) {
            $this->emailContentType = $emailContentType;
        }

        /**
         * @param string $emailFrom
         */
        public function setEmailFrom($emailFrom) {
            $this->emailFrom = $emailFrom;
        }

        /**
         * @param string $hiddenCopy
         */
        public function setHiddenCopy($hiddenCopy) {
            $this->hiddenCopy = $hiddenCopy;
        }

        /**
         * @param string $emailReplyTo
         */
        public function setEmailReplyTo($emailReplyTo) {
            $this->emailReplyTo = $emailReplyTo;
        }

        /**
         * @param string $emailEncoding
         */
        public function setEmailEncoding($emailEncoding) {
            $this->emailEncoding = $emailEncoding;
        }

        /**
         * @param string $message
         */
        public function setMessage($message) {
            $this->message = $message;
        }

        public function send(array $params=array()){
            $this->setParams($params);
            try{
                if(is_a($this->checkParams($params), '\Exception')){
                    return $this->checkParams($params);
                }

                $to = $this->emailTo;

                $subject = $this->emailTheme;

                if(!empty($this->structuredAttachArray)){

                    $attach = '';

                    foreach($this->structuredAttachArray as $key=>$path){

                        $tmpAttach = $this->generateMessageAttach($path);

                        if(is_a($tmpAttach, '\Exception')){
                            return $tmpAttach;
                        }
                        else{
                            $attach .= $tmpAttach;
                        }

                    }

                    $message = $this->generateMessageText().$attach;
                }
                else{
                    $message = $this->generateMessageText();
                }

                $headers = $this->generateMessageHeaders();

                if(!mail($to, $subject, $message, $headers)){
                    throw new \Exception('Ошибка при отправке почты');
                }
                else{
                    return true;
                }

            }catch (\Exception $e){
                return $e;
            }
        }

    }