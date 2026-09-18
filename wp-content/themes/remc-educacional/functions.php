<?php
/**
 * REMC Educational Theme
 * Theme Name: REMC Educacional
 * Theme URI: https://github.com/yourusername/remc
 * Author: REMC Team
 * Description: Tema educacional para a Rede Educacional de Monitoramento Climático
 * Version: 0.1.0
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: remc-educacional
 * Tags: education, weather, blue, green
 *
 * This theme works with the REMC Core plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REMC_THEME_VERSION', '0.5.0' );
define( 'REMC_THEME_DIR', get_template_directory() );
define( 'REMC_THEME_URL', get_template_directory_uri() );

/**
 * Versão de um arquivo do tema, baseada na data de modificação.
 *
 * Evita que o navegador sirva CSS/JS antigos em cache após uma alteração.
 */
function remc_asset_ver( $relative_path ) {
	$caminho = get_template_directory() . '/' . ltrim( $relative_path, '/' );
	if ( file_exists( $caminho ) ) {
		return (string) filemtime( $caminho );
	}
	return REMC_THEME_VERSION;
}

/**
 * Enqueue styles and scripts
 */
function remc_theme_enqueue() {
	// Main stylesheet
	wp_enqueue_style( 'remc-style', get_stylesheet_uri(), array(), remc_asset_ver( 'style.css' ) );

	// Custom styles for charts and forms
	wp_enqueue_style( 'remc-charts', REMC_THEME_URL . '/assets/css/charts.css', array(), remc_asset_ver( 'assets/css/charts.css' ) );
	wp_enqueue_style( 'remc-forms', REMC_THEME_URL . '/assets/css/forms.css', array(), remc_asset_ver( 'assets/css/forms.css' ) );

	// JavaScript for interactive elements
	wp_enqueue_script( 'remc-scripts', REMC_THEME_URL . '/assets/js/scripts.js', array( 'jquery' ), remc_asset_ver( 'assets/js/scripts.js' ), true );

	// Interface: menu responsivo, carrossel de observações e clima.
	wp_enqueue_script( 'remc-ui', REMC_THEME_URL . '/assets/js/remc-ui.js', array(), remc_asset_ver( 'assets/js/remc-ui.js' ), true );

	// Localize script for AJAX
	wp_localize_script( 'remc-scripts', 'remc_ajax', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'remc-nonce' ),
	) );

	wp_localize_script( 'remc-ui', 'remc_ui', array(
		'ajax_url'      => admin_url( 'admin-ajax.php' ),
		'weather_url'   => esc_url_raw( rest_url( 'remc/v1/weather' ) ),
		'share_nonce'   => wp_create_nonce( 'remc_toggle_share' ),
		'logged_in'     => is_user_logged_in(),
		'strings'       => array(
			'menu_open'   => __( 'Abrir menu', 'remc-educacional' ),
			'menu_close'  => __( 'Fechar menu', 'remc-educacional' ),
			'previous'    => __( 'Anterior', 'remc-educacional' ),
			'next'        => __( 'Próximo', 'remc-educacional' ),
			'position'    => __( '%1$s de %2$s', 'remc-educacional' ),
			'shared'      => __( 'Compartilhado no Feed', 'remc-educacional' ),
			'share'       => __( 'Compartilhar no Feed', 'remc-educacional' ),
			'unshare'     => __( 'Remover do Feed', 'remc-educacional' ),
			'pending'     => __( 'Ainda não compartilhado', 'remc-educacional' ),
			'view_post'   => __( 'Ver publicação', 'remc-educacional' ),
			'working'     => __( 'Enviando…', 'remc-educacional' ),
			'loading'     => __( 'Carregando dados meteorológicos…', 'remc-educacional' ),
			'error'       => __( 'Não foi possível carregar os dados meteorológicos agora.', 'remc-educacional' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'remc_theme_enqueue' );

/**
 * Theme setup
 */
function remc_theme_setup() {
	// Add theme support
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
	
	// Register navigation menus
	register_nav_menus( array(
		'main' => __( 'Menu Principal', 'remc-educacional' ),
		'footer' => __( 'Rodapé', 'remc-educacional' ),
	) );
	
	// Set content width
	if ( ! isset( $content_width ) ) {
		$content_width = 800;
	}
}
add_action( 'after_setup_theme', 'remc_theme_setup' );

/**
 * Theme widgets
 */
function remc_theme_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar Principal', 'remc-educacional' ),
		'id'            => 'sidebar-1',
		'description'   => __( 'Widgets exibidos na sidebar principal.', 'remc-educacional' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'remc_theme_widgets_init' );

/**
 * Custom template tags
 */
if ( ! function_exists( 'remc_posted_on' ) ) :
function remc_posted_on() {
	$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
	}
	
	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( 'c' ) ),
		esc_html( get_the_modified_date() )
	);
	
	$posted_on = sprintf(
		_x( 'Publicado em %s', 'post date', 'remc-educacional' ),
		'<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
	);
	
	echo '<span class="posted-on">' . $posted_on . '</span>';
}
endif;

if ( ! function_exists( 'remc_posted_by' ) ) :
function remc_posted_by() {
	$byline = sprintf(
		_x( ' por %s', 'post author', 'remc-educacional' ),
		'<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
	);
	
	echo '<span class="byline"> ' . $byline . '</span>';
}
endif;

/**
 * Get user turmas
 */
function remc_get_user_turmas( $user_id = null ) {	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	
	$turma_ids = get_user_meta( $user_id, '_linked_turmas', true );
	
	if ( ! $turma_ids || ! is_array( $turma_ids ) ) {
		return array();
	}
	
	return $turma_ids;
}

/**
 * Get user's turma IDs for a specific role
 */
function remc_get_user_turmas_by_role( $role = 'aluno' ) {
	$args = array(
		'role__in' => array( $role ),
		'meta_query' => array(
			array(
				'key' => '_linked_turmas',
				'compare' => 'EXISTS',
			),
		),
	);
	
	$users = get_users( $args );
	$turmas = array();
	
	foreach ( $users as $user ) {
		$turma_ids = get_user_meta( $user->ID, '_linked_turmas', true );
		if ( $turma_ids && is_array( $turma_ids ) ) {
			$turmas = array_merge( $turmas, $turma_ids );
		}
	}
	
	return array_unique( $turmas );
}

/**
 * Template part for displaying posts
 */
function remc_entry_meta() {
	echo '<div class="entry-meta">';
	
	if ( 'post' === get_post_type() ) {
		remc_posted_on();
		remc_posted_by();
	}
	
	echo '</div>';
}

/**
 * Formata número no padrão pt-BR (vírgula decimal).
 */
function remc_fmt_num( $valor, $casas = 1 ) {
	if ( null === $valor || '' === $valor ) {
		return '';
	}
	return number_format( (float) $valor, $casas, ',', '.' );
}

/**
 * URL do perfil de um membro (compatível com BuddyPress 12+).
 */
function remc_member_url( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return '';
	}
	if ( function_exists( 'bp_members_get_user_url' ) ) {
		return bp_members_get_user_url( $user_id );
	}
	if ( function_exists( 'bp_core_get_user_domain' ) ) {
		return bp_core_get_user_domain( $user_id );
	}
	return get_author_posts_url( $user_id );
}

/**
 * Resolve itens dinâmicos do menu principal e oculta o que não se aplica.
 *
 * Itens com URL "#remc-<regra>" são resolvidos aqui:
 *   #remc-minha-timeline  -> /members/<login>/activity/  (só logado)
 *   #remc-meu-perfil      -> /members/<login>/            (só logado)
 *   #remc-painel-aluno    -> visível para aluno/professor/admin
 *   #remc-painel-professor-> visível para professor/admin
 *   #remc-logado          -> visível para qualquer usuário logado
 */
function remc_filter_nav_menu_objects( $items, $args = null ) {
	if ( is_admin() ) {
		return $items;
	}

	$logged_in = is_user_logged_in();
	$user      = wp_get_current_user();
	$roles     = (array) $user->roles;
	$is_prof   = $logged_in && ( in_array( 'professor', $roles, true ) || in_array( 'administrator', $roles, true ) );
	$is_aluno  = $logged_in && in_array( 'aluno', $roles, true );

	$member_url = $logged_in ? remc_member_url( $user->ID ) : '';

	$keep = array();
	foreach ( $items as $item ) {
		// Item "pai" de submenu (URL "#"): mantido e podado depois, se ficar sem filhos.
		if ( '#' === trim( (string) $item->url ) ) {
			$keep[] = $item;
			continue;
		}

		if ( 0 !== strpos( (string) $item->url, '#remc-' ) ) {
			$keep[] = $item;
			continue;
		}

		$rule = substr( $item->url, strlen( '#remc-' ) );

		switch ( $rule ) {
			case 'minha-timeline':
				if ( ! $logged_in ) {
					continue 2;
				}
				// Nesta versão do BuddyPress a página de atividade do membro
				// redireciona para o perfil; o diretório com escopo "just-me" é
				// o caminho que funciona para ver a própria atividade.
				$base = '';
				if ( function_exists( 'bp_get_activity_directory_url' ) ) {
					$base = bp_get_activity_directory_url();
				} elseif ( function_exists( 'bp_get_activity_directory_permalink' ) ) {
					$base = bp_get_activity_directory_permalink();
				}
				if ( ! $base ) {
					$base = home_url( '/activity/' );
				}
				$item->url = add_query_arg( 'scope', 'just-me', $base );
				break;

			case 'meu-perfil':
				if ( ! $logged_in || ! $member_url ) {
					continue 2;
				}
				$item->url = $member_url;
				break;

			case 'painel-aluno':
				if ( ! $is_aluno && ! $is_prof ) {
					continue 2;
				}
				$url = get_option( 'remc_pagina_painel_aluno' );
				if ( ! $url ) {
					continue 2;
				}
				$item->url = $url;
				break;

			case 'painel-professor':
				if ( ! $is_prof ) {
					continue 2;
				}
				$url = get_option( 'remc_pagina_painel_professor' );
				if ( ! $url ) {
					continue 2;
				}
				$item->url = $url;
				break;

			case 'logado':
				if ( ! $logged_in ) {
					continue 2;
				}
				$item->url = admin_url();
				break;

			default:
				continue 2;
		}

		$keep[] = $item;
	}

	// Poda: remove item "pai" que ficou sem nenhum filho visível.
	$com_filho = array();
	foreach ( $keep as $k ) {
		if ( ! empty( $k->menu_item_parent ) ) {
			$com_filho[ (int) $k->menu_item_parent ] = true;
		}
	}

	$final = array();
	foreach ( $keep as $k ) {
		if ( '#' === trim( (string) $k->url ) && empty( $com_filho[ (int) $k->ID ] ) ) {
			continue;
		}
		$final[] = $k;
	}

	return $final;
}
add_filter( 'wp_nav_menu_objects', 'remc_filter_nav_menu_objects', 10, 2 );

/**
 * Controle de status de compartilhamento de uma observação.
 *
 * Renderizado no servidor (funciona sem JavaScript) e recriado pelo JS após
 * o compartilhamento via AJAX. Não depende apenas de cor: traz ícone + texto.
 */
function remc_status_control( $obs_id, $shared, $permalink = '' ) {
	$share_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=remc_share_observation&observation=' . $obs_id ),
		'remc_share_observation_' . $obs_id
	);
	$unshare_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=remc_unshare_observation&observation=' . $obs_id ),
		'remc_unshare_observation_' . $obs_id
	);

	ob_start();
	if ( $shared ) {
		?>
		<p class="remc-status remc-status--shared">
			<span class="remc-status__icon" aria-hidden="true">✔</span>
			<span class="remc-status__label"><?php esc_html_e( 'Compartilhado no Feed', 'remc-educacional' ); ?></span>
		</p>
		<p class="remc-status__actions">
			<a class="button remc-shared remc-share-toggle"
				data-obs="<?php echo esc_attr( $obs_id ); ?>"
				data-state="unshare"
				href="<?php echo esc_url( $unshare_url ); ?>"
				aria-label="<?php esc_attr_e( 'Remover esta observação do Feed', 'remc-educacional' ); ?>">
				<?php esc_html_e( 'Remover do Feed', 'remc-educacional' ); ?>
			</a>
			<?php if ( $permalink ) : ?>
				<a class="button button-secondary" href="<?php echo esc_url( $permalink ); ?>">
					<?php esc_html_e( 'Ver publicação', 'remc-educacional' ); ?>
				</a>
			<?php endif; ?>
		</p>
		<?php
	} else {
		?>
		<p class="remc-status remc-status--pending">
			<span class="remc-status__icon" aria-hidden="true">○</span>
			<span class="remc-status__label"><?php esc_html_e( 'Ainda não compartilhado', 'remc-educacional' ); ?></span>
		</p>
		<p class="remc-status__actions">
			<a class="button button-primary remc-share-toggle"
				data-obs="<?php echo esc_attr( $obs_id ); ?>"
				data-state="share"
				href="<?php echo esc_url( $share_url ); ?>">
				<?php esc_html_e( 'Compartilhar no Feed', 'remc-educacional' ); ?>
			</a>
		</p>
		<?php
	}
	return ob_get_clean();
}

/**
 * Bloco de compartilhamento em formato de carrossel: uma observação por vez.
 *
 * Usado no Painel do Aluno e na página do Feed (no lugar do antigo formulário
 * de texto livre). Reutiliza o mesmo mecanismo de publicação do remc-core.
 *
 * @param array $args {
 *     @type bool $mostrar_titulo Exibe o cabeçalho do bloco. Padrão true.
 * }
 */
function remc_share_panel( $args = array() ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$args = wp_parse_args( $args, array( 'mostrar_titulo' => true ) );
	$user = wp_get_current_user();

	if ( $args['mostrar_titulo'] ) {
		echo '<h2>' . esc_html__( 'Minhas observações no Feed', 'remc-educacional' ) . '</h2>';
	}
	?>
	<p class="description">
		<?php esc_html_e( 'Navegue pelas suas observações aprovadas e compartilhe, uma por vez, as que quiser no Feed. A prévia mostra exatamente o que ficará público: sem notas, sem e-mail, sem nome completo e sem endereço residencial.', 'remc-educacional' ); ?>
	</p>
	<?php

	$aprovadas = get_posts( array(
		'post_type'      => 'remc_observacao',
		'post_status'    => 'publish',
		'author'         => $user->ID,
		'posts_per_page' => 50,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => array(
			array( 'key' => '_status', 'value' => 'aprovado' ),
		),
	) );

	if ( empty( $aprovadas ) ) {
		echo '<p>' . esc_html__( 'Você ainda não tem observações aprovadas para compartilhar.', 'remc-educacional' ) . '</p>';
		return;
	}

	$total = count( $aprovadas );
	?>
	<div class="remc-carousel" data-remc-carousel aria-roledescription="<?php esc_attr_e( 'carrossel de observações', 'remc-educacional' ); ?>">
		<div class="remc-carousel__viewport">
			<?php
			$indice = 0;
			foreach ( $aprovadas as $obs ) {
				$indice++;
				$activity_id = (int) get_post_meta( $obs->ID, '_shared_activity_id', true );
				$shared      = (bool) $activity_id;
				$permalink   = $shared && class_exists( 'Remc_Activity' ) ? Remc_Activity::activity_permalink( $activity_id ) : '';
				$facts       = class_exists( 'Remc_Activity' ) ? Remc_Activity::public_facts( $obs->ID ) : array();
				$previa      = class_exists( 'Remc_Activity' ) ? Remc_Activity::build_public_content( $obs->ID ) : '';
				$data        = get_post_meta( $obs->ID, '_observation_date', true );
				?>
				<article class="card remc-slide<?php echo 1 === $indice ? ' is-active' : ''; ?>"
					data-remc-slide
					data-obs="<?php echo esc_attr( $obs->ID ); ?>"
					role="group"
					aria-roledescription="<?php esc_attr_e( 'observação', 'remc-educacional' ); ?>"
					aria-label="<?php echo esc_attr( sprintf( __( 'Observação %1$d de %2$d', 'remc-educacional' ), $indice, $total ) ); ?>"
					<?php echo 1 === $indice ? '' : 'aria-hidden="true"'; ?>>
					<header class="remc-slide__head">
						<h3 class="remc-slide__title"><?php echo esc_html( get_the_title( $obs ) ); ?></h3>
						<?php if ( $data ) : ?>
							<p class="remc-slide__when">
								<span aria-hidden="true">ðŸ—“ï¸</span>
								<?php echo esc_html( sprintf( __( 'Observado em %s', 'remc-educacional' ), mysql2date( 'd/m/Y H:i', $data ) ) ); ?>
							</p>
						<?php endif; ?>
					</header>

					<?php if ( ! empty( $facts ) ) : ?>
						<ul class="remc-facts">
							<?php foreach ( $facts as $fato ) : ?>
								<li class="remc-facts__item">
									<span class="remc-facts__icon" aria-hidden="true"><?php echo esc_html( $fato['icon'] ); ?></span>
									<span class="remc-facts__label"><?php echo esc_html( $fato['label'] ); ?>:</span>
									<span class="remc-facts__value"><?php echo esc_html( $fato['value'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<details class="remc-slide__preview">
						<summary><?php esc_html_e( 'Ver prévia pública', 'remc-educacional' ); ?></summary>
						<div class="feed-preview">
							<?php
							if ( $previa ) {
								echo wp_kses_post( $previa );
							} else {
								echo '<p>' . esc_html__( 'Este registro não tem variáveis reconhecidas. Peça ao professor para devolver a observação e corrija os campos do formulário antes de compartilhar.', 'remc-educacional' ) . '</p>';
							}
							?>
						</div>
					</details>

					<div class="remc-slide__status" data-remc-status aria-live="polite">
						<?php
						if ( '' === $previa ) {
							?>
							<p class="remc-status remc-status--empty">
								<span class="remc-status__icon" aria-hidden="true">!</span>
								<span class="remc-status__label"><?php esc_html_e( 'Sem dados reconhecidos para compartilhar', 'remc-educacional' ); ?></span>
							</p>
							<?php
						} else {
							echo remc_status_control( $obs->ID, $shared, $permalink ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				</article>
				<?php
			}
			?>
		</div>

		<div class="remc-carousel__nav">
			<button type="button" class="button button-secondary remc-prev" data-remc-prev>
				<span aria-hidden="true">←</span> <?php esc_html_e( 'Anterior', 'remc-educacional' ); ?>
			</button>
			<span class="remc-carousel__pos" data-remc-pos aria-live="polite">
				<?php echo esc_html( sprintf( __( '1 de %d', 'remc-educacional' ), $total ) ); ?>
			</span>
			<button type="button" class="button button-secondary remc-next" data-remc-next>
				<?php esc_html_e( 'Próximo', 'remc-educacional' ); ?> <span aria-hidden="true">→</span>
			</button>
		</div>
	</div>
	<?php
}

/**
 * Mostra o bloco de compartilhamento no lugar do formulário de texto livre.
 *
 * O template do BuddyPress (activity/post-form.php) é sobrescrito pelo tema e
 * chama esta função.
 */
function remc_activity_share_panel() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	echo '<div class="remc-share-panel">';
	remc_share_panel();
	echo '</div>';
}

/**
 * Aviso global do resultado do compartilhamento.
 *
 * Renderizado no início do <body> para que o retorno apareça em QUALQUER
 * página (a ação redireciona de volta para a página de origem).
 */
function remc_feed_notice() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$feed = isset( $_GET['remc_feed'] ) ? sanitize_key( wp_unslash( $_GET['remc_feed'] ) ) : '';
	if ( ! $feed ) {
		return;
	}

	$msgs = array(
		'shared'   => __( 'Dados compartilhados no feed da comunidade.', 'remc-educacional' ),
		'unshared' => __( 'Dados removidos do feed.', 'remc-educacional' ),
		'error'    => __( 'Não foi possível compartilhar agora. Tente novamente.', 'remc-educacional' ),
	);
	if ( ! isset( $msgs[ $feed ] ) ) {
		return;
	}

	printf(
		'<div class="container"><div class="form-status %s" role="status">%s</div></div>',
		esc_attr( 'error' === $feed ? 'error' : 'success' ),
		esc_html( $msgs[ $feed ] )
	);
}
add_action( 'wp_body_open', 'remc_feed_notice' );
