<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */


require_once __DIR__  . '/../../../../core/php/core.inc.php';

class inter2N extends eqLogic {
    /*     * *************************Attributs****************************** */



  public function getModel(){

       $stringForModel = "/api/system/info";
       $responsForModel = $this->resquest($stringForModel);
       if($responsForModel["success"] === true){
          $modelrespons = $responsForModel["result"]["deviceName"];
          $this->setConfiguration('modelName', $modelrespons);
          log::add(__CLASS__, 'debug', 'MODEL NAME ' . $modelrespons);
       }else{
           return "Modèle inconnu";
       }
  }



    public static function deamon_info() {
        $return = array();
        $return['log'] = '';
        $return['state'] = 'nok';
        $cron = cron::byClassAndFunction('inter2N', 'allTheLog');
        if (is_object($cron) && $cron->running()) {
            $return['state'] = 'ok';
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start($_debug = false) {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        log::add(__CLASS__, 'debug', 'subscribe id ' . $idSubs);
        foreach (eqLogic::byType('inter2N', true) as $eqLogic) {
            $idSubs = $eqLogic->subscribe();
            $eqLogic->setConfiguration('idSubs', $idSubs);
            $eqLogic->getModel();
            $eqLogic->save();
        }
        $cron = cron::byClassAndFunction('inter2N', 'allTheLog');
        if (!is_object($cron)) {
            throw new Exception(__('Tâche cron introuvable', __FILE__));
        }
        $cron->run();
    }

    public static function deamon_stop() {
        $cron = cron::byClassAndFunction('inter2N', 'allTheLog');
        if (!is_object($cron)) {
            throw new Exception(__('Tâche cron introuvable', __FILE__));
        }
        $cron->halt();
        foreach (eqLogic::byType('inter2N') as $eqLogic) {
           $id = $eqLogic->getConfiguration('idSubs');
           $eqLogic->unsubscribe($id);
        }

    }


    public static function allTheLog(){
      	foreach (eqLogic::byType('inter2N') as $eqLogic) {
          if($eqLogic->getIsEnable() == 1){
          	inter2N::switchesStatus($eqLogic->getId());
          }
        }
    }

    public function resquest($string){

            $username = $this->getConfiguration('username');
            $password = $this->getConfiguration('password');
            $protocole = $this->getConfiguration('protocole');
            $ip = $this->getConfiguration('ip');

        if(empty($username) ||  empty($password) || empty($ip) ){
            return;
        } else {
            $startRequest =  $protocole . '://' . $username .':'. $password .'@'. $ip;
        }
        $http = new com_http( $startRequest . $string);

        if(empty($startRequest)){
        } else {
            $request = $http->exec();
            $respons = json_decode($request, true);
            log::add(__CLASS__, 'debug', $respons);
            return $respons;
        }
    }



    public function createCamera(){
        if($this->getConfiguration('cameraselect') == 'yes'){
            $username = $this->getConfiguration('username');
            $password = $this->getConfiguration('password');
            $ip = $this->getConfiguration('ip');
            $port = config::byKey('portconfig', 'inter2N');
            $protocole = $this->getConfiguration('protocole');
            $name = $this->getName();

            $camera_jeedom = eqLogic::byLogicalId('camerainter2N_'.$name, 'camera');
            if (class_exists('camera')) {
                if (!is_object($camera_jeedom)) {
                    $camera_jeedom = new camera();
                    $camera_jeedom->setIsEnable(1);
                    $camera_jeedom->setIsVisible(1);
                    $camera_jeedom->setName('inter2N_Camera_'.$name);
                }
                $camera_jeedom->setConfiguration('ip', $ip);
                $camera_jeedom->setConfiguration('urlStream', '/api/camera/snapshot?width=640&height=480&source=internal');
                $camera_jeedom->setEqType_name('camera');
                $camera_jeedom->setConfiguration('protocole', $protocole);
                $camera_jeedom->setConfiguration('port', $port);
                $camera_jeedom->setConfiguration('username', $username);
                $camera_jeedom->setConfiguration('password', $password);
                $camera_jeedom->setLogicalId('camerainter2N_'.$name);
                $camera_jeedom->setDisplay('height','350px');
                $camera_jeedom->setDisplay('width', '350px');
                $camera_jeedom->save();
                message::add('inter2N', 'Un objet Camera à été créé, rendez vous dans le plugin Camera pour la gestion de celle-ci');
            }else{
              log::add(__CLASS__, 'debug', 'Le plugin camera ne doit pas etre installé');
            }
        }
    }



     public static function refreshDash($eqLogic){

           $cmdtest = cmd::byEqLogicId($eqLogic->getId());
           foreach($cmdtest as $cmdt){
             $mystring = $cmdt->getName();
             $findme   = 'Etat SWITCH_';
             $pos = strpos($mystring, $findme);

             if($pos !== false){
               $cmdt->setConfiguration('find', 'true');
             }else{
               $cmdt->setConfiguration('find', 'false');

             }
             if($cmdt->getSubType() == 'string'){
               $cmdt->event('');
             }elseif ($cmdt->getSubType() == 'binary' && $cmdt->getConfiguration('find') == 'false') {
               $cmdt->event(0);
             }
           }

    }



   public static function switchesStatus($eqLogic_id){    
      
      
        	$eqLogic = eqLogic::byId($eqLogic_id);
      		if(!is_object($eqLogic)){
            return;
            }
            $id = $eqLogic->getConfiguration('idSubs');
            if(empty($id)){
                log::add(__CLASS__, 'debug', 'L identifiant de l equipement est vide, redemarrez le demon');
            }else {
                $responsForLog = $eqLogic->logPull($id);
                if($responsForLog == 'false'){
                  return;                 
                }
                //Create an array with status of Output
                $arrayStatusSwitches = array();
                foreach ($responsForLog as $value){
                    $events = $value['events'];
                }

                foreach($events as $event){
                    $params = $event['params'];
    				        switch ($event['event']) {
                                case "MotionDetected":
                                  log::add(__CLASS__, 'debug', 'Motion detected:' . $params['state']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,'Mouvement');
                                  if($params['state'] == "in"){
                                      $cmd->event(1);
                                      $eqLogic->refreshWidget();
                                  }else{
                                      $cmd->event(0);
                                      $eqLogic->refreshWidget();
                                  }

                                  break;
                                case "NoiseDetected":
                                  log::add(__CLASS__, 'debug', 'Noise detected:' . $params['state']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,'Bruit_Detecte');
                                  if($params['state'] == "in"){
                                      $cmd->event(1);
                                      $eqLogic->refreshWidget();
                                      log::add(__CLASS__, 'debug', 'NoiseDetected');
                                  }elseif($params['state'] == "out"){
                                      $cmd->event(0);
                                      $eqLogic->refreshWidget();
                                  }else{
                                      $cmd->event(0);
                                  }
                                  break;
                                case "KeyPressed":
                                  log::add(__CLASS__, 'info', 'key pressed:' . $params['key']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,"dernier_bouton");
                                  $cmd->event($params['key']);
                                  break;
                                case "CodeEntered":
                                  log::add(__CLASS__, 'info', 'code entered:' . $params['code']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,"Code_entree");
                                  $cmd->event($params['code']);
                                  break;
                                case "CardEntered":
                                  log::add(__CLASS__, 'debug', 'card entered:' . $params['uid']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,"Lecteur_carte");
                                  $cmd->event($params['uid']);
                                  break;
                                case "SwitchStateChanged":
                                  log::add(__CLASS__, 'debug', 'etat switch:' . $params['switch'] . ' State :'. $params['state']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,'SWITCH_'.$params['switch']);
                                  if($params['state'] == true){
                                       $cmd->event(1);
                                       $eqLogic->refreshWidget();
                                  } else{
                                       $cmd->event(0);
                                       $eqLogic->refreshWidget();
                                  }
                                  break;
                                  case "InputChanged":
                                    log::add(__CLASS__, 'debug', 'etat input:' . $params['port'] . ' State :'. $params['state']);
                                    $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id, $params['port']);
                                    if($params['state'] == true){
                                         $cmd->event(1);
                                         $eqLogic->refreshWidget();
                                    } else{
                                         $cmd->event(0);
                                         $eqLogic->refreshWidget();
                                    }
                                    break;

                                    case "OutputChanged":
                                      log::add(__CLASS__, 'debug', 'etat output:' . $params['port'] . ' State :'. $params['state']);
                                      $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id, $params['port']);
                                      if($params['state'] == true){
                                           $cmd->event(1);
                                           $eqLogic->refreshWidget();
                                      } else{
                                           $cmd->event(0);
                                           $eqLogic->refreshWidget();
                                      }

                                      break;

                                case "CallStateChanged":
                                  log::add(__CLASS__, 'debug', 'call :' . $params['direction'] . ' State :'. $params['state']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,"Appel");
                                  $cmd->event($params['state']);
                                  $eqLogic->refreshWidget();
                                  break;
                                case "TamperSwitchActivated":
                                  log::add(__CLASS__, 'debug', 'TamperSwitchActivated :' . $params['state']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,'Arrachement_Interphone');
                                  if($params['state'] == "in"){
                                  	$cmd->event(1);
                                    $eqLogic->refreshWidget();
                                  }else{
                                      $cmd->event(0);
                                    $eqLogic->refreshWidget();
                                  }
                                  break;
                                case "UnauthorizedDoorOpen":
                                  log::add(__CLASS__, 'debug', 'UnauthorizedDoorOpen :' . $params['state']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,'ouverture_non_autorisee');
                                  if($params['state'] == "in"){
                                      $cmd->event(1);
                                    $eqLogic->refreshWidget();
                                  }else{
                                      $cmd->event(0);
                                    $eqLogic->refreshWidget();
                                  }
                                  break;
                                case "DoorOpenTooLong":
                                  log::add(__CLASS__, 'debug', 'DoorOpenTooLong :' . $params['state']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,'Porte_ouverte_trop_longtemps');
                                  if($params['state'] == "in"){
                                      $cmd->event(1);
                                    $eqLogic->refreshWidget();
                                  }else{
                                      $cmd->event(0);
                                    $eqLogic->refreshWidget();
                                  }
                                  break;
                                case "FingerEntered":
                                  log::add(__CLASS__, 'debug', 'FingerEntered :' . $params['direction'] . ' - UID : '.$params['uuid']. ' - valid :'.$params['valid']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,"empreinte");
                                  $cmd->event($params['uuid']);
                                  $eqLogic->refreshWidget();
                                  break;
                                case "MobKeyEntered":
                                  log::add(__CLASS__, 'debug', 'MobKeyEntered :' . $params['direction'] . ' - UID : '.$params['authid']. ' - valid :'.$params['valid']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,"Bluetooth_Tel_Mobile");
                                  $cmd->event($params['authid']);
                                  $eqLogic->refreshWidget();
                                  break;
                                case "DoorStateChanged":
                                  log::add(__CLASS__, 'debug', 'DoorStateChanged :' . $params['state']);
                                  $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic_id,'Etat_porte');
                                  if($params['state'] == "opened"){
                                      $cmd->event(1);
                                    $eqLogic->refreshWidget();
                                  }else{
                                      $cmd->event(0);
                                    $eqLogic->refreshWidget();
                                  }
                                  break;
                  }

                    }
                    if(@$state === false){
                        array_push($arrayStatusSwitches, '0');
                    }elseif(@$state === true){
                        array_push($arrayStatusSwitches, '1');
                    }
                   /* inter2N::refreshDash($eqLogic);*/
                  /*  log::add(__CLASS__, 'debug', 'responsForEventSwitch:' . json_encode($event));*/
                    log::add(__CLASS__, 'debug', 'arrStatusSwitch' . json_encode($arrayStatusSwitches));
             }
    }

    public function subscribe(){
        $stringForSubscribe = "/api/log/subscribe?-10";
        $responsForSubscribe = $this->resquest($stringForSubscribe);
        foreach($responsForSubscribe as $value){
            @$id = $value['id'];
        }
        if(empty($id)){
            log::add(__CLASS__, 'debug', 'Pas d\'ID attribué, redémarrez le démon !!');
            return;
        }
        log::add(__CLASS__, 'debug', 'id' . $id);
        return @$id;
    }

    public function unsubscribe($id){
        $stringForUnsubscribe = "/api/log/log/unsubscribe?id=" . $id;
        $responsForUnsubscribe = $this->resquest($stringForUnsubscribe);
        return;
    }

    public function logPull($id){
        $timeoutchoice = $this->getConfiguration('timeout',20);
        if(is_numeric($timeoutchoice) == false){
            message::add(__CLASS__, 'Le timeout renseigné n\'est pas au format numerique. Processus arreté', 'tiemout', 'timeout2N');  
            sleep(10);
            message::removeAll(__CLASS__, 'timeout2N');
            $responsForlog = 'false';
            return $responsForlog;
          
        }
        $stringForLog = "/api/log/pull?id={$id}&timeout={$timeoutchoice}"; 
            $username = $this->getConfiguration('username');
            $password = $this->getConfiguration('password');
            $protocole = $this->getConfiguration('protocole');
            $ip = $this->getConfiguration('ip');

        if(empty($username) ||  empty($password) || empty($ip) ){
            return;
        } else {
            $startRequest =  $protocole . '://' . $username .':'. $password .'@'. $ip;
        }
        $http = new com_http( $startRequest . $stringForLog);

        if(empty($startRequest)){
        } else {
            $request = $http->exec($timeoutchoice);
            $responsForlog = json_decode($request, true);
        }
        log::add(__CLASS__, 'debug', $stringForLog . ' ' . json_encode($responsForlog));
        return $responsForlog;
    }



    public function switchesIdArray(){

        $arrayIdSwitches = array();
        $stringForCheckSwitches = "/api/switch/caps";
        $responsForCheckSwitches = $this->resquest($stringForCheckSwitches);
        foreach ($responsForCheckSwitches as $value){
            $switches = $value['switches'];
            foreach($switches as $switch){
                if($switch['enabled'] === true ){
                    $idSwitch =  $switch['switch'];
                    array_push($arrayIdSwitches, $idSwitch);
                } else {}
            }
        }
        return $arrayIdSwitches;
    }


    public function iOIdArray(){

        $arrayIoSwitches = array();
        $stringForCheckIo = "/api/io/caps";
        $responsForCheckIo = $this->resquest($stringForCheckIo);
        foreach ($responsForCheckIo as $value){
            $io = $value['ports'];
            foreach($io as $element){
                if($element['port'] != '' ){
                    $idport =  $element['port'];
                    array_push($arrayIoSwitches, $idport);
                } else {}
            }
        }
        return $arrayIoSwitches;
    }


      public function getXmlConfig($string){
            $username = $this->getConfiguration('username');
            $password = $this->getConfiguration('password');
            $protocole = $this->getConfiguration('protocole');
            $ip = $this->getConfiguration('ip');

            if(empty($username) ||  empty($password) || empty($ip) ){
                return;
            } else {
                $startRequest = $protocole . '://' . $username .':'. $password .'@'. $ip;
            }
            $http = new com_http( $startRequest . $string);

            if(empty($startRequest)){
            } else {
                $request = $http->exec();
                return $request;
            }

    }


  public function config_xml_put($arraymastercode, $stringForConfig, $xml){
            $username = $this->getConfiguration('username');
            $password = $this->getConfiguration('password');
            $protocole = $this->getConfiguration('protocole');
            $ip = $this->getConfiguration('ip');
            $choicesignal = $this->getConfiguration('sonnerietype');
            $xmlB = new SimpleXMLElement($xml);
            $i = 0;
            $test = '';
            if($choicesignal == 'simplesignal'){ $test = 1; };
            if($choicesignal == 'bothsignal'){ $test = 2; };
            if($choicesignal == 'nonesignal'){ $test = 0; };

            $xmlB->AccessControl->AccessPoint[0]->Signalization = $test;
            $xmlB->AccessControl->AccessPoint[1]->Signalization = $test;

            foreach($arraymastercode as $switch=>$array_switch){
                   $len_array_values = count($array_switch);
                   for($j=0; $j < $len_array_values; $j++){
                      if($switch != ''){
                         $xmlB->Switches->Switch[$i]->Code[$j]->Code = intval($array_switch[$j]);
                        }
                   }
                   $i++;
             }

            $xml_to_upload = $xmlB->asXML();

            if(empty($username) ||  empty($password) || empty($ip) ){
                return;
            } else {
                $startRequest =  $protocole . '://' . $username .':'. $password .'@'. $ip;
            }
            $http = new com_http( $startRequest . $stringForConfig);
            $array_req = array('blob-cfg' => $xml_to_upload);
            $http->setPut($array_req);

            if(empty($startRequest)){
            } else {
                $request = $http->exec();
                 log::add(__CLASS__, 'debug', 'STATUS_REQUETE_CONFIG ' . json_encode($request));
               /* return $request;*/
            }


  }



    public function action($action, $option){

            if($action == 'On'){
                $stringForAction = "/api/switch/ctrl?switch=" . $option . "&action=on";

            } else {
                $stringForAction = "/api/switch/ctrl?switch=" . $option . "&action=off";

            }

        $responsForAction = $this->resquest($stringForAction);
        log::add(__CLASS__, 'debug', 'responsFunctionAction:' . json_encode($responsForAction));
        return $responsForAction;
    }



    public function action_iO($typeiO,$exception){
        $base = "/api/io/ctrl?port=";
        if($exception == 'on'){
          $stringForAction = $base . $typeiO . "&action=on";
        }elseif($exception == 'off'){
          $stringForAction = $base . $typeiO . "&action=off";
        }
        $responsForAction = $this->resquest($stringForAction);
        log::add(__CLASS__, 'debug', 'responsFunctionAction:' . json_encode($responsForAction));
        return $responsForAction;
    }




    /*     * ***********************Methode static*************************** */

    /*
    * Fonction exécutée automatiquement toutes les minutes par Jeedom  */
    //   public static function cron() {
        //   }



        /*
        * Fonction exécutée automatiquement toutes les heures par Jeedom
        public static function cronHourly() {

        }
        */

        /*
        * Fonction exécutée automatiquement tous les jours par Jeedom
        public static function cronDaily() {

        }
        */



        /*     * *********************Méthodes d'instance************************* */

    /*     public function toHtml($_version = 'dashboard') {

          $replace = $this->preToHtml($_version);
          if (!is_array($replace)) {
            return $replace;
          }
          $version = jeedom::versionAlias($_version);


             foreach (($this->getCmd('info')) as $cmd) {
                $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
                $replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();

              }

           $replace['#Switch On_1_id#'] = $this->getCmd('action', 'Switch On_1')->getId();

          $html = template_replace($replace, getTemplate('core', $version, 'inter2N.template', __CLASS__));
          cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
          return $html;
        }*/




        public function preInsert() {
              $this->setDisplay('height','350px');
              $this->setDisplay('width', '540px');
              $this->setIsEnable(1);
              $this->setIsVisible(1);
        }

        public function postInsert() {

        }

        public function preSave() {

        }



    public static function sanitize_strings($string_code){

          $string_explode = explode(',', $string_code);
         foreach($string_explode as $element){
              trim($element);
          }
         log::add(__CLASS__, 'debug', 'STRINGEXPLODE ' . json_encode($string_explode));
          return $string_explode;

     }



        public function postSave() {
            $ports = $this->iOIdArray();
            log::add(__CLASS__, 'debug', 'PORTS ' .json_encode($ports));
            $stringForConfig = "/api/config";
            $ip = $this->getConfiguration('ip');
            if($ip != ''){
                $this->setLogicalId($ip);
            }
            $this->createCamera();
            $this->crea_cmd();
            $xml = $this->getXmlConfig($stringForConfig);
            log::add(__CLASS__, 'debug', 'CODE1 ' .$this->getConfiguration('mastercodeSwitch1'));
           /* log::add(__CLASS__, 'debug', 'XML ' .json_encode($xml));*/
            $arraystring1 = self::sanitize_strings($this->getConfiguration('mastercodeSwitch1'));
            $arraystring2 = self::sanitize_strings($this->getConfiguration('mastercodeSwitch2'));
            $arraystring3 = self::sanitize_strings($this->getConfiguration('mastercodeSwitch3'));
            $arraystring4 = self::sanitize_strings($this->getConfiguration('mastercodeSwitch4'));
             log::add(__CLASS__, 'debug', 'STRING1 ' .json_encode($arraystring1));
              $array_mastercodes = array(
                            'Switch1' => $arraystring1,
                            'Switch2' => $arraystring2,
                            'Switch3' => $arraystring3,
                            'Switch4' => $arraystring4
                             );

            if( ($this->getConfiguration('password') != '' )|| ($this->getConfiguration('ip') != '') ){
                   $this->config_xml_put($array_mastercodes, $stringForConfig, $xml);
            }

        }


        public function preUpdate() {
                if ($this->getConfiguration('ip') == '') {
    			     throw new Exception(__('L\'adresse IP de l\'équipement ne peut être vide', __FILE__));
    		     }
               if ($this->getConfiguration('username') == '') {
    			     throw new Exception(__('L\'username de l\'équipement ne peut être vide', __FILE__));
    		     }
               if ($this->getConfiguration('password') == '') {
    			     throw new Exception(__('Le mot de passe de l\'équipement ne peut être vide', __FILE__));
    		     }
        }



        public function postUpdate() {

        }

        public function preRemove() {

        }

        public function postRemove() {

        }


      public function crea_cmd() {
            //cmd ON for switches
           $cmd = cmd::byEqLogicIdAndLogicalId($this->getId(), 'refresh');
                    if (!is_object($cmd)) {
                        $cmd = new inter2NCmd();
                        $cmd->setLogicalId('refresh');
                        $cmd->setName(__('Rafraichir', __FILE__));
                        $cmd->setIsVisible(1);
                        $cmd->setType('action');
                        $cmd->setSubType('other');
                        $cmd->setEqLogic_id($this->getId());
                        $cmd->save();
                    }
            $ports = $this->iOIdArray();
            foreach ($ports as $port) {
                    $cmd = cmd::byEqLogicIdAndLogicalId($this->getId(), $port);
                    if (!is_object($cmd)) {
                        $cmd = new inter2NCmd();
                        $cmd->setLogicalId($port);
                        $cmd->setName(__($port, __FILE__));
                        $cmd->setIsVisible(0);
                        $cmd->setType('info');
                        $cmd->setSubType('binary');
                        $cmd->setEqLogic_id($this->getId());
                        $cmd->setDisplay('generic_type', 'GENERIC');
                        $cmd->save();

                    }
                    $stateId = $cmd->getId();


                    $cmd = $this->getCmd(null, $port.'_Off');
                    if (!is_object($cmd)) {
                        $cmd = new inter2NCmd();
                        $cmd->setLogicalId($port.'_Off');
                        $cmd->setName(__($port.'_off', __FILE__));
                        $cmd->setIsVisible(1);
                    }
                    $cmd->setType('action');
                    $cmd->setSubType('other');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setDisplay('generic_type', 'SWITCH_OFF');
                    $cmd->setTemplate('dashboard','inter2N::actionswitch');
                    $cmd->setOrder(1);
                    $cmd->setValue($stateId);
                    $cmd->save();


                    $cmd = $this->getCmd(null, $port.'_On');
                    if (!is_object($cmd)) {
                        $cmd = new inter2NCmd();
                        $cmd->setLogicalId($port.'_On');
                        $cmd->setName(__($port.'_On', __FILE__));
                        $cmd->setIsVisible(1);
                    }
                    $cmd->setType('action');
                    $cmd->setSubType('other');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setDisplay('generic_type', 'SWITCH_ON');
                    $cmd->setTemplate('dashboard','inter2N::actionswitch');
                    $cmd->setOrder(2);
                    $cmd->setValue($stateId);
                    $cmd->save();
            }


            $switches = $this->switchesIdArray();
            foreach ($switches as $switch) {
                    $cmd = cmd::byEqLogicIdAndLogicalId($this->getId(), 'SWITCH_' . $switch);
                    if (!is_object($cmd)) {
                        $cmd = new inter2NCmd();
                        $cmd->setLogicalId('SWITCH_' . $switch);
                        $cmd->setName(__('Etat SWITCH_' . $switch, __FILE__));
                        $cmd->setIsVisible(0);
                        $cmd->setType('info');
                        $cmd->setSubType('binary');
                        $cmd->setEqLogic_id($this->getId());
                        $cmd->setDisplay('generic_type', 'GENERIC');
                        $cmd->save();

                    }
                    $stateId = $cmd->getId();


                    $cmd = $this->getCmd(null, 'Switch Off_'. $switch);
                    if (!is_object($cmd)) {
                        $cmd = new inter2NCmd();
                        $cmd->setLogicalId('Switch Off_' . $switch);
                        $cmd->setName(__('SWITCH_'.$switch.'_off', __FILE__));
                        $cmd->setIsVisible(1);
                    }
                    $cmd->setType('action');
                    $cmd->setSubType('other');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setDisplay('generic_type', 'SWITCH_OFF');
                    $cmd->setTemplate('dashboard','inter2N::actionswitch');
                    $cmd->setOrder(3);

                    $cmd->setValue($stateId);
                    $cmd->save();


                    $cmd = $this->getCmd(null, 'Switch On_'. $switch);
                    if (!is_object($cmd)) {
                        $cmd = new inter2NCmd();
                        $cmd->setLogicalId('Switch On_' . $switch);
                        $cmd->setName(__('SWITCH_'.$switch.'_on', __FILE__));
                        $cmd->setIsVisible(1);
                    }
                    $cmd->setType('action');
                    $cmd->setSubType('other');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setDisplay('generic_type', 'SWITCH_ON');
					          $cmd->setTemplate('dashboard','inter2N::actionswitch');
                    $cmd->setOrder(4);
                    $cmd->setValue($stateId);
                    $cmd->save();
            }

            $arrayFunctions = array();
            $stringForEnableFunctions = "/api/system/caps";
            $responsForEnableFunctions = $this->resquest($stringForEnableFunctions);
            foreach($responsForEnableFunctions as $enable){
                if($enable === true){
                    $result = $responsForEnableFunctions['result'];
                    $activeFunctions = $result['options'];
                    array_push($arrayFunctions, $activeFunctions);
                    foreach( $arrayFunctions as $function){
                        //Cmd info binary

                        $namesInfoBinary = ['motionDetection;Mouvement', 'noiseDetection;Bruit_Detecte', 'doorSensor;Arrachement_Interphone', 'doorSensor;Porte_ouverte_trop_longtemps', 'doorSensor;ouverture_non_autorisee', 'doorSensor;Etat_porte'];
                        $namesInfoString = ['keypad;dernier_bouton', 'keypad;Code_entree','cardReader;Lecteur_carte','fpReader;empreinte','phone;Bluetooth_Tel_Mobile', 'phone;Appel'];

                       if($this->getConfiguration('fingerprintselect') == 'no'){
                           $elementa = 'fpReader;empreinte';
                           unset($namesInfoString[array_search($elementa, $namesInfoString)]);
                       }
                       if($this->getConfiguration('tamperswitchprot') == 'no'){
                           $elementb = 'doorSensor;Arrachement_Interphone';
                           unset($namesInfoBinary[array_search($elementb, $namesInfoBinary)]);

                       }


                        foreach($namesInfoBinary as $nameInfoBinary){
                            $nameInfoBinaryExplode = explode(";", $nameInfoBinary);
                            $nameCmdinter2N = $nameInfoBinaryExplode[0];
                            $nameCmdPlugin = $nameInfoBinaryExplode[1];
                            if($function[$nameCmdinter2N] === 'active,licensed' || $function[$nameCmdinter2N] === 'active'){
                                $cmd = $this->getCmd(null, $nameCmdPlugin);
                                if (!is_object($cmd)) {
                                    $cmd = new inter2NCmd();
                                    $cmd->setLogicalId($nameCmdPlugin);
                                    $cmd->setName(__($nameCmdPlugin, __FILE__));
                                    $cmd->setIsVisible(1);
                                }
                               if($cmd->getName() == 'Mouvement'){
                                 $cmd->setTemplate('dashboard', 'presence');
                                 $cmd->setGeneric_type('PRESENCE');
                               }
                               if($cmd->getName() == 'Etat_porte'){
                                 $cmd->setTemplate('dashboard', 'door');

                               }
                                $cmd->setType('info');
                                $cmd->setSubType('binary');
                                $cmd->setEqLogic_id($this->getId());
                                $cmd->save();

                            }
                            log::add(__CLASS__, 'debug', 'cmdInfoBinaryCreate : ' . $cmd->getName());
                        }


                        foreach($namesInfoString as $nameInfoString){
                            //Cmd info String
                            $nameInfoStringExplode = explode(";", $nameInfoString);
                            $nameCmdinter2N = $nameInfoStringExplode[0];
                            $nameCmdPlugin = $nameInfoStringExplode[1];
                            if($function[$nameCmdinter2N] === 'active,licensed' || $function[$nameCmdinter2N] === 'active'){
                                $cmd = $this->getCmd(null, $nameCmdPlugin);
                                if (!is_object($cmd)) {
                                    $cmd = new inter2NCmd();
                                    $cmd->setLogicalId($nameCmdPlugin);
                                    $cmd->setName(__($nameCmdPlugin, __FILE__));
                                    $cmd->setIsVisible(1);
                                }
                                  if($cmd->getName() == 'Appel'){
                                 $cmd->setDisplay('icon', '<i class="icon techno-phone16"></i>');
                                 $cmd->setDisplay('showNameOndashboard',1);
                                 $cmd->setDisplay('showIconAndNamedashboard',1);
                               }
                                 if($cmd->getName() == 'Lecteur_carte'){
                                 $cmd->setDisplay('icon', '<i class="fas fa-credit-card"></i>');
                                 $cmd->setDisplay('showNameOndashboard',1);
                                 $cmd->setDisplay('showIconAndNamedashboard',1);
                               }

                                $cmd->setType('info');
                                $cmd->setSubType('string');
                                if( ($cmd->getLogicalId() == 'Code_entree') || ($cmd->getLogicalId() == 'Lecteur_carte')  ){
                                  $cmd->setConfiguration('repeatEventManagement','always');
                                }
                                $cmd->setEqLogic_id($this->getId());
                                $cmd->setOrder(1);
                                $cmd->save();
                            }
                            log::add(__CLASS__, 'debug', 'cmdInfoSrtingCreate : ' . $cmd->getName());
                        }
                    }

                } elseif($enable === false){
                    message::add('inter2N', json_encode($responsForEnableFunctions['error']));
                }
            }
        }


  public static function templateWidget(){

      $return = array('info' => array('string' => array()));
	    $return['action']['other']['actionswitch'] = array(
		  'template' => 'tmplimg',
		  'replace' => array(
			'#_img_light_on_#' => '<img class=\'img-responsive" src="plugins/inter2N/data/img/defaut_on.png\' width="50" height="50">',
			'#_img_light_off_#' => '<img class=\'img-responsive" src="plugins/inter2N/data/img/defaut_off.png\' width="50" height="50">',
            '#_img_dark_on_#' => '<img class=\'img-responsive" src="plugins/inter2N/data/img/defaut_on.png\' width="50" height="50">',
			'#_img_dark_off_#' => '<img class=\'img-responsive" src="plugins/inter2N/data/img/defaut_off.png\' width="50" height="50">'
			)
	);
	return $return;


  }

        //  Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin


        /*
        * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
        public static function postConfig_<Variable>() {
        }
        */

        /*
        * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
        public static function preConfig_<Variable>() {
        }
        */

        /*     * **********************Getteur Setteur*************************** */
    }

    class inter2NCmd extends cmd {
        /*     * *************************Attributs****************************** */


        /*     * ***********************Methode static*************************** */


        /*     * *********************Methode d'instance************************* */

        /*
        * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
        public function dontRemoveCmd() {
            return true;
        }
        */

        public function execute($_options = array()) {
            $eqLogic = $this->getEqLogic();
            if ($this->getType() != 'action' && 'ínfo') {
                return;
            }
            switch ($this->getLogicalId()) {
                case 'refresh':
                    inter2N::refreshDash($eqLogic);
                    break;
            }

            $mystring = $this->getLogicalId();
            $findme   = 'On';
            $pos = strpos($mystring, $findme);
            if($pos !== false){
                $id = explode("_", $mystring);
                $findtwo = 'Switch';
                $pos2 = strpos($id[0], $findtwo);
                if($pos2 !== false){
                  $option = $id[1];
                }else{
                  $option = $id[0];
                }

            }elseif($pos == false){
                $findmeOff = 'Off';
                $posOff = strpos($mystring, $findmeOff);
                if($posOff !== false){
                    $id = explode("_", $mystring);
                    $findmeOff2 = 'Switch';
                    $posoff2 = strpos($id[0], $findmeOff2);
                    if($posoff2 !== false){
                      $option = $id[1];
                    }else{
                      $option = $id[0];
                    }
                 }
            }else{
                return;
            }

            switch ($this->getLogicalId()) {

                case 'Switch On_' . $option:
                    $action = 'On';
                    $eqLogic->action($action, $option);
                    return;
                break;

                case 'Switch Off_' . $option:
                    $action = 'Off';
                    $eqLogic->action($action, $option);
                    return;
                break;

                case $option.'_On':
                    $typeiO = $option;
                    $exception = 'on';
                    $eqLogic->action_iO($typeiO, $exception);
                    return;
                break;

                case $option.'_Off':
                    $typeiO = $option;
                    $exception = 'off';
                    $eqLogic->action_iO($typeiO, $exception);
                    return;
                break;

            };
        }

        /*     * **********************Getteur Setteur*************************** */
    }