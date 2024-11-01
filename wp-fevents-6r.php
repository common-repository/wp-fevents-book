<?php
define("DEBUG_LEVEL",2);
class FEvents6r extends FEventsBook
{
	public function __construct($name, $code, $base_path, $VER = '0.0', $option_page = true, $fmenu_page = false)
	{
		$this->VER = $VER;
		$this->wpf_name = $name;
		$this->wpf_code = $code;
		$this->wpf_path = plugins_url( $base_path );
		
		//eventi per 6ruote:
		add_shortcode( 'feventsbookadd', array( $this, 'add_fevent6' ) );
		add_shortcode( 'feventsbook6ruote', array( $this, 'show_fevent6' ) );
		add_shortcode( 'feventsbookedt', array( $this, 'edt_fevent6' ) );
		
		//save csv
		add_action( 'init', array( $this, 'generate_csv6r' ) );
		
		// per 6r serve anche uno script per il frontend
		add_action( 'wp_enqueue_scripts', array ($this, 'load_scripts') );
		
		// e una spunta per il consenso alle email
		add_action( 'show_user_profile', array ($this, 'fwp_add_custom_user_profile_fields' ) );
		add_action( 'edit_user_profile', array ($this, 'fwp_add_custom_user_profile_fields' ) );
		add_action( 'personal_options_update', array ($this, 'fwp_save_custom_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array ($this, 'fwp_save_custom_user_profile_fields' ) );
	}
	
	public function load_scripts()
	{
		wp_enqueue_script( 'my-script-handle', plugins_url('wp-fevents-front.js', __FILE__ ), false, $this->VER, true );
		$this->wpapi_date_picker();	
	}
	
	///////////////////////// ****************** Eventi per 6ruote ******************
	// shortcode [feventsbookedt]
	// visualizza e permette l'editazione di un singolo evento
	public function edt_fevent6($atts)
	{
		$current_user = wp_get_current_user();
		$idUser = $current_user->ID;
		if ( 0==$idUser )
			return __('Not logged User - access to the service denied' , 'wp-fevents-book');
		
		//cancellazione di un evento
		if ( isset($_POST['delete_eventid']) )
			if ( $_POST['delete_eventid']>-1 )
				$this->delete_event6r($_POST['delete_eventid'], $_POST['remove_event'], true);
		
		// se l'utente ha selezionato un evento da editare
		if ( isset($_POST['cmdEdt6r']) ) {
			$cmdedt = $_POST['cmdEdt6r'];
			$eventid = $_POST['eventid'];
			//lancia form editazione
			return $this->add_fevent6( array('eventid'=>$eventid) );
		}
		if ( isset($_POST['cmdSubmit6r']) ) {
			$cmd = $_POST['cmdSubmit6r'];
			$idUser = $_POST['UserID'];
			$idEvent = $_POST['IDevento'];
			return $this->add_fevent6( array('eventid'=>$idEvent) );
		}
		$evento6rA = stripslashes_deep( get_option ( $this->wpf_code.'6ruote') );
		$noevents = true;
		// crea select con tutti e soli gli eventi appartenenti all'utente loggato o tutti per gli admin
		$out = '<form method="POST" action="'.$_SERVER[ "REQUEST_URI" ].'" id="formedt6r">';
		$sel ='Selezionare l\'evento da modificare: <select name="eventid">';
		foreach ($evento6rA as $ev6r) {
			if ( $ev6r['idUser'] == $idUser || $current_user->has_cap('edit_users')) {
				$descr = $ev6r['User_Localita'].' dal '.$ev6r['User_dataEventoDal'].' al '.$ev6r['User_dataEventoAl'];
				$sel .= '<option value="'.$ev6r['ID'].'">'.$descr.'</option>';
				$noevents = false;
			}
		}
		$sel .= '</select>';
		$out .= $sel;
		$out .= '<input type="submit" name="cmdEdt6r" value="Modifica"/>';
		if ($noevents)
			$out = 'Non ci sono eventi da modificare per questo utente';
		return $out.'</form>';
	}
	
	//shortcode [feventsbookadd]
	public function add_fevent6($atts)
	{
		extract(shortcode_atts(array(
			'eventid' => -1
			), $atts));
		$evento6rA = stripslashes_deep( get_option ( $this->wpf_code.'6ruote') );

		if ($eventid == -1) {
			// calcola di nuovo l'id prossimo evento all'inserimento
			if (sizeof($evento6rA)==0)
				$eventid = 1001;
			else
				$eventid = $evento6rA[sizeof($evento6rA)-1]['ID']+1;
			$ev = array(
				'User_dataEventoDal' => '',
				'User_dataEventoAl' => '',
				'User_Localita' => '',
				'User_NomeCamp' => '',
				'User_IndirizzoCamp' => '',
				'User_WebCamp' => '',
				'User_convenzionato_ACSI' => '',
				'User_convenzionato_Fed' => '',
				'User_convenzionato_ADAC' => '',
				'User_convenzionato_CCI' => '',
				'User_animali' => '',
				'User_disabili' => '',
				'User_prezzo' => '',
				'User_evento' => '',
				'User_ev_disabili' => '',
				'User_costo_visita' => '',
				'User_guida' => '',
				'User_guida_costo' => '',
				'User_altro' => '',
				'User_maxusers' => 100
			);
		}
		else {
			// si suppone che l'evento esista!!
			foreach ($evento6rA as $ev6r) {
				if ( $ev6r['ID'] == $eventid ) {
					$ev = $ev6r;
				}
			}
		}
		return $this->showform_fevent6($eventid, $ev);
	}
	
	// shortcode [feventsbook6ruote]
	private function showform_fevent6($eventID, $ev)
	{
		if ( isset($_POST['cmdSubmit6r']) ) {
			$idEvent = $_POST['IDevento'];
			$cmd = $_POST['cmdSubmit6r'];
			if ($cmd == 'Modifica evento') {
				//in caso di modifica NON cambio questi campi (resta l'owner originario)
				$idUser = $ev['idUser'];
				$UserName = $ev['UserName'];
				$UserMail = $ev['UserMail'];				
			}
			else {
				$idUser = $_POST['UserID'];
				$UserName = $_POST['UserName'];
				$UserMail = $_POST['UserMail'];				
			}
			$User_WebCamp = $_POST['User_WebCamp'];
			$User_convenzionato_ACSI = isset($_POST['User_convenzionato_ACSI'])?$_POST['User_convenzionato_ACSI']:'';
			$User_convenzionato_Fed = isset($_POST['User_convenzionato_Fed'])?$_POST['User_convenzionato_Fed']:'';
			$User_convenzionato_ADAC = isset($_POST['User_convenzionato_ADAC'])?$_POST['User_convenzionato_ADAC']:'';
			$User_convenzionato_CCI	 = isset($_POST['User_convenzionato_CCI'])?$_POST['User_convenzionato_CCI']:'';
			$User_animali	 = isset($_POST['User_animali'])?$_POST['User_animali']:'';
			$User_disabili	 = isset($_POST['User_disabili'])?$_POST['User_disabili']:'';
			$User_prezzo	 = isset($_POST['User_prezzo'])?$_POST['User_prezzo']:'';
			$User_evento	 = isset($_POST['User_evento'])?$_POST['User_evento']:'';
			$User_ev_disabili	 = isset($_POST['User_ev_disabili'])?$_POST['User_ev_disabili']:'';
			$User_costo_visita	 = isset($_POST['User_costo_visita'])?$_POST['User_costo_visita']:'';
			$User_guida	 = isset($_POST['User_guida'])?$_POST['User_guida']:'';
			$User_guida_costo	 = isset($_POST['User_guida_costo'])?$_POST['User_guida_costo']:'';
			$User_altro	 = isset($_POST['User_altro'])?$_POST['User_altro']:'';
			$User_maxusers	 = isset($_POST['User_maxusers'])?$_POST['User_maxusers']:'';

			$nuovoevento6r = array(
			'ID'     => $idEvent,
			'idUser' => $idUser,
			'UserName' => $UserName,
			'UserMail' => $UserMail,
			'User_dataEventoDal' => $_POST['User_dataEventoDal'],
			'User_dataEventoAl' => $_POST['User_dataEventoAl'],
			'User_Localita' => $_POST['User_Localita'],
			'User_NomeCamp' => $_POST['User_NomeCamp'],
			'User_IndirizzoCamp' => $_POST['User_IndirizzoCamp'],
			'User_WebCamp' => $User_WebCamp,
			'User_convenzionato_ACSI' => $User_convenzionato_ACSI,
			'User_convenzionato_Fed' => $User_convenzionato_Fed,
			'User_convenzionato_ADAC' => $User_convenzionato_ADAC,
			'User_convenzionato_CCI' => $User_convenzionato_CCI,
			'User_animali' => $User_animali,
			'User_disabili' => $User_disabili,
			'User_prezzo' => $User_prezzo,
			'User_evento' => $User_evento,
			'User_ev_disabili' => $User_ev_disabili,
			'User_costo_visita' => $User_costo_visita,
			'User_guida' => $User_guida,
			'User_guida_costo' => $User_guida_costo,
			'User_altro' => $User_altro,
			'User_maxusers' => $User_maxusers
			);
			if ( isset( $cmd ) ) {
				$addnew = true;
				$type = 1;
				//ricalcolo id per evitare tentativi di doppio inserimento
				$eventi6r = stripslashes_deep( get_option ( $this->wpf_code.'6ruote') );
				if ($cmd == 'Inserisci evento') {
					if (sizeof($eventi6r)==0)
						$idEvent = 1001;
					else
						$idEvent = $eventi6r[sizeof($eventi6r)-1]['ID']+1;
					$nuovoevento6r['ID'] = $idEvent;
				}
				if (!add_option ( $this->wpf_code.'6ruote', array($nuovoevento6r)) ) {
					$cntisc = 0;
					foreach ($eventi6r as $ev6r) {
						//estrazione eventi 6ruote
						if ( $ev6r['ID'] == $idEvent ) {
							$addnew = false;
							$type = 0;
							if ( __('DELETE' , 'wp-fevents-book') == $cmd ) {
								// lo elimino
								unset( $eventi6r[$cntisc] );
								$type = -1;
							}
							else { 
								// aggiorno tutti i campi
								$eventi6r[$cntisc]['User_dataEventoDal'] = $_POST['User_dataEventoDal'];
								$eventi6r[$cntisc]['User_dataEventoAl'] = $_POST['User_dataEventoAl'];
								$eventi6r[$cntisc]['User_Localita'] = $_POST['User_Localita'];
								$eventi6r[$cntisc]['User_NomeCamp'] = $_POST['User_NomeCamp'];
								$eventi6r[$cntisc]['User_IndirizzoCamp'] = $_POST['User_IndirizzoCamp'];
								$eventi6r[$cntisc]['User_WebCamp'] = $_POST['User_WebCamp'];
								$eventi6r[$cntisc]['User_convenzionato_ACSI'] = $User_convenzionato_ACSI;
								$eventi6r[$cntisc]['User_convenzionato_Fed'] = $User_convenzionato_Fed;
								$eventi6r[$cntisc]['User_convenzionato_ADAC'] = $User_convenzionato_ADAC;
								$eventi6r[$cntisc]['User_convenzionato_CCI'] = $User_convenzionato_CCI;
								$eventi6r[$cntisc]['User_animali'] = $User_animali;
								$eventi6r[$cntisc]['User_disabili'] = $User_disabili;
								$eventi6r[$cntisc]['User_prezzo'] = $User_prezzo;
								$eventi6r[$cntisc]['User_evento'] = $User_evento;
								$eventi6r[$cntisc]['User_ev_disabili'] = $User_ev_disabili;
								$eventi6r[$cntisc]['User_costo_visita'] = $User_costo_visita;
								$eventi6r[$cntisc]['User_guida'] = $User_guida;
								$eventi6r[$cntisc]['User_guida_costo'] = $User_guida_costo;
								$eventi6r[$cntisc]['User_altro'] = $User_altro;
								$eventi6r[$cntisc]['User_maxusers'] = $User_maxusers;
								if ( $type == 0 ) // in modifica: notifica solo se richiesta
									if (!isset($_POST['notifyUsers']))
										$type = -2;
							}
						}
						$cntisc++;
					}
					//ricompatta
					$eventi6r = array_values($eventi6r);
					if ( $addnew ) {
						array_push( $eventi6r, $nuovoevento6r );
						$this->fwp_log('Inserimento nuovo evento id='. $idEvent);
					}
					update_option ( $this->wpf_code.'6ruote', $eventi6r );
					//aggiorna form con i nuovi dati
					$ev = stripslashes_deep( $nuovoevento6r );
				}
			$this->fwp_notifyEvent( $type, $idEvent );
			}
		}

		$current_user = wp_get_current_user();
		$idUser = $current_user->ID;
		//crea output html
		if ( 0==$idUser )
			$out = __('Not logged User - access to the service denied' , 'wp-fevents-book');
		else {
			$out = $this->out_form_new( $current_user, $eventID, $ev );
		}
		return '<!-- SHORTCODE ADD_FEVENT DI '.$this->wpf_name.' con ID='.$eventID.' VER. '.$this->VER. '--><br/>'.$out;
	}
	
	public function out_form_new( $current_user, $eventID, $ev )
	{
		$out = '<div id="fevent6r'.$eventID.'" class="wrap">';
		if ($ev['User_Localita']=='') {
			$out .= '<h2>Inserimento nuovo evento</h2>';
			$btntxt = 'Inserisci evento';
			$ev_userid = $current_user->ID;
			$ev_name = $current_user->display_name;
			$ev_email = $current_user->user_email;
			$ev_notify = '';
		}
		else {
			$out .= '<h2>Modifica evento</h2>';
			$out .= '<input type="submit" class="button-primary" value="'. __('Delete Event and its booked users', 'wp-fevents-book').'" onclick="var r=confirm(\''.__('Delete Event', 'wp-fevents-book').' '.$eventID.' '.__('and all its booked users?', 'wp-fevents-book').'\'); if (r==true) { document.forms[\'forminserisci\'].elements[0].value=\''.$eventID.'\'; document.forms[\'forminserisci\'].elements[1].value=\'YES\'; document.forms[\'forminserisci\'].submit(); } return false;">';
			$btntxt = 'Modifica evento';
			$ev_userid = $ev['idUser'];
			$ev_name = $ev['UserName'];
			$ev_email = $ev['UserMail'];
			$ev_notify = '<br/><input style="width: 10px; margin:5px;" type="checkbox" name="notifyUsers" value="notifyUsers" checked> avvisa della modifica gli utenti iscritti';
		}
		$out .= '<form method="post" action="'.$_SERVER[ "REQUEST_URI" ].'" id="forminserisci">
			<input type="hidden" name="delete_eventid" value="-1">
			<input type="hidden" name="remove_event" value="NO">
			<input type="hidden" name="UserID" value="'.$ev_userid.'">
			<table class="form-table-event">
				<tr valign="top"><th scope="row">'.__('Event ID' , 'wp-fevents-book').'</th>
					<td><input style="background-color:yellow; width: 60px;" type="text" name="IDevento" value="'.$eventID.'" readonly="readonly"/></td>
				</tr>
				<tr valign="top"><th scope="row">'.__('Nome utente' , 'wp-fevents-book').'</th>
					<td><input style="background-color:yellow;" type="text" name="UserName" value="'.$ev_name.'" readonly="readonly"/></td>
				</tr>
				<tr valign="top"><th scope="row">'.__('Mail di riferimento' , 'wp-fevents-book').'</th>
					<td><input style="background-color:yellow;" type="text" name="UserMail" value="'.$ev_email.'" readonly="readonly"/></td>
				</tr>
				<tr valign="top"><th scope="row"><span class="err_msg" id="data_error_msg">*</span> Data</th>
					<td>dal <input type="text" style="width: 120px;" name="User_dataEventoDal" id="User_dataEventoDal" class="my-datepicker" value="'.$ev['User_dataEventoDal'].'"/>
					al <input type="text" style="width: 120px;" name="User_dataEventoAl" id="User_dataEventoAl" class="my-datepicker" value="'.$ev['User_dataEventoAl'].'"/></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row"><span class="err_msg" id="localita_error_msg">*</span> Localit&agrave;</th>
					<td><input type="text" name="User_Localita" id="User_Localita" value="'.$ev['User_Localita'].'"/></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row"><span class="err_msg" id="nomecamp_error_msg">*</span> Nome campeggio</th>
					<td><input type="text" name="User_NomeCamp" id="User_NomeCamp" value="'.$ev['User_NomeCamp'].'"/></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;"><span class="err_msg" id="indcamp_error_msg">*</span> Indirizzo campeggio</th>
					<td><textarea style="width: 100%;" rows="2" name="User_IndirizzoCamp" id="User_IndirizzoCamp">'.$ev['User_IndirizzoCamp'].'</textarea></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Indirizzo WEB campeggio</th>
					<td><input type="text" name="User_WebCamp" value="'.$ev['User_WebCamp'].'"/></td>
				</tr>';
				$ckACSI = $ev['User_convenzionato_ACSI'] == 'ACSI' ? 'checked' : '';
				$ckFed = $ev['User_convenzionato_Fed'] == 'Federcampeggi' ? 'checked' : '';
				$ckADAC = $ev['User_convenzionato_ADAC'] == 'ADAC' ? 'checked' : '';
				$ckCCI = $ev['User_convenzionato_CCI'] == 'CCI' ? 'checked' : '';
				$out .= '<tr valign="top"><th scope="row">Convenzionato</th>
					<td>
					<input style="width: 10px; margin:5px;" type="checkbox" name="User_convenzionato_ACSI" value="ACSI" '.$ckACSI.'>ACSI
					<input style="width: 10px; margin:5px;" type="checkbox" name="User_convenzionato_Fed" value="Federcampeggi" '.$ckFed.'>Federcampeggio
					<input style="width: 10px; margin:5px;" type="checkbox" name="User_convenzionato_ADAC" value="ADAC" '.$ckADAC.'>ADAC
					<input style="width: 10px; margin:5px;" type="checkbox" name="User_convenzionato_CCI" value="CCI" '.$ckCCI.'>CCI</td>
				</tr>';
				$ckSI = $ev['User_animali'] == 'si' ? 'checked' : '';
				$ckNO = $ev['User_animali'] == 'no' || '' ? 'checked' : '';
				$out .= '<tr valign="top"><th scope="row">Animali consentiti</th>
					<td><input style="width: 10px;" type="radio" name="User_animali" value="no" '.$ckNO.'>no
					<input style="width: 10px;" type="radio" name="User_animali" value="si" '.$ckSI.'>si</td>
				</tr>';
				$ckSI = $ev['User_disabili'] == 'si' ? 'checked' : '';
				$ckNO = $ev['User_disabili'] == 'no' || '' ? 'checked' : '';
				$out .= '<tr valign="top"><th scope="row">Campeggio attrezzato per disabili</th>
					<td><input style="width: 10px;" type="radio" name="User_disabili" value="no" '.$ckNO.'>no
					<input style="width: 10px;" type="radio" name="User_disabili" value="si" '.$ckSI.'>si</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Prezzo per soggiorno (specificare prezzo adulti, prezzo ragazzi e fasce d\'et&agrave; (soglia minima di pagamento nonch&eacute; eventuali prezzi forfait)</th>
					<td><textarea style="width: 100%;" rows="3" name="User_prezzo">'.$ev['User_prezzo'].'</textarea></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Evento/Visita proposto</th>
					<td><textarea style="width: 100%;" rows="3" name="User_evento">'.$ev['User_evento'].'</textarea></td>
				</tr>';
				$ckSI = $ev['User_ev_disabili'] == 'si' ? 'checked' : '';
				$ckNO = $ev['User_ev_disabili'] == 'no' || '' ? 'checked' : '';
				$out .= '<tr valign="top"><th scope="row">Evento adeguato per disabili</th>
					<td><input style="width: 10px;" type="radio" name="User_ev_disabili" value="no" '.$ckNO.'>no
					<input style="width: 10px;" type="radio" name="User_ev_disabili" value="si" '.$ckSI.'>si</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Costo visita (diviso per fasce d\'et&agrave; / gruppi)</th>
					<td><textarea style="width: 100%;" rows="3" name="User_costo_visita">'.$ev['User_costo_visita'].'</textarea></td>
				</tr>';
				$ckSI = $ev['User_guida'] == 'si' ? 'checked' : '';
				$ckNO = $ev['User_guida'] == 'no' || '' ? 'checked' : '';
				$out .= '<tr valign="top"><th scope="row">Possibilit&agrave; di guida </th>
					<td><input style="width: 10px;" type="radio" name="User_guida" value="no" '.$ckNO.'>no
					<input style="width: 10px;" type="radio" name="User_guida" value="si" '.$ckSI.'>si</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Eventuale costo aggiuntivo per la guida</th>
					<td><input type="text" name="User_guida_costo" value="'.$ev['User_guida_costo'].'"/></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Altre attivit&agrave; proposte (specificare eventuali costi aggiuntivi - per proposte di pasti, segnalare se attrezzati per celiachi)</th>
					<td><textarea style="width: 100%;" rows="3" name="User_altro">'.$ev['User_altro'].'</textarea></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Numero massimo partecipanti</th>
					<td><input type="number" min="1" max="100" name="User_maxusers" value="'.$ev['User_maxusers'].'"/></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row"><div class="err_msg" id="some_error_msg">* campi obbligatori</div></th>
					<td><input type="submit" name="cmdSubmit6r" value="'.$btntxt.'" id="inserisci6r"/>'.$ev_notify.'</td>
				</tr>';
				$out .=
			'</table>
		</form>
		</div>';
		return $out;
	}
	
	// show and subscribe to an event
	public function show_fevent6 ($atts)
	{
		extract(shortcode_atts(array(
			'eventid' => -1
			), $atts));
		$options = stripslashes_deep( get_option ( $this->wpf_code.'6ruote') );
		//evento defaults fields values
		$evento_default = array(

		);
		
		$current_user = wp_get_current_user();
		$idUser = $current_user->ID;
		if ( 0==$idUser )
			return __('Not logged User - access to the service denied' , 'wp-fevents-book');

		if ($eventid != -1) {
			$eventid = $eventid - 1000;
			$evento = $options[$eventid];
			$evento = array_merge($evento_default, $evento);
			return $this->out_form_6r($evento, $eventid+1000);
		}
		else {
			// loop su tutti gli eventi esistenti; visualizza quelli attivi
			$prevprec = '<div class="btn-center"><input type="submit" class="tab-prev" value="precedente"/> <input type="submit" class="tab-next" value="successivo"/></div>';
			$rethtml = $prevprec;
			/*
			<div class="tab-count" style="display: none;" id=4></div>
			<div class="tab-show" id=1>blurb1</div>
			<div class="tab-hide" style="display: none;" id=2>blurb2</div>
			<div class="tab-hide" style="display: none;" id=3>blurb3</div>
			<div class="tab-hide" style="display: none;" id=4>blurb4</div>';
			*/
			$cnt = 0;
			//non conteggiare gli eventi cancellati
			if (isset($_POST['UevID']))
				$cntvedi = $_POST['UevID']-1000;
			else
				$cntvedi = 0;

			foreach ($options as $eventidloop) {
				if (0==$cnt)
					$rethtml .= '<div class="tab-show" evid="'.$eventidloop['ID'].'" id='.$cnt.' >';				
				else
					$rethtml .= '<div class="tab-hide" evid="'.$eventidloop['ID'].'" style="display: none;" id='.$cnt.'>';
				$rethtml .= $this->out_form_6r($eventidloop, $eventidloop['ID'], $idUser);
				$rethtml .= '</div>';
				$cnt++;
			}
			$cnthtml = '<div class="btn-center infoattive">Proposte attive: '.$cnt.'</div>';
			$rethtml .= '<div class="tab-count" style="display: none;" id='.--$cnt.'></div>';
			$rethtml .= $prevprec;
			if ( -1==$cnt )
				$rethtml = '<div class="noitems">Al momento non ci sono proposte, &egrave; il momento perfetto per farne una...</div>';
			return $cnthtml.$rethtml;
		}
	}
	
	public function out_form_6r( $evento, $eventID, $idU )
	{
		// abbiamo la data fine evento gg/mm/aaaa
		//if the separator is a dash (-) or a dot (.), then the European d-m-y format is assumed.
		//$dtnow = strtotime("now"); //visibile fino al giorno stesso alle ore 0
		$dtnow = strtotime("-2 days"); //visibile fino al giorno successivo alle ore 24
		$dtend = strtotime(str_replace('/','.',$evento['User_dataEventoAl']));
		//print $dtnow.' '.$dtend.' -- ';
		if ($dtnow > $dtend) {
			$out = '<div class="wrap"><h2>Evento scaduto</h2></div>';
			$this->archive_fevent6( $eventID );
			return $out;
		}
		$evNrUsers = 0; // holds the number of users partecipating at the event
		$evU = array (
			'idU' => '',
			'UName' => '',
			'UMail' => '',
			'UevID' => '',
			'UCani' => '',
			'UAdulti' => '',
			'URagazzi' => '',
			'UBambini5' => '',
			'UBambini0' => '',
			'UPartecipa' => 'no',
			'UDisabili' => 'no',
			'UNote' => '');
		$iscrittieventi = get_option( $this->wpf_code.'6riscritti' );
		if ($iscrittieventi != false) {
			foreach ($iscrittieventi as $iscritto) {
				//estrazione iscritti all'evento in oggetto
				if ( $iscritto['UevID'] == $eventID ) {
					if ( $iscritto['idU'] == $idU ) {
						// l'utente e' gia' iscritto:
						$evU = $iscritto;
					}
					$evNrUsers++;
				}
			}
		}
		if ( isset($_POST['cmdAderisci6r']) ) {
			$cmdA = $_POST['cmdAderisci6r'];
			$idU = $_POST['UID'];
			$UName = $_POST['UName'];
			$UMail = $_POST['UMail'];
			$UevID = $_POST['UevID']; //ID evento a cui si iscrive
			$UCani = $_POST['UCani'];
			$UAdulti  = $_POST['UAdulti'];
			$URagazzi = $_POST['URagazzi'];
			$UBambini5 = $_POST['UBambini5'];
			$UBambini0 = $_POST['UBambini0'];
			$UPartecipa = $_POST['UPartecipa'];
			$UDisabili = $_POST['UDisabili'];
			$UNote = $_POST['UNote'];
			if ($eventID==$UevID) {
				$nuovoiscritto = array(
				'idU' => $idU,
				'UName' => $UName,
				'UMail' => $UMail,
				'UevID' => $UevID,
				'UCani' => $UCani,
				'UAdulti' => $UAdulti,
				'URagazzi' => $URagazzi,
				'UBambini5' => $UBambini5,
				'UBambini0' => $UBambini0,
				'UPartecipa' => $UPartecipa,
				'UDisabili' => $UDisabili,
				'UNote' => $UNote);
				$evU = $nuovoiscritto;
				if ( isset( $cmdA ) ) 
				{
					$addnew = true;
					// mi serve per la mail
					$isdel = false;
					if (!add_option ( $this->wpf_code.'6riscritti', array($nuovoiscritto )) ) {
						$iscrittieventi = get_option( $this->wpf_code.'6riscritti' );
						$cntisc = 0;
						foreach ($iscrittieventi as $iscritto) {
							//estrazione iscritti all'evento in oggetto
							if ( $iscritto['UevID'] == $eventID ) {
								if ( $iscritto['idU'] == $idU ) {
									// l'utente e' gia' iscritto:
									$addnew = false;
									if ( __('Cancella iscrizione' , 'wp-fevents-book') == $cmdA ) {
										// lo elimino
										unset( $iscrittieventi[$cntisc] );
										// ora c'e' un iscritto in meno!
										$evNrUsers--;
										//... ed e' questo utente:
										// 'disabilito' il bottone aggiorna/cancella dall'evento
										$evU['idU']='';
										$isdel = true;
									}
									else { 
										// o aggiorno i campi
										$iscrittieventi[$cntisc]['UCani'] = $UCani;
										$iscrittieventi[$cntisc]['UAdulti'] = $UAdulti;
										$iscrittieventi[$cntisc]['URagazzi'] = $URagazzi;
										$iscrittieventi[$cntisc]['UBambini5'] = $UBambini5;
										$iscrittieventi[$cntisc]['UBambini0'] = $UBambini0;
										$iscrittieventi[$cntisc]['UPartecipa'] = $UPartecipa;
										$iscrittieventi[$cntisc]['UDisabili'] = $UDisabili;
										$iscrittieventi[$cntisc]['UNote'] = $UNote;
									}
								}
							}
							$cntisc++;
						}
						//ricompatta
						$iscrittieventi = array_values($iscrittieventi);
						if ( $addnew )
							array_push( $iscrittieventi, $nuovoiscritto );
						update_option ( $this->wpf_code.'6riscritti', $iscrittieventi );
					}
					$this->fwp_notifyEventInscription( $addnew, $isdel, $eventID );
				}
			}
		}
		$idOwner = $evento['idUser'];
		$out = '<div class="wrap">
		<!--h2>Iscrizione ad un evento</h2-->
		<form name="wpfeventsbookform6r_'.$eventID.'" method="POST" action="'.$_SERVER[ "REQUEST_URI" ].'" id="formaderisci">
			<input type="hidden" name="UserID" value="'.$idOwner.'">
			<input type="hidden" name="UevID" value="'.$eventID.'">
			<input type="hidden" name="InviaMail6r" value="NO">';
		// if supervisor or owner
		$current_user = wp_get_current_user();
		$idUser = $current_user->ID;
		if ( $idOwner == $idUser || $current_user->has_cap('edit_users')) {
			$out .= '<div class="btn-center"><input class="btn-csv" type="button" value="Scarica lista partecipanti" onclick="document.forms[\'wpfeventsbookform6r_'.$eventID.'\'].submit(); return false;" /></div>';
			$out .= '<div class="mailiscrittick"><input id="toggletextmail" type="button" value="Invia mail agli iscritti"></div>';
			$out .= '<div class="mailiscritti"><textarea class="rotextarea" rows="3" name="InviaMail6r_text" ></textarea>';
			$out .= '<a href="#" title="Invia mail per gli iscritti" onclick="document.forms[\'wpfeventsbookform6r_'.$eventID.'\'].elements[\'InviaMail6r\'].value=\'YES\'; document.forms[\'wpfeventsbookform6r_'.$eventID.'\'].submit(); alert (\'email inviate\'); return false;">Invia</a></div>';
		}			
		$out .=	'<table class="form-table-event">
				<tr valign="top"><th scope="row">'.__('Event ID' , 'wp-fevents-book').'</th>
					<td>'.$eventID.'</td>
				</tr>
				<tr valign="top"><th scope="row">'.__('Nome utente' , 'wp-fevents-book').'</th>
					<td>'.$evento['UserName'].'</td>
				</tr>
				<tr valign="top"><th scope="row">'.__('Mail di riferimento' , 'wp-fevents-book').'</th>
					<td>'.$evento['UserMail'].'</td>
				</tr>
				<tr valign="top"><th scope="row">Data</th>
					<td>dal '.$evento['User_dataEventoDal'].' al '.$evento['User_dataEventoAl'].'</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Localit&agrave;</th>
					<td>'.$evento['User_Localita'].'</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Nome campeggio</th>
					<td>'.$evento['User_NomeCamp'].'</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Indirizzo campeggio</th>
					<td><textarea class="rotextarea" rows="2" name="User_NomeCamp" readonly="readonly">'.$evento['User_IndirizzoCamp'].'</textarea></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Indirizzo WEB campeggio</th>
					<td>'.$evento['User_WebCamp'].'</td>
				</tr>';
				$convezionato = $evento['User_convenzionato_ACSI'].' '.$evento['User_convenzionato_Fed'].' '.$evento['User_convenzionato_ADAC'].' '.$evento['User_convenzionato_CCI'];
				$out .= '<tr valign="top"><th scope="row">Convenzionato</th>
					<td>'.$convezionato.'</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Animali consentiti</th>
					<td>'.$evento['User_animali'].'</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Campeggio attrezzato per disabili</th>
					<td>'.$evento['User_disabili'].'</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Prezzo per soggiorno: specificare prezzo adulti, prezzo ragazzi e fasce d\'et&agrave; (soglia minima di pagamento nonch&eacute; eventuali prezzi forfait)</th>
					<td><textarea class="rotextarea" rows="3" name="User_prezzo" readonly="readonly">'.$evento['User_prezzo'].'</textarea></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Evento/Visita proposto</th>
					<td><textarea class="rotextarea" rows="3" name="User_evento" readonly="readonly">'.$evento['User_evento'].'</textarea></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Evento adeguato per disabili</th>
					<td>'.$evento['User_ev_disabili'].'</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Costo visita (diviso per fasce d\'et&agrave; / gruppi)</th>
					<td><textarea class="rotextarea" rows="3" name="User_costo_visita" readonly="readonly">'.$evento['User_costo_visita'].'</textarea></td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Possibilit&agrave; di guida </th>
					<td>'.$evento['User_guida'].'</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row">Eventuale costo aggiuntivo per la guida</th>
					<td>'.$evento['User_guida_costo'].'</td>
				</tr>';
				$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Altre attivit&agrave; proposte (specificare eventuali costi aggiuntivi - per proposte di pasti, segnalare se attrezzati per celiachi)</th>
					<td><textarea class="rotextarea" rows="3" name="User_altro" readonly="readonly">'.$evento['User_altro'].'</textarea></td>
				</tr>';
				$posti = $evento['User_maxusers'] - $evNrUsers;
				//$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Piazzole ancora disponibili:</th>
				//	<td>'.$posti.'</td></tr>';
				$out .= $this->partecipanti_6r( $eventID, $evento['idUser']  );
				if ($evU['idU']=='')
					if ( $posti > 0 )
						$out .= '<tr valign="top"><td colspan="2" class="headerpartecipazione clickme"><input id="btnpartecipa" type="submit" value="Partecipa all\'evento: ancora '.$posti.' dei '.$evento['User_maxusers'].' posti disponibili" /></td></tr>';
					else
						$out .= '<tr valign="top"><td colspan="2" class="headerpartecipazione"><input id="btnfull" type="submit" disabled="disabled" value="Nessun posto disponibile al momento" /></td></tr>';
				else
					$out .= '<tr valign="top"><td colspan="2" class="headerpartecipazione clickme"><input id="btnpartecipa" type="submit" value="Modifica partecipazione" /></td></tr>';
				$out .= '</table><div class="book" style="visibility: show; display: block"><table class="form-table-event" style="margin: -24px -1px 24px 0;">';
				$out .= $this->aderisci_6r($evU);
				if ($evU['idU']=='')
					$out .= '<tr valign="top"><td colspan="2"><input type="submit" name="cmdAderisci6r" value="Conferma partecipazione" id="aderisci6r"/></td></tr>';
				else
					$out .= '<tr valign="top"><td colspan="2"><div><input type="submit" style="margin-left:35px; width:40%;" name="cmdAderisci6r" value="Aggiorna partecipazione" id="aggiorna6r"/>
					<input type="submit" style="margin-left:35px; width:40%;" name="cmdAderisci6r" value="Cancella iscrizione" id="cancella6r"/></div></td></tr>';
				$out .= 
			'</table></div>
		</form>
		</div>';
		return $out;
	}

	public function aderisci_6r($evU)
	{
		$current_user = wp_get_current_user();
		$sel5 = '
			<option value="0">0</option>
			<option value="1">1</option>
			<option value="2">2</option>
			<option value="3">3</option>
			<option value="4">4</option>
			<option value="5">5</option>
		</select>';
		$sel10 = '
			<option value="0">0</option>
			<option value="1">1</option>
			<option value="2">2</option>
			<option value="3">3</option>
			<option value="4">4</option>
			<option value="5">5</option>
			<option value="6">6</option>
			<option value="7">7</option>
			<option value="8">8</option>
			<option value="9">9</option>
			<option value="10">10</option>
		</select>';
		$out = '<input type="hidden" name="UID" value="'.$current_user->ID.'">
				<tr valign="top"><th scope="row">'.__('Nome utente' , 'wp-fevents-book').'</th>
					<td><input style="background-color:yellow;" type="text" name="UName" value="'.$current_user->display_name.'" readonly="readonly"/></td>
				</tr>
				<tr valign="top"><th scope="row">'.__('Mail di riferimento' , 'wp-fevents-book').'</th>
					<td><input style="background-color:yellow;" type="text" name="UMail" value="'.$current_user->user_email.'" readonly="readonly"/></td>
				</tr>';
		/*
		$out .= '<tr valign="top"><th scope="row">Eventuale tessera posseduta</th>
					<td>
					<input style="width: 10px;" type="checkbox" name="Uconvenzionato_ACSI" value="ACSI">ACSI
					<input style="width: 10px;" type="checkbox" name="Uconvenzionato_Fed" value="Federcampeggi">Federcampeggio
					<input style="width: 10px;" type="checkbox" name="Uconvenzionato_ADAC" value="ADAC">ADAC
					<input style="width: 10px;" type="checkbox" name="Uconvenzionato_CCI" value="CCI">CCI</td>
				</tr>';
		*/
		$sev = $evU['UCani']=='' ? '0':$evU['UCani'];
		$sel = '<option value="'.$sev.'" selected >'.$sev.'</option>';
		$out .= '<tr valign="top"><th scope="row">Animali</th>
					<td><select name="UCani">'.$sel.$sel5.' '.$evU['UCani'].'</td>
				</tr>';
		$sev = $evU['UAdulti']=='' ? '0':$evU['UAdulti'];
		$sel = '<option value="'.$sev.'" selected >'.$sev.'</option>';				
		$out .= '<tr valign="top"><th scope="row">Numero Adulti</th>
					<td><select name="UAdulti">'.$sel.$sel10.' '.$evU['UAdulti'].'</td>
				</tr>';
		$sev = $evU['URagazzi']=='' ? '0':$evU['URagazzi'];
		$sel = '<option value="'.$sev.'" selected >'.$sev.'</option>';
		$out .= '<tr valign="top"><th scope="row">Numero Ragazzi (12-18)</th>
					<td><select name="URagazzi">'.$sel.$sel10.' '.$evU['URagazzi'].'</td>
				</tr>';
		$sev = $evU['UBambini5']=='' ? '0':$evU['UBambini5'];
		$sel = '<option value="'.$sev.'" selected >'.$sev.'</option>';
		$out .= '<tr valign="top"><th scope="row">Numero Bambini (6-11)</th>
					<td><select name="UBambini5">'.$sel.$sel10.' '.$evU['UBambini5'].'</td>
				</tr>';
		$sev = $evU['UBambini0']=='' ? '0':$evU['UBambini0'];
		$sel = '<option value="'.$sev.'" selected >'.$sev.'</option>';
		$out .= '<tr valign="top"><th scope="row">Numero Bambini (0-5)</th>
					<td><select name="UBambini0">'.$sel.$sel10.' '.$evU['UBambini0'].'</td>
				</tr>';
		$ckSI = $evU['UPartecipa'] == 'si' ? 'checked' : '';
		$ckNO = $evU['UPartecipa'] == 'no' || '' ? 'checked' : '';
		$out .= '<tr valign="top"><th scope="row">Partecipazione ad evento</th>
			<td><input style="width: 10px;" type="radio" name="UPartecipa" value="no" '.$ckNO.'>no
				<input style="width: 10px;" type="radio" name="UPartecipa" value="si" '.$ckSI.'>si</td>
			</tr>';
		$ckSI = $evU['UDisabili'] == 'si' ? 'checked' : '';
		$ckNO = $evU['UDisabili'] == 'no' || '' ? 'checked' : '';
		$out .= '<tr valign="top"><th scope="row">Presenza disabili</th>
			<td><input style="width: 10px;" type="radio" name="UDisabili" value="no" '.$ckNO.'>no
				<input style="width: 10px;" type="radio" name="UDisabili" value="si" '.$ckSI.'>si</td>
			</tr>';
		$out .= '<tr valign="top"><th scope="row" style="vertical-align: top;">Note</th>
				<td><textarea style="width: 100%;" rows="3" name="UNote">'.$evU['UNote'].'</textarea></td>
			</tr>';			
		return $out;
	}
	
	public function partecipanti_6r( $eventID, $idOwner  )
	{
		$out = '<tr valign="top"><td colspan="2" class="stacco"></td></tr>';
		$out .= '<tr valign="top"><td colspan="2" class="headerlistapartecipanti">Lista partecipanti</td></tr>';
		$iscritti6r = get_option ( $this->wpf_code.'6riscritti');
		$cntiscr = 0;
		if ($iscritti6r!=false)
			foreach ($iscritti6r as $iscritto) {
				//estrazione iscritti all'evento in oggetto
				if ( $iscritto['UevID'] == $eventID  ) {
					//if ($cntiscr == 0)
					//	$out .= '<tr class="iscritto" valign="top"><td colspan="2" align="left">Nome  | NrCani | NrAdulti | NrRagazzi | NrBambini | NrBambini<5anni  | Note </td></tr>';
					$cntiscr++;
					//$info = ' ID='.$iscritto['idU'].' ('.$iscritto['UMail'].') '.$iscritto['UCani'].' '.$iscritto['UAdulti'].' '.$iscritto['URagazzi'].' '.$iscritto['UBambini5'].'  '.$iscritto['UBambini0'].'  '.$iscritto['UPartecipa'].'  '.$iscritto['UDisabili'].'  ['.$iscritto['UNote'].']';
					$note = $iscritto['UNote']==''?'-- nessuna nota --':$iscritto['UNote'];
					$info = ' Adulti:<strong>'.$iscritto['UAdulti'].'</strong> Ragazzi 12-18:<strong>'.$iscritto['URagazzi'].'</strong> Bambini 6-11:<strong>'.$iscritto['UBambini5'].'</strong> Bambini 0-5:<strong>'.$iscritto['UBambini0'].'</strong> Animali:<strong>'.$iscritto['UCani'].'</strong> <span title="'.$note.'"><u>NOTE</u></span>';
					$out .= '<tr class="iscritto" valign="top"><td colspan="2"><i>nome:</i> <strong>'.$iscritto['UName'].'</strong></td></tr>';
					$out .= '<tr class="iscrittodettagli" valign="top"><td colspan="2"><i>dettagli:</i>'.$info.'</td></tr>';
				}
		}
		if ($cntiscr == 0)
			$out .= '<tr class="iscritto" valign="top"><td colspan="2" align="center">-- ancora nessun iscritto --</td></tr>';
		return $out;
	}

	public function delete_event6r($delete_eventid, $remove_event, $notify)
	{
		// qui invia la email, perche' in editazione non lo fa e perche' mi serve la lista iscritti
		if ( $remove_event == 'YES' AND $notify == true )
			$this->fwp_notifyEvent( -1, $delete_eventid );
		//estrae iscritti all'evento ID UevID
		$iscrittieventi = get_option( $this->wpf_code.'6riscritti' );
		$cntisc = 0;
		if ( $iscrittieventi != null ) {
			foreach ( $iscrittieventi as $iscritto ) {
				//estrazione iscritti all'evento in oggetto
				if ( $iscritto['UevID'] == $delete_eventid )
					unset ( $iscrittieventi[$cntisc] );
				$cntisc++;
			}
			//ricompatta
			$iscrittieventi = array_values( $iscrittieventi );
			update_option ( $this->wpf_code.'6riscritti', $iscrittieventi );
			echo 'DELETED Users at Event ID='.$delete_eventid.'<br/>';			
		}
	
		if ( $remove_event == 'YES' ) {
			//estrae elenco eventi
			$events = stripslashes_deep( get_option ( $this->wpf_code.'6ruote') );
			
			$cnt = 0;
			foreach ( $events as $event) {
				if ($event['ID']==$delete_eventid)
					unset ( $events[$cnt] );
				$cnt++;		
			}
			//ricompatta
			$events = array_values( $events );
			update_option ( $this->wpf_code.'6ruote', $events );
			echo '... DELETED Event ID='.$delete_eventid.'<br/>';
		}
	}
	
	public function archive_fevent6( $eventID )
	{
		$outtxt = '';
		$this->fwp_log( 'archivia evento id='.$eventID);
		
		//1. genera campi della "locandina" + file csv dei partecipanti a admin@seiruote.info da allegare
		$events = stripslashes_deep( get_option ( $this->wpf_code.'6ruote') );
		foreach ( $events as $event) {
			if ($event['ID']==$eventID)
				$evento = $event;
		}
		foreach ( $evento as $key => $val ) {
			$outtxt .= str_replace( 'User_', '', $key ) .': '. $val . chr(13);
		}
		$outtxt .= chr(13);
		
		//2. genera lista iscritti 
		/* 11 campi UCani, UAdulti, URagazzi, UBambini5, UBambini0, UPartecipa,UDisabili,UNote*/
		$outtxt .= '"UserID","User","Email","Animali","Adulti","Ragazzi","Bambini5","Bambini0","Eventi","Disabili","Note"' . chr(13);
		//estrae iscritti all'evento ID
		$iscrittieventi = get_option( $this->wpf_code.'6riscritti' );
		$cnt = 0;

		if ( $iscrittieventi != null )
		{
			foreach ( $iscrittieventi as $iscritto ) {
				//estrazione iscritti all'evento in oggetto
				if ( $iscritto['UevID'] == $eventID )
				{
					$cnt++;
					$user = get_userdata( $iscritto['idU'] );
					$username = $user->user_firstname.' '.$user->user_lastname;
					$email = $user->user_email;

					$outtxt .= '"';
					$outtxt .= implode( '","', array ( $iscritto['idU'], $username, $email, $iscritto['UCani'], $iscritto['UAdulti'], $iscritto['URagazzi'], $iscritto['UBambini5'], $iscritto['UBambini0'], $iscritto['UPartecipa'], $iscritto['UDisabili'], $iscritto['UNote']) ) . '"' . chr(13);
				}
			}
		}
		$outtxt .= 'Totale iscritti:'.$cnt;
		
		//3. invia email all'amministratore
		$this->fwp_log( $outtxt );
		$headers = "From: ".get_option('blogname')." <".get_option('admin_email')."> \r\n";
		$recipients[] = get_option('admin_email');
		if ( wp_mail( $recipients, 'Archiviazione evento', str_replace ( '\r', '<br\>', $outtxt ), $headers ) ) {
			 $this->fwp_log("INVIATA EMAIL Archiviazione evento a ".implode( ",", $recipients ) );
	 		//4. cancella evento senza notificare gli iscritti
			if (DEBUG_LEVEL == 2)
				$this->delete_event6r( $eventID, 'YES', false );
		}
	}

	public function generate_csv6r()
	{
		//form cancellazione
		if ( isset($_POST['remove_event']) )
			if ( $_POST['remove_event']=='YES' ) 
				return;
		if ( isset($_POST['UevID']) ) {
			if ( isset($_POST['cmdAderisci6r']) )
				return;

			$idEvent = $_POST['UevID'];
			if ( $_POST['InviaMail6r']=='YES' ) {
				$this->fwp_notifyPartecipants( $_POST['InviaMail6r_text'], $idEvent );
				return;
				}

			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) )
				$sitename .= '_';
			$filename = $sitename . 'users@event#' . $idEvent . 'T' . date( 'Y-m-d-H-i-s' ) . '.csv';

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );


			/* 11 campi UCani, UAdulti, URagazzi, UBambini5, UBambini0, UPartecipa,UDisabili,UNote*/
			echo '"UserID","User","Email","Animali","Adulti","Ragazzi","Bambini5","Bambini0","Eventi","Disabili","Note"' . chr(13);
			//estrae iscritti all'evento ID
			$iscrittieventi = get_option( $this->wpf_code.'6riscritti' );
			$cnt = 0;

			if ( $iscrittieventi != null )
			{
				foreach ( $iscrittieventi as $iscritto ) {
					//estrazione iscritti all'evento in oggetto
					if ( $iscritto['UevID'] == $idEvent )
					{
						$cnt++;
						$user = get_userdata( $iscritto['idU'] );
						$username = $user->user_firstname.' '.$user->user_lastname;
						$email = $user->user_email;

						echo '"';
						echo implode( '","', array ( $iscritto['idU'], $username, $email, $iscritto['UCani'], $iscritto['UAdulti'], $iscritto['URagazzi'], $iscritto['UBambini5'], $iscritto['UBambini0'], $iscritto['UPartecipa'], $iscritto['UDisabili'], $iscritto['UNote']) ) . '"' . chr(13);
					}
				}
			}
			echo 'Totale iscritti:'.$cnt;
			exit;
		}
	}
			
	/************** Notifiche email
	*/
	public function fwp_notifyPartecipants( $content, $idEvent )
	{
		//email inviate dall'organizzatore dell'evento
		$options = stripslashes_deep( get_option ( $this->wpf_code.'6ruote') );
		$evento = $options[$idEvent];
		$this->fwp_log('Notifica per i partecipanti all\'evento id='.$idEvent);
			
		//to get users emails
		$recipients = array();
		$blogusers = get_users();
		foreach ($blogusers as $user) {
			if ( $this->fwp_is_user_subscribed( $user->ID, $idEvent ) ) //solo quelli che sono iscritti
				$recipients[] = $user->user_email;
		}
		
		$data = (array) get_userdata( get_current_user_id() );
        $data = (array) $data['data'] ;
		$headers[] = "Reply-to: ".$data['user_nicename']." <".$data['user_email'].">";
		$headers[] = "Bcc: admin@seiruote.info";
		$content .= "<hr/>Rispondere a: ".$data['user_email'];
		$subj = 'Notifica per i partecipanti all\'evento seiruote.info';
		//send email to users
		$this->fwp_send_email( $subj, $content, $recipients, $idEvent, $headers );
	}
	
	public function fwp_notifyEventInscription ( $isnew, $isdel, $idEvent )
	{
		$data = (array) get_userdata( get_current_user_id() );
        $data = (array) $data['data'] ;
		$data['blogname'] = get_option('blogname');
		//$headers = "From: ".$data['blogname']." <".get_option('admin_email')."> \r\n";
		$headers = "From: ".$data['user_nicename']." <".$data['user_email']."> \r\n";
		$action = $isnew ? 'aggiunta' : 'modificata';
	
		$logstring = 'In data '.date_i18n(get_option('date_format'), time()) . ' ['.current_time( 'mysql' ).'] ';
		$logstring .= '\r\ne\' stata '.$action.' la partecipazione di '.$data['user_email'].' all\'evento con id='. $idEvent;
		if ( $isdel ) $logstring .= ' che si e\' CANCELLATO';
		$this->fwp_log("INVIO EMAIL ***TESTO: ".$logstring);
		
		$report .= $logstring.'<hr>';
		$recipients = array('admin@seiruote.info');
		
		add_filter( 'wp_mail_content_type', array( $this,'set_html_content_type') );
		if ( wp_mail( $recipients, 'Report modifica iscritti', str_replace ( '\r\n', '<br\>', $report ), $headers ) ) $this->fwp_log("INVIATA EMAIL ");
		remove_filter( 'wp_mail_content_type', array( $this,'set_html_content_type') );
	}
	
	public function fwp_notifyEvent( $type, $idEvent )
	{
		if ($type==-2)
			return;
		if ($type==1)
			$this->fwp_log('Notifica nuovo evento id='.$idEvent);
		if ($type==0)
			$this->fwp_log('Notifica aggiornamento evento id='.$idEvent);
		if ($type==-1)
			$this->fwp_log('Notifica cancellazione evento id='.$idEvent);
			
		//to get users emails
		$recipients = array();
		// get_the_author_meta( 'emaileventi', $user->ID )=="true" NON devo spedire
		//$blogusers = get_users('orderby=login&meta_key=emaileventi&meta_value=true&meta_compare=!=');
		$blogusers = get_users();
		foreach ( $blogusers as $user ) {
		//$this->fwp_log('User='.$user->ID." type=".$type." NOemail:".$user->emaileventi);
			if ( $type==1 ) {
				//nuovo evento
				if ( !($user->emaileventi==="true") ) //escludo quelli che NON accettano email
					$recipients[] = $user->user_email;
			}
			else {
				//modifica o cancellazione
				if ( $this->fwp_is_user_subscribed( $user->ID, $idEvent ) ) //escludo quelli che non sono iscritti
					$recipients[] = $user->user_email;
			}
		}
		//prepare email text
		$this->fwp_prepare_email( $type, $recipients, $idEvent );
	}
	
	private function fwp_is_user_subscribed ( $userid, $eventid )
	{
		//estrae iscritti all'evento ID UevID
		$iscrittieventi = get_option( $this->wpf_code.'6riscritti' );
		if ( $iscrittieventi != null ) {
			foreach ( $iscrittieventi as $iscritto ) {
				//$this->fwp_log('User='.$userid.' evid='. $eventid.'iscr_idU'.$iscritto['idU'].'iscrUevID'.$iscritto['UevID']);
				//estrazione iscritti all'evento in oggetto
				if ( $iscritto['UevID'] == $eventid )
					if ( $iscritto['idU'] == $userid )
						return true; //utente $userid iscritto all'evento $eventid
			}	
		}
		return false;
	}
	
	private function fwp_prepare_email( $type, $recipients, $idEvent )
	{
		switch ($type)	{
			case 1:
				$subj = 'seiruote.info: Inserito nuovo Evento';
				//e-mail comunicazione evento
				$testo = '&egrave; stato pubblicato un nuovo evento. Gli eventi proposti sul nostro sito sono le <strong>iniziative di incontro suggerite dagli altri utenti.</strong><br/>Per dare la Tua adesione all\'evento, esegui il login e compila la scheda di partecipazione con i dati richiesti. ';
				$testo .= 'Salvo diversa indicazione, gli eventi sono organizzati e pianificati in modo autonomo dagli utenti proponenti e seiruote.info non interviene in alcun modo nella gestione delle iniziative proposte; la partecipazione &egrave; libera e in qualsiasi momento &egrave; possibile revocarla.<br/><br/>Se non vuoi ricevere pi&ugrave; le notifiche, clicca sul tuo nick in alto a destra, seleziona "modificare il profilo" e spuntare la voce relativa alle notifiche.';
			break;
			case 0:
				$subj = 'seiruote.info: Modifica Evento';
				//e-mail modifica evento
				$testo = 'sono state apportate delle variazioni al programma dell’evento a cui ti sei iscritto.  Per verificare le novit&agrave; introdotte, esegui il login e consulta l’elenco delle iniziative in corso.';

			break;
			case -1:
				$subj = 'seiruote.info: Cancellazione Evento';
				//e-mail revoca evento
				$testo = '&egrave; stato revocato l\'evento a cui ti sei iscritto. Per verificare le novit&agrave; introdotte, esegui il login e consulta l\'elenco delle iniziative in corso.';
			break;
			}

		$content = 'Gentile utente,<br/>
		ti comunichiamo che sul sito <a href="http://www.seiruote.info">www.seiruote.info</a> nella sezione "Idee per il Week End" '.$testo.'<br/><hr>';
		
		$this->fwp_send_email( $subj, $content, $recipients, $idEvent );
	}
	
	private function fwp_send_email( $subj, $content, $recipients, $idEvent, $headers="" )
	{
		$data = (array) get_userdata( get_current_user_id() );
        $data = (array) $data['data'] ;
		$data['blogname'] = get_option('blogname');
		//$headers[] = "From: seiruote.info <admin@seiruote.info>";
		$headers[] = "From: ".$data['user_nicename']." <".$data['user_email'].">";
	
		$contentlog = 'In data '.date_i18n(get_option('date_format'), time()) . ' ['.current_time( 'mysql' ).'] ';
		$contentlog .= ' e\' stato inserito/modificato/inviata notifica da '.$data['user_email'].' - evento con id='. $idEvent;
		$cntisc = 0;
		$eventi6r = stripslashes_deep( get_option ( $this->wpf_code.'6ruote') );
		foreach ($eventi6r as $ev6r) {
			//estrazione eventi 6ruote
			if ( $ev6r['ID'] == $idEvent ) {
				$content .= sprintf (' >> Descrizione: Dal %s al %s - Luogo: %s - Campeggio: %s',
				$eventi6r[$cntisc]['User_dataEventoDal'],
				$eventi6r[$cntisc]['User_dataEventoAl'],
				$eventi6r[$cntisc]['User_Localita'],
				$eventi6r[$cntisc]['User_NomeCamp']);
			}
			$cntisc++;
		}
		$logstring = " Email da ";
		foreach ($headers as $header)
			$logstring .= $header." - ";
		$logstring .= " agli indirizzi: ".implode(", ", $recipients);
		$logstring .= "\r\n******* INFO: ".$contentlog;
		$logstring .= "\r\n******* OGGETTO: ".$subj;
		$logstring .= "\r\n******* TESTO: ".$content;
		$this->fwp_log("INVIO EMAIL ".$logstring);
		
		add_filter( 'wp_mail_content_type', array( $this,'set_html_content_type') );
		$report = 'REPORT invio email del '.date_i18n(get_option('date_format'), time()) . ' ['.current_time( 'mysql' ).'] ';
		$report .= $logstring.'<hr>';
		if (DEBUG_LEVEL == 4) {
			$recipients = array();
		}	
		foreach ($recipients as $recip) {
			if ( wp_mail( $recip, $subj, $content, $headers ) ) {
				$this->fwp_log("EMAIL INVIATA a ".$recip);
				$report .= '<br>EMAIL INVIATA a '.$recip;
				}
			else {
				$this->fwp_log("+++ERRORE EMAIL INVIO a ".$recip);
				$report .= '<br>+++ERRORE EMAIL INVIO a '.$recip;
			}
		}
		// send report email to admin
		$recipients = get_option('admin_email');
		wp_mail( $recipients, 'Report invio email', str_replace ( '\r\n', '<br\>', $report ), $headers );
		remove_filter( 'wp_mail_content_type', array( $this,'set_html_content_type') );
	}
		
	function set_html_content_type()
	{
		return 'text/html';
	}
	
	/************** LOG
	* Levels are: 1 for errors, 2 for normal activity, 3 for debug.
	*/
	public function fwp_log( $text='', $level=2 )
	{
	    if (DEBUG_LEVEL < $level) return;

	    //$db = debug_backtrace(false);
	    $time = date('d-m-Y H:i:s ');
	    switch ($level) {
	        case 1: $time .= '- ERROR';
	            break;
	        case 2: $time .= '- INFO ';
	            break;
	        case 3: $time .= '- DEBUG';
	            break;
	    }
	    if (is_array($text) || is_object($text)) $text = print_r($text, true);
	    file_put_contents(dirname(__FILE__) . '/log.txt', $time . ' - ' . $text . "\n", FILE_APPEND | FILE_TEXT);
	}
	
	/************** User Info addizionali
	*/
	function fwp_add_custom_user_profile_fields( $user )
	{
	?>
	<h3><?php _e('Consenso invio email', 'fwp_fevents_6r'); ?></h3>
	<table class="form-table">
		<tr>
			<th>
				<?php _e('Nessun invio email per gli eventi seiruote.info', 'fwp_fevents_6r'); ?>
			</th>
			<td>
				<label for="emaileventi">
				<input type="checkbox" name="emaileventi" id="emaileventi" value="true" <?php if (esc_attr( get_the_author_meta( "emaileventi", $user->ID )) == "true") echo "checked"; ?> />
				<?php _e('Selezionare se NON si desidera ricevere informazioni su nuove iniziative o su modifiche alle inizaitive esistenti', 'fwp_fevents_6r'); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php }
	
	function fwp_save_custom_user_profile_fields( $user_id )
	{
		if ( !current_user_can( 'edit_user', $user_id ) )
			return FALSE;
		if (!isset($_POST['emaileventi'])) $_POST['emaileventi'] = "false"; 
		update_usermeta( $user_id, 'emaileventi', $_POST['emaileventi'] );
	}

}
?>