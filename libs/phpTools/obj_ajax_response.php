<?php
class Ajax_response{
  public $source_data;
  public function __construct($source_data){
    $this->source_data = $source_data;
    return true;
  }
  public function toJson(){
    return json_encode($this->source_data);
  }
  public function toXml(){
    $simplexml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><entries />');
    foreach($this->source_data as $id=>$data){
      $content = $simplexml->addChild('entry');
      foreach($data as $index=>$val){
        if(is_array($val)){
          foreach($val as $val_data){
            if(is_array($val_data)){
              $aa = $content->addChild($index);
              foreach($val_data as $key=>$attr){
                $aa->addChild($key, $attr);
              }
            }
            else{
              $content->addChild($index, $val_data);
            }
          }
        }
        else{
          $content->addChild($index, $val);
        }
      }
    }
    $dom = new DOMDocument();
    $dom->loadXML($simplexml->asXML());
    $dom->formatOutput = true;
    $formattedXML = $dom->saveXML();
    //header("Content-type: text/xml"); 
    return $formattedXML;
  }
  
}

class Ajax_media extends Ajax_response{
  public function chk_file(){
    foreach($this->source_data as $media_info){
      if(isset($media_info['source'])){
        switch($media_info['source']){
          case 'localhost':
            $source_data;
        }
      }
    }
  }  
}
?>