<?php
/**
* megad 
*
* megad
*
* @package project
* @author Serge J. <jey@tut.by>
* MegaD API: http://ab-log.ru/smart-house/ethernet/megad-328-api
* @copyright http://www.atmatic.eu/ (c)
* @version 0.1 (wizard, 12:04:34 [Apr 09, 2015])
*/
Define('DEF_TYPE_OPTIONS', 'automation=Automation|light=Light controller|dimmer=Dimmer controller'); // options for 'TYPE'
//
//
class megad extends module {
/**
* megad
*
* Module class constructor
*
* @access private
*/
function megad() {
  $this->name="megad";
  $this->title="MegaD";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  if (IsSet($this->device_id)) {
   $out['IS_SET_DEVICE_ID']=1;
  }
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='megaddevices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_megaddevices') {
   $this->search_megaddevices($out);
  }
  if ($this->view_mode=='edit_megaddevices') {
   $this->edit_megaddevices($out, $this->id);
  }
  if ($this->view_mode=='delete_megaddevices') {
   $this->delete_megaddevices($this->id);
   $this->redirect("?data_source=megaddevices");
  }

  if ($this->view_mode=='scan') {
   $this->scan();
   $this->redirect("?data_source=megaddevices");
  }



 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='megadproperties') {
  if ($this->view_mode=='' || $this->view_mode=='search_megadproperties') {
   $this->search_megadproperties($out);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* megaddevices search
*
* @access public
*/
 function search_megaddevices(&$out) {
  require(DIR_MODULES.$this->name.'/megaddevices_search.inc.php');
 }

 function readConfig($id) {
  require(DIR_MODULES.$this->name.'/readconfig.inc.php');
 }

 function readValues($id) {
  require(DIR_MODULES.$this->name.'/readvalues.inc.php');
 }

 function scan() {
  require(DIR_MODULES.$this->name.'/scan.inc.php');
 }




/**
* megaddevices edit/add
*
* @access public
*/
 function edit_megaddevices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/megaddevices_edit.inc.php');
 }

/**
* Title
*
* Description
*
* @access public
*/
 function refreshDevice($id) {
  $rec=SQLSelectOne("SELECT * FROM megaddevices WHERE ID='".$id."'");
  if (!$rec['ID']) {
   return;
  }

  $this->readValues($rec['ID']);

 }

/**
* megaddevices delete record
*
* @access public
*/
 function delete_megaddevices($id) {
  $rec=SQLSelectOne("SELECT * FROM megaddevices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM megadproperties WHERE DEVICE_ID='".$rec['ID']."'");
  SQLExec("DELETE FROM megaddevices WHERE ID='".$rec['ID']."'");
  
 }

 function propertySetHandle($object, $property, $value) {
   $properties=SQLSelect("SELECT ID FROM megadproperties WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."' AND TYPE=1");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     $this->setProperty($properties[$i]['ID'], $value);
    }
   }
 }

/**
* Title
*
* Description
*
* @access public
*/
 function processRequest() {
  $ip=$_SERVER['REMOTE_ADDR'];

  $rec=SQLSelectOne("SELECT * FROM megaddevices WHERE IP='".$ip."'");
  if (!$rec['ID']) {
   $rec=array();
   $rec['IP']=$ip;
   $rec['TITLE']='MegaD '.$rec['IP'];
   $rec['PASSWORD']='sec';
   $rec['ID']=SQLInsert('megaddevices', $rec);
   $this->readConfig($rec['ID']);
  } else {
   //processing
   global $pt; //port
   global $m;  // mode
   global $at; // internal temperature
   global $v; // value for ADC
   global $dir; //direction 1/0
   global $cnt; //counter

   //input data changed
   if (isset($pt)) {
    $prop=SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID=".$rec['ID']." AND NUM='".DBSafe($pt)."'");
    if ($prop['ID']) {
     //
     if (isset($v)) {
      $value=$v;
     } else {
      if ($m=='1') {
       $value=0;
      } else {
       $value=1;
      }
     }

     if (isset($cnt)) {
      $prop['COUNTER']=$cnt;
     }

     $old_value=$prop['CURRENT_VALUE'];
     $prop['CURRENT_VALUE']=$value;
     $prop['UPDATED']=date('Y-m-d H:i:s');
     SQLUpdate('megadproperties', $prop);

     if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
      if ($old_value!=$prop['CURRENT_VALUE'] || $prop['CURRENT_VALUE']!=gg($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'])) {
       setGlobal($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'], $prop['CURRENT_VALUE'], array($this->name=>'0'));
      }
     }


    }
   }

   // internal temp sensor data
   if (isset($at)) {
    $prop=SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='".$rec['ID']."' AND TYPE='100'");
    $value=$at;

    if ($prop['ID']) {
     $old_value=$prop['CURRENT_VALUE'];
     $prop['CURRENT_VALUE']=$value;
     $prop['UPDATED']=date('Y-m-d H:i:s');
     SQLUpdate('megadproperties', $prop);

     if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
      if ($old_value!=$prop['CURRENT_VALUE'] || $prop['CURRENT_VALUE']!=gg($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'])) {
       setGlobal($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'], $prop['CURRENT_VALUE'], array($this->name=>'0'));
      }
     }
    }


   }


  }

  exit;

 }

 /**
 * Title
 *
 * Description
 *
 * @access public
 */
  function updateDevices() {
   $devices=SQLSelect("SELECT * FROM megaddevices WHERE UPDATE_PERIOD>0 AND NEXT_UPDATE<=NOW()");
   $total=count($devices);
   for($i=0;$i<$total;$i++) {
    $devices[$i]['NEXT_UPDATE']=date('Y-m-d H:i:s', time()+$devices[$i]['UPDATE_PERIOD']);
    $this->refreshDevice($devices[$i]['ID']);
   }
  }


/**
* Title
*
* Description
*
* @access public
*/
 function setProperty($property_id, $value) {
  $prop=SQLSelectOne("SELECT * FROM megadproperties WHERE ID='".$property_id."'");
  $prop['CURRENT_VALUE']=$value;
  SQLUpdate('megadproperties', $prop);

  $channel=$prop['NUM'];
  $device=SQLSelectOne("SELECT * FROM megaddevices WHERE ID='".$prop['DEVICE_ID']."'");

  if ($prop['TYPE']==1) {
   $url='http://'.$device['IP'].'/'.$device['PASSWORD'].'/?cmd='.$prop['NUM'].':'.$value;
   getURL($url, 0);
  }

  $this->readValues($prop['DEVICE_ID']);

 }


/**
* megadproperties search
*
* @access public
*/
 function search_megadproperties(&$out) {
  require(DIR_MODULES.$this->name.'/megadproperties_search.inc.php');
 }

/**
* Title
*
* Description
*
* @access public
*/
 function get_local_ip() {

                if ( preg_match("/^WIN/", PHP_OS) )
                 $find_ip = $this->get_local_ip_win();
                else
                {
                        $find_ip = $this->get_local_ip_linux();
                }

                        $local_ip='';
                        foreach ( $find_ip as $iface => $iface_ip)
                        {
                                if ( (preg_match("/^192\.168/", $find_ip[$iface]) || preg_match("/^10\./", $find_ip[$iface])) ) {
                                  $local_ip = $find_ip[$iface];
                                  break;
                                }
                        }

                return $local_ip;
  
 }

function get_local_ip_linux()
{
        $out = explode(PHP_EOL,shell_exec("/sbin/ifconfig"));
        $local_addrs = array();
        $ifname = 'unknown';
        foreach($out as $str)
        {
                $matches = array();
                if(preg_match('/^([a-z0-9]+)(:\d{1,2})?(\s)+Link/',$str,$matches))
                {
                        $ifname = $matches[1];
                        if(strlen($matches[2])>0)
                        $ifname .= $matches[2];
                }
                elseif(preg_match('/inet addr:((?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3})\s/',$str,$matches))
                $local_addrs[$ifname] = $matches[1];
        }
        return $local_addrs;
}

function get_local_ip_win()
{
        $out = explode("\n",shell_exec("ipconfig"));

        $local_addrs = array();
        foreach($out as $str)
        {
                if (preg_match('/IPv4/',$str))
                $local_addrs[trim($str)] = preg_replace("/.*:\s(\d+)\.(\d+)\.(\d+)\.(\d+)/", "$1.$2.$3.$4", $str);
        }
        return $local_addrs;
}


/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS megaddevices');
  SQLExec('DROP TABLE IF EXISTS megadproperties');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall() {
/*
megaddevices - megad Devices
megadproperties - megad Properties
*/
  $data = <<<EOD
 megaddevices: ID int(10) unsigned NOT NULL auto_increment
 megaddevices: TITLE varchar(255) NOT NULL DEFAULT ''
 megaddevices: TYPE varchar(255) NOT NULL DEFAULT ''
 megaddevices: CONNECTION_TYPE int(3) NOT NULL DEFAULT '0'
 megaddevices: PORT int(10) NOT NULL DEFAULT '0'
 megaddevices: IP varchar(255) NOT NULL DEFAULT ''
 megaddevices: PASSWORD varchar(255) NOT NULL DEFAULT ''
 megaddevices: ADDRESS int(3) NOT NULL DEFAULT '0'
 megaddevices: UPDATE_PERIOD int(10) NOT NULL DEFAULT '0'
 megaddevices: NEXT_UPDATE datetime
 megaddevices: CONFIG text
 megadproperties: ID int(10) unsigned NOT NULL auto_increment
 megadproperties: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 megadproperties: TYPE int(3) NOT NULL DEFAULT '0'
 megadproperties: NUM int(3) NOT NULL DEFAULT '0'
 megadproperties: CURRENT_VALUE int(10) NOT NULL DEFAULT '0'
 megadproperties: COUNTER int(10) NOT NULL DEFAULT '0'
 megadproperties: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 megadproperties: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
 megadproperties: ETH varchar(255) NOT NULL DEFAULT ''
 megadproperties: ECMD varchar(255) NOT NULL DEFAULT ''
 megadproperties: PWM varchar(255) NOT NULL DEFAULT ''
 megadproperties: MODE varchar(255) NOT NULL DEFAULT ''
 megadproperties: DEF varchar(255) NOT NULL DEFAULT ''
 megadproperties: MISC varchar(255) NOT NULL DEFAULT ''
 megadproperties: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDA5LCAyMDE1IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
