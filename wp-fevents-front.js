jQuery(document).ready(function($){
	$('.my-datepicker').datepicker({ dateFormat: 'dd/mm/yy'});
	
	$('#forminserisci').submit(function () {
		var error = 0;
		/*
		$('#data_error_msg').parent().hide();
		$('#localita_error_msg').parent().hide();
		$('#nomecamp_error_msg').parent().hide();
		$('#indcamp_error_msg').parent().hide();
		*/
		var data = $('#User_dataEventoDal').val();
		if (data == '') {
			error = 1;
			$('#data_error_msg').html('Inserire entrambe le date');
			$('#data_error_msg').parent().show();
		}
		var loc = $('#User_Localita').val();
		if (loc == '') {
			error = 1;
			$('#localita_error_msg').html('Inserire la localit&agrave;');
			$('#localita_error_msg').parent().show();
		}
		var nome = $('#User_NomeCamp').val();
		if (nome == '') {
			error = 1;
			$('#nomecamp_error_msg').html('Inserire il nome del campeggio');
			$('#nomecamp_error_msg').parent().show();
		}
		var indi = $('#User_IndirizzoCamp').val();
		if (indi == '') {
			error = 1;
			$('#indcamp_error_msg').html('Inserire l\'indirizzo del campeggio');
			$('#indcamp_error_msg').parent().show();
		}
		if (error) {
			$('#some_error_msg').html('Inserire i campi obbligatori');
			$('#some_error_msg').parent().show();
			return false;
		} else {
			return true;
		}
	});
	
	$('.tab-next').click(function () {
		if ($('div.tab-show')[0].id == $('div.tab-count')[0].id) {
		$('div.tab-show')
			.hide()
			.removeClass('tab-show')
			.addClass('tab-hide');
		$('div.tab-hide').eq(0)
			.fadeIn(500)
			.removeClass('tab-hide')
			.addClass('tab-show');
		}
		else {
		$('div.tab-show')
			.hide()
			.removeClass('tab-show')
			.addClass('tab-hide')
			.next()
			.fadeIn(500)
			.removeClass('tab-hide')
			.addClass('tab-show');
			}
		//nascondi i dettagli "partecipa all'evento"
		$('.book').hide();
		//e imposta caption del bottone
		$('#btnpartecipa').val('Partecipa/Cancella partecipazione');
		//nascondi form email
		$('.mailiscritti').hide();
		// goto top page slowly
		$('html, body').animate({ scrollTop: 400 }, 2000);		
		return false;
	});
	
	$('.tab-prev').click(function () {
		if ($('div.tab-show')[0].id == 0) {
		$('div.tab-show')
			.hide()
			.removeClass('tab-show')
			.addClass('tab-hide');
		$('div.tab-hide').eq($('div.tab-count')[0].id)
			.fadeIn(500)
			.removeClass('tab-hide')
			.addClass('tab-show');
		}
		else {
		$('div.tab-show')
			.hide()
			.removeClass('tab-show')
			.addClass('tab-hide')
			.prev()
			.fadeIn(500)
			.removeClass('tab-hide')
			.addClass('tab-show');
			}
		//nascondi i dettagli "partecipa all'evento"
		$('.book').hide();
		//e imposta caption del bottone
		$('#btnpartecipa').val('Partecipa/Cancella partecipazione');
		//nascondi form email
		$('.mailiscritti').hide();
		// goto top page slowly
		$('html, body').animate({ scrollTop: 400 }, 2000);
		return false;
	});
	
	$('.clickme').click(function() {
		$('.book').toggle('slow', function() {
		// Animation complete.
		});
		$v = $('#btnpartecipa').val();
	    if ($v == 'Chiudi')
			$('#btnpartecipa').val('Partecipa/Cancella partecipazione');
	    else
	        $('#btnpartecipa').val('Chiudi');
		return false;
	});
	
	$('.book').hide();
	
	$('.mailiscrittick').click(function () {
		$('.mailiscritti').toggle('slow', function () {
			// Animation complete.
		});
    });
	$('.mailiscritti').hide();
	
});