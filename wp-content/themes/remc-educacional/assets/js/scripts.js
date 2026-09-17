// REMC Core Scripts
jQuery(document).ready(function($) {
	// Form validation helper
	$('.remc-validate').on('submit', function(e) {
		var valid = true;
		
		$(this).find('.form-input, .form-select, .form-textarea').each(function() {
			var $field = $(this);
			var value = $field.val();
			var required = $field.attr('required');
			
			if (required && !value) {
				$field.addClass('form-error');
				valid = false;
			} else {
				$field.removeClass('form-error');
			}
		});
		
		if (!valid) {
			e.preventDefault();
			alert('Por favor, preencha os campos obrigatórios.');
		}
	});

	// AJAX chart loading
	$('.chart-load').on('click', function(e) {
		e.preventDefault();
		
		var $button = $(this);
		var url = $button.attr('href');
		
		$.ajax({
			url: remc_ajax.ajax_url,
			type: 'GET',
			dataType: 'json',
			data: url.split('?')[1],
			beforeSend: function() {
				$button.prop('disabled', true).text('Carregando...');
			},
			success: function(response) {
				if (response.success) {
					renderChart(response.data);
				}
			},
			complete: function() {
				$button.prop('disabled', false).text($button.data('original-text'));
			}
		});
	});

	// Render chart data (placeholder for charting library)
	function renderChart(data) {
		// This would use a charting library like Chart.js
		// For now, just log the data
		console.log('Chart data:', data);
		
		if (data.data && Object.keys(data.data).length === 0) {
			$('.chart-canvas').text('Nenhum dado disponível para o período selecionado.');
		}
	}

	// CSV Export
	$('.export-csv').on('click', function(e) {
		e.preventDefault();
		
		var url = $(this).attr('href');
		
		// Create a temporary form and submit it
		var $form = $('<form>', {
			method: 'GET',
			action: url,
			target: '_blank'
		}).appendTo('body');
		
		$form.submit();
		$form.remove();
	});

	// Toggle visibility for optional fields
	$('.toggle-optional').on('change', function() {
		var $target = $($(this).data('target'));
		
		if ($(this).val() === 'none' || $(this).val() === '') {
			$target.hide();
		} else {
			$target.show();
		}
	});

	// Preview observations before submit
	$('#preview-observation').on('click', function(e) {
		e.preventDefault();
		
		var formData = {
			action: 'remc_preview_observation',
			nonce: remc_ajax.nonce,
			data: $('#observation-form').serialize()
		};
		
		$.post(remc_ajax.ajax_url, formData, function(response) {
			$('#preview-modal').html(response).fadeIn();
		});
	});

	// Close preview modal
	$('.close-modal').on('click', function() {
		$('#preview-modal').fadeOut(function() {
			$(this).html('');
		});
	});

	// Prevent form double-submission
	$('.remc-form').on('submit', function() {
		var $submit = $(this).find('button[type="submit"], input[type="submit"]');
		$submit.prop('disabled', true).attr('data-original-text', $submit.text()).text('Enviando...');
	});
});
