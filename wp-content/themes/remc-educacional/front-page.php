<?php
/**
 * Template Name: Página Inicial
 * The front page template file
 */

get_header();
?>

<main id="principal" class="main-content" role="main">
	<header class="hero">
		<h1>REMC - Rede Educacional de Monitoramento Climático</h1>
		<p>Ciência cidadã, coleta de dados e oficinas práticas para alunos estação meteorológica.</p>
	</header>

	<main class="main-content">
		<section class="intro">
			<h2>Sobre o Projeto</h2>
			<p>A REMC é uma plataforma educacional que permite que alunos observem e registrem condições meteorológicas em casa e na escola, aprendendo colaborativamente com orientação de professores.</p>
			
			<h3>Principais Funcionalidades</h3>
			<ul>
				<li>Registro de observações meteorológicas (chuva, vento, temperatura, nuvens)</li>
				<li>Biblioteca de oficinas com instrumentos caseiros</li>
				<li>Fluxo de revisão docente para dados e relatórios</li>
				<li>Consultas, gráficos e exportação de dados</li>
			</ul>
		</section>

		<?php if ( has_nav_menu( 'main' ) ) : ?>
		<nav class="nav-secondary">
			<?php
				wp_nav_menu( array(
					'theme_location' => 'main',
					'menu_class' => 'nav-menu',
				) );
			?>
		</nav>
		<?php endif; ?>

		<section class="featured-content">
			<h2>Conteúdo em Destaque</h2>
			
			<?php
			$tutorials = new WP_Query( array(
				'post_type' => 'remc_tutorial',
				'post_status' => 'publish',
				'posts_per_page' => 3,
				'orderby' => 'date',
				'order' => 'DESC',
			) );
			
			if ( $tutorials->have_posts() ) :
				echo '<div class="tutorials-grid">';
				while ( $tutorials->have_posts() ) : $tutorials->the_post();
					?>
					<article class="tutorial-card card">
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php the_excerpt(); ?></p>
						<a href="<?php the_permalink(); ?>" class="button">Ver detalhes</a>
					</article>
					<?php
				endwhile;
				echo '</div>';
				wp_reset_postdata();
			endif;
			?>
		</section>

		<?php
		if ( function_exists( 'is_buddypress' ) && is_buddypress() ) :
			?>
			<section class="community">
				<h2>Turmas e Comunidade</h2>
				<p>Participe das turmas da REMC e compartilhe suas observações.</p>
			</section>
			<?php
	endif;
	?>
</main>

<?php
get_footer();
