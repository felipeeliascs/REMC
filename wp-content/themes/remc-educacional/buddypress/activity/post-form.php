<?php
/**
 * REMC: o feed publico carrega apenas dados meteorologicos compartilhados
 * (componente "remc" / tipo "remc_shared_observation").
 *
 * Este arquivo sobrescreve intencionalmente o template do BuddyPress
 * (buddypress/activity/post-form.php) para remover o formulario de texto livre
 * ("O que ha de novo?"). Sem ele, qualquer pessoa logada poderia publicar
 * texto arbitrario no feed publico, o que contraria o requisito de expor
 * somente dados observados e aprovados.
 *
 * Nao alteramos arquivos do nucleo nem do BuddyPress: e um override de tema.
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Intencionalmente vazio: o feed e alimentado apenas pelo remc-core.
