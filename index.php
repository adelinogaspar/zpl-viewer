<?php
# urano:8000/gaspar/zebra
function &identifica_comando_zpl
( $str
, &$comando
, &$parametros
) {
  $v_str = trim($str);
  $parametros = array();
  
  if (substr($v_str, 0, 1) == 'A') {
    $comando = substr($v_str, 0, 1);
    $parametros = explode(',', substr($v_str, 4));
  } elseif (!(stripos($v_str, 'XA') === false)) {
    // começo de etiqueta
    $comando = substr($v_str, 0, 2);
  } elseif (!(stripos($v_str, 'LH') === false)) {
    // label home
    $comando = substr($v_str, 0, 2);
    $parametros = explode(',', substr($v_str, 2));
  } elseif (!(stripos($v_str, 'FX') === false)) {
    // comentários de zpl
    $comando = substr($v_str, 0, 2);
    $parametros = substr($v_str, 2);
  } elseif (!(stripos($v_str, 'FO') === false)) {
    // field origin (posicionamento de cursor)
    $comando = substr($v_str, 0, 2);
    $parametros = explode(',', substr($v_str, 2));
  } elseif (!(stripos($v_str, 'GB') === false)) {
    // graphic box (retângulo)
    $comando = substr($v_str, 0, 2);
    $parametros = explode(',', substr($v_str, 2));
  } elseif (!(stripos($v_str, 'FD') === false)) {
    // field data (texto da etiqueta)
    $comando = substr($v_str, 0, 2);
    $parametros[0] = substr($v_str, 2);
  } elseif (!(stripos($v_str, 'FW') === false)) {
    // field orientation (orientação do texto)
    $comando = substr($v_str, 0, 2);
    $parametros[0] = substr($v_str, 2);
  } elseif (!(stripos($v_str, 'FS') === false)) {
    // field separator (final de comando)
    $comando = substr($v_str, 0, 2);
  } elseif (!(stripos($v_str, 'IS') === false)) {
    // image save (salva o conteúdo na memória da zebra)
    $comando = substr($v_str, 0, 2);
    $parametros = explode(',', substr($v_str, 4));
  } elseif (!(stripos($v_str, 'IM') === false)) {
    // image save (salva o conteúdo na memória da zebra)
    $comando = substr($v_str, 0, 2);
    $parametros[0] = substr($v_str, 4);
  } else {
    $comando = '*** NAO IDENTIFICADO *** '.substr($v_str, 0, 2);
  }
}


function box($x1, $y1, $x2, $y2, $tam) {
  return "
    var rect = new
        Kinetic.Rect (
        { x: $x1
        , y: $y1
        , width: $x2
        , height: $y2
        //, fill: 'red'
        , stroke: 'black'
        , strokeWidth: ($tam/2)
        });

        layer.add(rect);
  ";
}

function text($x, $y, $str, $altura=10, $orientacao=null, $largura, $pos_x_global=0, $pos_y_global=0, $cor_texto='green') {
  $v_orientacao_js = 'roda_0';
  $ajuste_x = 0;
  if ($orientacao=='R') {
    $v_orientacao_js = 'roda_90';
  } elseif ($orientacao=='N') {
    $v_orientacao_js = 'roda_0';
    // ajuste no posicionamento do texto quando a orientação é horizontal
    $ajuste_x = -25;
  }

  // calcula a posição do texto
  $x2 = $x+12+$pos_x_global+$ajuste_x;
  $y2 = $y-5;
  //echo "l=$largura t=$largura\n";
  // ajusta a proposrção entre altura e largura do texto
  if ($altura < $largura) {
    $proporcao = round($altura/$largura,2)+0.4;
  } elseif ($largura < $altura) {
    $proporcao = round($largura/$altura,2);
  } else {
    $proporcao = 1;
  }
  
  return "
    var txtCampo = new Kinetic.Text (
        { x: $x2
        , y: $y2
        , text: '$str'
        , fontSize: $altura
        /*, fontFamily: 'Calibri'*/
        /*, fontFamily: 'Helvetica'*/
        , fontFamily: 'Arial'
        , fontStyle: 'bold'
        , fill: '$cor_texto'
        , rotation: $v_orientacao_js
        });
        
        txtCampo.setScale($proporcao, 1);
        layer.add(txtCampo);
  ";
}

$parametros = array_merge($_POST, $_GET);

$str_zpl_parametro = "";
$str_zpl_parametro = $parametros['zpl'];

// troca os comandos "~" pelo separador padrão "^"
$str_zpl_parametro = str_replace('~', '^', $str_zpl_parametro);

$comandos_zpl = explode('^', $str_zpl_parametro);

/* define as variáveis globais da etiqueta */
$pos_x = 0;
$pos_y = 0;
$label_home_x = 0;
$label_home_y = 0;
$orientacao = 'N';
$js = '';
$zpl_formatado = '';
$separador = '';
$altura_fonte = 10;
$largura_fonte = 10;
$cor_texto = 'blue';

foreach($comandos_zpl as $zpl) {
  if (!is_null($zpl)) {
    // separa os comandos e parâmetros
    identifica_comando_zpl($zpl, $cmd, $param);

    if ($cmd == 'FO') {
      // guarda a posição atual do cursor na etiqueta
      if (count($param) == 2) {
        $pos_x = $param[0];
        $pos_y = $param[1];
      } else {
        // esse é um ajuste que deve ser feito por causa de um código zpl gerado erroneamente pela pc_print
        $pos_x = $param[0];
        $pos_y = $param[2];
      }
    } elseif ($cmd == 'FW') {
      $orientacao = $param[0];
    } elseif ($cmd == 'GB') {
      // GB: cria um retângulo
      $js .= box($pos_x, $pos_y, $param[0], $param[1], $param[2]);
    } elseif ($cmd == 'FD') {
      // FD: cria um campo de texto
      $js .= text($pos_x, $pos_y, $param[0], $altura_fonte, $orientacao, $largura_fonte, $label_home_x, $label_home_y, $cor_texto);
    } elseif ($cmd == 'A') {
      // A: seta os parâmetros de fonte
      $altura_fonte = $param[0];
      $largura_fonte = $param[1];
    } elseif ($cmd == 'LH') {
      // LH: label home - posição relativa de todos os objetos na etiqueta
      $label_home_x = $param[0];
      $label_home_y = $param[1];
    } elseif ($cmd == 'IS') {
      $dir_base = dirname(__FILE__);
      $nome_arquivo = $param[0];
      $bytes = @file_put_contents($dir_base."/$nome_arquivo", $js);
      
      if ($bytes > 0) {
        $js .= "alert('arquivo $nome_arquivo escrito... bytes=$bytes');";        
      } else {
        $js .= "alert('erro ao gerar o arquivo $dir_base/$nome_arquivo');";
      }
    } elseif ($cmd == 'IM') {
      $cor_texto = 'green';
      // IM: carrega uma imagem da memória da zebra para a etiqueta atual
      $dir_base = dirname(__FILE__);
      $nome_arquivo = $param[0];
      if ($str_arquivo = @file_get_contents($dir_base."/$nome_arquivo")) {
        //$js .= "alert('carregou o arquivo $dir_base/$nome_arquivo');";
        $js = $str_arquivo . $js;
      } else {
        $js .= "alert('erro ao carregar o arquivo $dir_base/$nome_arquivo');";
      }      
    }

    $zpl_formatado .= "$separador\"".trim($zpl)."\\n\"";
    $separador = "+\n";
  } //*/
}

// faz o output do javascript que monta o canvas da etiqueta
$js .= "\n$(\"#txtZebraFormatado\").val($zpl_formatado);";
echo $js;