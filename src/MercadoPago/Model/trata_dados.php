
<?php

class TrataDados
{

  static function dados($v){
    $dados=array();
    $dados['complementos']=array("casa","ap","apto","apart","frente","fundos","sala","cj");
    $dados['brasilias']=array("bloco","setor","quadra","lote");
    $dados['naobrasilias']=array("av","avenida","rua","alameda","al.","travessa","trv","praça","praca");
    $dados['sems']=array("sem ","s.","s/","s. ","s/ ");
    $dados['numeros']=array('n.º','nº',"numero","num","número","núm","n");
    $dados['semnumeros']=array();
    foreach($dados['numeros'] as $n)
      foreach($dados['sems'] as $s)
      $dados['semnumeros'][]="$s$n";
    return $dados[$v];
  }

  static function endtrim($e){
    return preg_replace('/^\W+|\W+$/','',$e);
  }

  static function ehBrasilia($end){
    $brasilias=self::dados('brasilias');
    $naobrasilias=self::dados('naobrasilias');
    $brasilia=false;
    foreach($brasilias as $b)
      if(strpos(strtolower($end),$b)!=false)
        $brasilia=true;
    if($brasilia)
      foreach($naobrasilias as $b)
        if(strpos(strtolower($end),$b)!=false)
          $brasilia=false;
    return $brasilia;
  }

  static function buscaReversa($texto){
    $encontrar=substr($texto,-10);
    for($i=0;$i<10;$i++){
      if(is_numeric(substr($encontrar,$i,1))){
        return array(
            substr($texto,0,-10+$i),
            substr($texto,-10+$i)
            );
      }
    }
  }

  static function tiraNumeroFinal($endereco){
    $numeros=self::dados('numeros');
    foreach($numeros as $n)
      foreach(array(" $n"," $n ") as $N)
      if(substr($endereco,-strlen($N))==$N)
        return substr($endereco,0,-strlen($N));
    return $endereco;
  }

  static function separaNumeroComplemento($n){
    $semnumeros=self::dados('semnumeros');
    $n=self::endtrim($n);
    foreach($semnumeros as $sn){
      if($n==$sn)return array($n,'');
      if(substr($n,0,strlen($sn))==$sn)return array(substr($n,0,strlen($sn)),substr($n,strlen($sn)));
    }
    $q=preg_split('/\D/',$n);
    $pos=strlen($q[0]);
    return array(substr($n,0,$pos),substr($n,$pos));
  }

  static function brasiliaSeparaComplemento($end){
    $complementos=self::dados('complementos');
    foreach($complementos as $c)
      if($pos=strpos(strtolower($end),$c))
        return array(substr($end,0,$pos),substr($end,$pos));
    return array($end,'');
  }

  static function trataEndereco($end){
    $numeros=self::dados('numeros');
    $complementos=self::dados('complementos');
    if(self::ehBrasilia($end)){
      $numero='s/nº';
      list($endereco,$complemento)=self::brasiliaSeparaComplemento($end);
    }else{
      $endereco=$end;
      $numero='s/nº';
      $complemento='';
      $quebrado=preg_split('/[-,]/',$end);
      if(sizeof($quebrado)==3){ list($endereco,$numero,$complemento)=$quebrado;
      }elseif(sizeof($quebrado)==2){ list($endereco,$numero)=$quebrado;
      }else{
        list($endereco,$numero)=self::buscaReversa($end);
      }
      $endereco=self::tiraNumeroFinal($endereco);
      if($complemento=='')list($numero,$complemento)=self::separaNumeroComplemento($numero);
    }
    return array(self::endtrim($endereco),self::endtrim($numero),self::endtrim($complemento));
  }
}
