<?php
/**
 * REMC: substitui o formulário "O que há de novo?" do BuddyPress.
 *
 * O feed público da REMC não aceita texto livre: ele carrega apenas dados
 * meteorológicos observados e aprovados, compartilhados por opção da autora ou
 * do autor. Este override (buddypress/activity/post-form.php) mantém a
 * privacidade e oferece o caminho correto de compartilhamento.
 *
 * Não alteramos arquivos do núcleo nem do BuddyPress.
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'remc_activity_share_panel' ) ) {
	remc_activity_share_panel();
}
