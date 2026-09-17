<?php
/**
 * Home - "Tempo agora em Cachoeira Paulista" + pequena previsão.
 *
 * Os dados vêm do serviço Remc_Weather (Open-Meteo), com cache. A falha da API
 * não impede o restante da página de funcionar.
 *
 * @package remc-educacional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$remc_clima = null;
$remc_erro  = '';

if ( class_exists( 'Remc_Weather' ) ) {
	$remc_clima = Remc_Weather::instance()->get_weather();
	if ( is_wp_error( $remc_clima ) ) {
		$remc_erro  = $remc_clima->get_error_message();
		$remc_clima = null;
	}
} else {
	$remc_erro = __( 'Serviço meteorológico indisponível.', 'remc-educacional' );
}

$remc_feed_url = '';
if ( function_exists( 'bp_get_activity_directory_url' ) ) {
	$remc_feed_url = bp_get_activity_directory_url();
}
if ( ! $remc_feed_url ) {
	$remc_feed_url = home_url( '/activity/' );
}
?>
<section class="home-weather" id="tempo-agora" data-remc-weather aria-labelledby="home-weather-title">
	<div class="home-weather__head">
		<h2 id="home-weather-title"><?php esc_html_e( 'Tempo agora em Cachoeira Paulista', 'remc-educacional' ); ?></h2>
		<button type="button" class="button button-secondary home-weather__refresh" data-remc-weather-refresh>
			<?php esc_html_e( 'Atualizar', 'remc-educacional' ); ?>
		</button>
	</div>

	<p class="form-status info" data-remc-weather-msg hidden></p>

	<?php if ( $remc_clima ) : ?>
		<?php $remc_cur = $remc_clima['current']; ?>
		<div class="card weather-card">
			<div class="weather-card__main">
				<span class="weather-card__icon" data-w-icon aria-hidden="true"><?php echo esc_html( $remc_cur['icon'] ); ?></span>
				<div>
					<p class="weather-card__place">
						<?php
						printf(
							/* translators: 1: cidade, 2: UF */
							esc_html__( '%1$s — %2$s', 'remc-educacional' ),
							esc_html( $remc_clima['place']['name'] ),
							esc_html( $remc_clima['place']['admin1'] )
						);
						?>
					</p>
					<p class="weather-card__temp" data-w-temp><?php echo esc_html( remc_fmt_num( $remc_cur['temperature'] ) . ' °C' ); ?></p>
					<p class="weather-card__label" data-w-label><?php echo esc_html( $remc_cur['label'] ); ?></p>
				</div>
			</div>
			<ul class="weather-card__facts">
				<li><span aria-hidden="true">🌡️</span> <?php esc_html_e( 'Sensação:', 'remc-educacional' ); ?> <span data-w-apparent><?php echo esc_html( remc_fmt_num( $remc_cur['apparent'] ) . ' °C' ); ?></span></li>
				<li><span aria-hidden="true">💧</span> <?php esc_html_e( 'Umidade:', 'remc-educacional' ); ?> <span data-w-humidity><?php echo esc_html( $remc_cur['humidity'] . '%' ); ?></span></li>
				<li><span aria-hidden="true">💨</span> <?php esc_html_e( 'Vento:', 'remc-educacional' ); ?> <span data-w-wind><?php echo esc_html( remc_fmt_num( $remc_cur['wind'] ) . ' km/h' ); ?></span></li>
				<li><span aria-hidden="true">🌧️</span> <?php esc_html_e( 'Precipitação:', 'remc-educacional' ); ?> <span data-w-precip><?php echo esc_html( remc_fmt_num( $remc_cur['precipitation'], 2 ) . ' mm' ); ?></span></li>
			</ul>
			<?php if ( ! empty( $remc_clima['daily'][0] ) ) : ?>
				<p class="weather-card__range">
					<?php esc_html_e( 'Máx.', 'remc-educacional' ); ?>
					<span data-w-max><?php echo esc_html( remc_fmt_num( $remc_clima['daily'][0]['max'] ) . ' °C' ); ?></span>
					|
					<?php esc_html_e( 'Mín.', 'remc-educacional' ); ?>
					<span data-w-min><?php echo esc_html( remc_fmt_num( $remc_clima['daily'][0]['min'] ) . ' °C' ); ?></span>
				</p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $remc_clima['daily'] ) ) : ?>
			<div class="remc-forecast">
				<h3><?php esc_html_e( 'Próximos dias', 'remc-educacional' ); ?></h3>
				<ul class="remc-forecast__list" data-w-daily>
					<?php
					foreach ( array_slice( $remc_clima['daily'], 0, 3 ) as $remc_dia ) :
						$remc_ts = strtotime( $remc_dia['data'] );
						?>
						<li class="remc-forecast__item">
							<span class="remc-forecast__day"><?php echo esc_html( $remc_ts ? wp_date( 'D/m', $remc_ts ) : $remc_dia['data'] ); ?></span>
							<span class="remc-forecast__temp"><?php echo esc_html( remc_fmt_num( $remc_dia['max'] ) . '° / ' . remc_fmt_num( $remc_dia['min'] ) . '°' ); ?></span>
							<span class="remc-forecast__rain" title="<?php esc_attr_e( 'Chance de chuva', 'remc-educacional' ); ?>">
								<span aria-hidden="true">🌧️</span> <?php echo esc_html( $remc_dia['chuva'] . '%' ); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<p class="home-weather__meta">
			<?php
			printf(
				/* translators: %s: hora da última atualização */
				esc_html__( 'Dados do Open-Meteo. Atualizado em %s.', 'remc-educacional' ),
				esc_html( mysql2date( 'd/m/Y H:i', $remc_clima['updated_at'] ) )
			);
			?>
			<a href="<?php echo esc_url( $remc_feed_url ); ?>"><?php esc_html_e( 'Compare com as observações da rede', 'remc-educacional' ); ?></a>
		</p>
	<?php else : ?>
		<div class="card">
			<p class="form-status error" data-remc-weather-msg>
				<?php echo esc_html( $remc_erro ? $remc_erro : __( 'Não foi possível carregar os dados meteorológicos agora.', 'remc-educacional' ) ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Você ainda pode usar o REMC normalmente: registre suas observações e veja o Feed da turma.', 'remc-educacional' ); ?>
			</p>
		</div>
	<?php endif; ?>
</section>
