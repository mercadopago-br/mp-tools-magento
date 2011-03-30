<?
#
# Criado por Richard Barros / contato@richardbarros.com.br
# http://www.richardbarros.com.br
# 

	$excludeList_debugphp = array('GLOBALS', '_FILES', '_COOKIE', '_POST', '_GET', 'excludeList_debugphp', 'includeList_debugphp', 'i_debugphp', 'variaveis_debugphp', 'HTTP_POST_VARS', 'HTTP_GET_VARS', 'HTTP_SESSION_VARS', '_ENV', 'HTTP_ENV_VARS', 'HTTP_COOKIE_VARS', '_SERVER', 'HTTP_SERVER_VARS', 'HTTP_POST_FILES', '_REQUEST', 'chave_debugphp', 'chave2_debugphp', 'valor_debugphp', 'valor2_debugphp', 'j_debugphp', 'acor_debugphp', 'funcoes_debugphp');
 
	$includeList_debugphp = array('_POST', '_GET', '_COOKIE', '_FILES');
  
    $variaveis_debugphp = get_defined_vars();
	$funcoes_debugphp = get_defined_functions();
 ?>
<table style="background: #FFF; text-align: left; width: 97%; border-top: solid 10px #C54D3F; padding: 0px; margin: 35px 10px;" cellpadding="2" cellspacing="0">
<thead>
	<tr>
		<th colspan="2" style="width: 350px; font: 13pt 'Georgia'; text-align: center; color: #BFB9B2; padding-top: 30px;" valign="top">Debug v0.1 - richardbarros.com.br</th>
	</tr>
</thead>
<tbody>
 <?
	if (is_array($variaveis_debugphp)):
		//ksort($variaveis_debugphp);
		
		$j_debugphp = 0;
		foreach ($variaveis_debugphp as $chave_debugphp => $valor_debugphp)
		{	
			if ($j_debugphp%2 == 0)
				$acor_debugphp = "#FEFEFE";
			else
				$acor_debugphp = "#FBF9F6";

			if ((!is_array($valor_debugphp)) && (!in_array($chave_debugphp, $excludeList_debugphp))) {
				if (($chave == NULL) && ($i_debugphp == NULL)) { echo "<tr><td colspan=\"2\" style=\"background: #F6F3F0; border-bottom: solid 1px #CBA; padding-top: 10px; font: bold 10pt Georgia, Arial; color: #CBA\">Variáveis</td></tr>";
						 $i_debugphp = 1; }
				if (is_object($valor_debugphp)) { $valor_debugphp = "<em>Object</em>"; }
				echo "<tr style=\"background: $acor_debugphp;\"><td  valign='top' style=\"font: 10pt 'Verdana'; color: #008F7F; width: 20%;\"><span style=\"color: #0066CC\">$</span>$chave_debugphp</td><td style=\"font: 10pt 'Verdana'; color: #404040;\">$valor_debugphp</td></tr>";
				$j_debugphp++;
			}
			elseif (in_array($chave_debugphp, $includeList_debugphp))
			{
				if (count($valor_debugphp) > 0) {
					echo "<tr><td colspan=\"2\" style=\"background: #F6F3F0; border-bottom: solid 1px #CBA; padding-top: 10px; font: bold 10pt Georgia, Arial; color: #CBA\">$chave_debugphp</td></tr>";
						
					foreach($valor_debugphp as $chave2_debugphp => $valor2_debugphp)
					{
						echo "<tr style=\"background: $acor_debugphp;\"><td valign='top' style=\"font: 10pt 'Verdana'; color: #008F7F\"><span style=\"color: #0066CC\">$</span>$chave2_debugphp</td><td style=\"font: 10pt 'Verdana'; color: #404040;\">$valor2_debugphp</td></tr>";
							$j_debugphp++;
					}
				}
			}
		}
		if (count($funcoes_debugphp[user]) > 0)
		{
			echo "<tr><td colspan=\"2\" style=\"background: #F6F3F0; border-bottom: solid 1px #CBA; padding-top: 10px; font: bold 10pt Georgia, Arial; color: #CBA\">Funções</td></tr>";
			
			foreach ($funcoes_debugphp[user] as $chave_debugphp => $valor_debugphp)
			{
				if ($j_debugphp%2 == 0)
				$acor_debugphp = "#FEFEFE";
				else
				$acor_debugphp = "#FBF9F6";

				echo "<tr style=\"background: $acor_debugphp;\"><td style=\"font: 10pt 'Verdana'; color: #992200;\">$valor_debugphp();</td></tr>";
				$j_debugphp++;
			}
		}	
	endif;
  ?>
</tbody>
</table>