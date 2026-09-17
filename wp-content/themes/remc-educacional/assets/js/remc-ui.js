/**
 * REMC - Interface (sem dependências).
 *
 * - menu responsivo (abrir/fechar e submenu);
 * - carrossel de observações (uma por vez) com estado de compartilhamento;
 * - compartilhar/remover do Feed via AJAX, com fallback por link (sem JS);
 * - atualização do card meteorológico com estado de carregamento/erro.
 *
 * @package remc-educacional
 */
( function () {
	'use strict';

	var cfg = window.remc_ui || {};
	var strings = cfg.strings || {};

	function txt( key, fallback ) {
		return strings[ key ] || fallback;
	}

	/* ------------------------------------------------------------------ */
	/* Menu responsivo                                                     */
	/* ------------------------------------------------------------------ */
	function initMenu() {
		var nav = document.querySelector( '.nav-main' );
		if ( ! nav ) {
			return;
		}

		var toggle = nav.querySelector( '.menu-toggle' );
		if ( toggle ) {
			toggle.addEventListener( 'click', function () {
				var aberto = nav.classList.toggle( 'is-open' );
				toggle.setAttribute( 'aria-expanded', aberto ? 'true' : 'false' );
				var rotulo = toggle.querySelector( '.menu-toggle__text' );
				if ( rotulo ) {
					rotulo.textContent = aberto ? txt( 'menu_close', 'Fechar menu' ) : txt( 'menu_open', 'Menu' );
				}
			} );
		}

		// Submenus: no toque/teclado, o item pai abre/fecha em vez de navegar.
		nav.querySelectorAll( '.menu-item-has-children > a' ).forEach( function ( link ) {
			var href = ( link.getAttribute( 'href' ) || '' ).trim();
			if ( '#' !== href && '' !== href ) {
				return;
			}
			link.setAttribute( 'role', 'button' );
			link.setAttribute( 'aria-expanded', 'false' );
			link.addEventListener( 'click', function ( evento ) {
				evento.preventDefault();
				var li = link.parentNode;
				var aberto = li.classList.toggle( 'is-open' );
				link.setAttribute( 'aria-expanded', aberto ? 'true' : 'false' );
			} );
		} );

		// Fecha o menu ao clicar fora (telas pequenas).
		document.addEventListener( 'click', function ( evento ) {
			if ( ! nav.classList.contains( 'is-open' ) ) {
				return;
			}
			if ( nav.contains( evento.target ) ) {
				return;
			}
			nav.classList.remove( 'is-open' );
			if ( toggle ) {
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Carrossel de observações                                            */
	/* ------------------------------------------------------------------ */
	function initCarousel( root ) {
		var slides = Array.prototype.slice.call( root.querySelectorAll( '[data-remc-slide]' ) );
		if ( slides.length < 2 ) {
			return;
		}

		var posEl = root.querySelector( '[data-remc-pos]' );
		var prev = root.querySelector( '[data-remc-prev]' );
		var next = root.querySelector( '[data-remc-next]' );
		var atual = 0;

		function rotulo( atualIdx ) {
			var padrao = txt( 'position', '%1$s de %2$s' );
			return padrao.replace( '%1$s', atualIdx + 1 ).replace( '%2$s', slides.length );
		}

		function render() {
			slides.forEach( function ( slide, idx ) {
				var ativo = idx === atual;
				slide.classList.toggle( 'is-active', ativo );
				slide.setAttribute( 'aria-hidden', ativo ? 'false' : 'true' );
			} );
			if ( posEl ) {
				posEl.textContent = rotulo( atual );
			}
			if ( prev ) {
				prev.disabled = 0 === atual;
				prev.setAttribute( 'aria-disabled', prev.disabled ? 'true' : 'false' );
			}
			if ( next ) {
				next.disabled = atual === slides.length - 1;
				next.setAttribute( 'aria-disabled', next.disabled ? 'true' : 'false' );
			}
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				if ( atual > 0 ) {
					atual--;
					render();
				}
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				if ( atual < slides.length - 1 ) {
					atual++;
					render();
				}
			} );
		}

		root.addEventListener( 'keydown', function ( evento ) {
			if ( 'ArrowLeft' === evento.key && atual > 0 ) {
				atual--;
				render();
			} else if ( 'ArrowRight' === evento.key && atual < slides.length - 1 ) {
				atual++;
				render();
			}
		} );

		render();
	}

	function initCarousels() {
		document.querySelectorAll( '[data-remc-carousel]' ).forEach( initCarousel );
	}

	/* ------------------------------------------------------------------ */
	/* Compartilhar no Feed (AJAX)                                         */
	/* ------------------------------------------------------------------ */
	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( text ) {
			node.textContent = text;
		}
		return node;
	}

	function buildStatus( data ) {
		var frag = document.createDocumentFragment();
		var compartilhado = !! data.shared;

		var status = el( 'p', 'remc-status remc-status--' + ( compartilhado ? 'shared' : 'pending' ) );
		var icone = el( 'span', 'remc-status__icon', compartilhado ? '✓' : '○' );
		icone.setAttribute( 'aria-hidden', 'true' );
		status.appendChild( icone );
		status.appendChild( el( 'span', 'remc-status__label', compartilhado ? txt( 'shared', 'Compartilhado no Feed' ) : txt( 'pending', 'Ainda não compartilhado' ) ) );
		frag.appendChild( status );

		var acoes = el( 'p', 'remc-status__actions' );
		var botao = el( 'button', 'button ' + ( compartilhado ? 'remc-shared' : 'button-primary' ) + ' remc-share-toggle' );
		botao.type = 'button';
		botao.setAttribute( 'data-obs', String( data.observation ) );
		botao.setAttribute( 'data-state', compartilhado ? 'unshare' : 'share' );
		botao.textContent = compartilhado ? txt( 'unshare', 'Remover do Feed' ) : txt( 'share', 'Compartilhar no Feed' );
		acoes.appendChild( botao );

		if ( compartilhado && data.permalink ) {
			var ver = el( 'a', 'button button-secondary', txt( 'view_post', 'Ver publicação' ) );
			ver.href = data.permalink;
			acoes.appendChild( document.createTextNode( ' ' ) );
			acoes.appendChild( ver );
		}

		frag.appendChild( acoes );
		return frag;
	}

	function initShare() {
		if ( ! cfg.logged_in ) {
			return;
		}

		document.addEventListener( 'click', function ( evento ) {
			var botao = evento.target.closest( '.remc-share-toggle' );
			if ( ! botao ) {
				return;
			}
			evento.preventDefault();

			if ( botao.dataset.busy ) {
				return;
			}
			botao.dataset.busy = '1';

			var slide = botao.closest( '[data-remc-slide]' );
			var status = slide ? slide.querySelector( '[data-remc-status]' ) : null;
			var original = botao.textContent;
			botao.textContent = txt( 'working', 'Enviando…' );

			var corpo = new URLSearchParams();
			corpo.append( 'action', 'remc_toggle_share' );
			corpo.append( 'nonce', cfg.share_nonce || '' );
			corpo.append( 'observation', botao.getAttribute( 'data-obs' ) );
			corpo.append( 'state', botao.getAttribute( 'data-state' ) );

			window.fetch( cfg.ajax_url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: corpo.toString()
			} )
				.then( function ( resposta ) {
					return resposta.json();
				} )
				.then( function ( json ) {
					if ( ! json || ! json.success || ! status ) {
						throw new Error( 'falha' );
					}
					status.innerHTML = '';
					status.appendChild( buildStatus( json.data ) );
				} )
				.catch( function () {
					botao.disabled = false;
					botao.textContent = original;
					if ( status ) {
						var erro = el( 'p', 'form-status error', txt( 'error', 'Não foi possível compartilhar agora.' ) );
						status.insertBefore( erro, status.firstChild );
					}
				} )
				.finally( function () {
					delete botao.dataset.busy;
				} );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Clima (refresh opcional)                                            */
	/* ------------------------------------------------------------------ */
	function atualizarClima() {
		var bloco = document.querySelector( '[data-remc-weather]' );
		var botao = document.querySelector( '[data-remc-weather-refresh]' );
		if ( ! bloco || ! botao || ! cfg.weather_url ) {
			return;
		}

		botao.addEventListener( 'click', function () {
			botao.disabled = true;
			var aviso = bloco.querySelector( '[data-remc-weather-msg]' );
			if ( aviso ) {
				aviso.textContent = txt( 'loading', 'Carregando…' );
				aviso.hidden = false;
			}

			window.fetch( cfg.weather_url + '?force=1', { credentials: 'same-origin' } )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( dados ) {
					if ( ! dados || dados.error || ! dados.current ) {
						throw new Error( 'falha' );
					}
					preencherClima( bloco, dados );
					if ( aviso ) {
						aviso.hidden = true;
					}
				} )
				.catch( function () {
					if ( aviso ) {
						aviso.textContent = txt( 'error', 'Não foi possível carregar os dados meteorológicos agora.' );
						aviso.hidden = false;
					}
				} )
				.finally( function () {
					botao.disabled = false;
				} );
		} );
	}

	function preencherClima( bloco, dados ) {
		var c = dados.current;
		var fmt = function ( valor, casas ) {
			if ( null === valor || undefined === valor ) {
				return '';
			}
			var n = Number( valor );
			if ( isNaN( n ) ) {
				return '';
			}
			return n.toLocaleString( 'pt-BR', {
				minimumFractionDigits: casas || 0,
				maximumFractionDigits: casas === undefined ? 1 : casas
			} );
		};
		var set = function ( seletor, valor ) {
			var no = bloco.querySelector( seletor );
			if ( no && null !== valor && '' !== valor ) {
				no.textContent = valor;
			}
		};

		set( '[data-w-icon]', c.icon );
		set( '[data-w-temp]', fmt( c.temperature ) + ' °C' );
		set( '[data-w-label]', c.label );
		set( '[data-w-apparent]', fmt( c.apparent ) + ' °C' );
		set( '[data-w-humidity]', c.humidity + '%' );
		set( '[data-w-wind]', fmt( c.wind ) + ' km/h' );
		set( '[data-w-precip]', fmt( c.precipitation, 2 ) + ' mm' );

		if ( dados.daily && dados.daily[ 0 ] ) {
			set( '[data-w-max]', fmt( dados.daily[ 0 ].max ) + ' °C' );
			set( '[data-w-min]', fmt( dados.daily[ 0 ].min ) + ' °C' );
		}

		var lista = bloco.querySelector( '[data-w-daily]' );
		if ( lista && dados.daily ) {
			lista.innerHTML = '';
			dados.daily.slice( 0, 3 ).forEach( function ( dia ) {
				var item = el( 'li', 'remc-forecast__item' );
				var data = el( 'span', 'remc-forecast__day', dia.data );
				var temp = el( 'span', 'remc-forecast__temp', fmt( dia.max ) + '° / ' + fmt( dia.min ) + '°' );
				var chuva = el( 'span', 'remc-forecast__rain', dia.chuva + '%' );
				item.appendChild( data );
				item.appendChild( temp );
				item.appendChild( chuva );
				lista.appendChild( item );
			} );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Boot                                                                */
	/* ------------------------------------------------------------------ */
	function boot() {
		initMenu();
		initCarousels();
		initShare();
		atualizarClima();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
