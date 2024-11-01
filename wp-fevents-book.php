<?php
/* class name must be unique */

class FEventsBook
{

	private $wpf_path;
	private $wpf_name;
	private $wpf_code;
	private $VER;
	private $wpf_loglevel = 0;

	public function __construct( $name, $code, $base_path, $VER = '0.0', $option_page = true, $fmenu_page = false )
	{
		$this->VER = $VER;
		$this->wpf_name = $name;
		$this->wpf_code = $code;
		$this->wpf_path = plugins_url( $base_path );

		add_action( 'plugins_loaded', array( $this, 'init_fplugin' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'mw_enqueue_color_picker' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wpapi_date_picker' ) );

		// voice menu entry in 'Settings'
		if ( $option_page ) {
			//options setting
			add_action( 'admin_init', array( $this, 'add_foption_init' ) );
			//menu setting
			add_action( 'admin_menu', array( $this, 'add_foption_page' ) );
		}
		// voice menu entry in any other submenu
		if ( $fmenu_page ) {
			add_action( 'admin_menu', array( $this, 'add_fsubmenu_page' ) );
		}

		// Load i18n language support
		load_plugin_textdomain( 'wp-fevents-book', false, basename( dirname( __FILE__ ) ) . '/languages' );

		add_shortcode( 'feventsbook', array( $this, 'add_fshortcode' ) );
		//register_uninstall_hook( $file, array( $this, 'uninstall_fplugin' ) );
		//save csv
		add_action( 'init', array( $this, 'generate_csv' ) );

		add_filter( 'plugin_action_links', array( $this, 'wp_fevents_book_action_links' ), 10, 2 );
	}

	public function wp_fevents_book_action_links( $links, $file )
	{
		$this_plugin = plugin_basename( __FILE__ );
		// check to make sure we are on the correct plugin
		if ( str_replace( 'init.php', 'wp-fevents-book.php', $file ) == $this_plugin ) {
			// the anchor tag and href to the URL we want. For a "Settings" link, this needs to be the url of your settings page
			$settings_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/options-general.php?page=feventsbook">Settings</a>';
			// add the link to the list
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	public function load_styles()
	{
		wp_register_style( 'feventsbook', $this->wpf_path . 'style.css' );
		wp_enqueue_style( 'feventsbook' );
	}

	public function load_scripts()
	{
		/*
		  wp_register_script( 'fpluginxxxxx', $this->wpf_path.'jscript.js' );
		  wp_enqueue_script( 'fpluginxxxxx' );
		 */
	}

	/* REF.: http://make.wordpress.org/core/2012/11/30/new-color-picker-in-wp-3-5/ */

	public function mw_enqueue_color_picker()
	{
		// first check that $hook_suffix is appropriate for your admin page
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'my-script-handle', plugins_url( 'wp-fevents.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
	}

	public function wpapi_date_picker()
	{
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'my-style', plugins_url( 'style-datepicker.css', __FILE__ ), false, $this->VER, 'all' );
		/* TODO: fix save $options['Scadenza'] in UNIX timestamp and display it in local language
		  $lang = substr( get_bloginfo('language'), 0, 2 );
		  $avail = array( "it", "de", "fr", "cr" );
		  if ( in_array( $lang, $avail ) )
		  wp_enqueue_script( 'jquery.ui.datepicker-i18n', plugins_url( '/languages/jquery.ui.datepicker-'.$lang.'.min.js',__FILE__ ));
		 */
	}

	public function init_fplugin()
	{
		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	// options stored in DB entry '$this->wpf_code'
	function add_foption_init()
	{
		// register_setting( $option_group, $option_name, $sanitize_callback )
		// settings_fields( $option_group )
		register_setting( $this->wpf_code . '_options', $this->wpf_code, array( $this, 'foptions_validate' ) );
	}

	public function add_foption_page()
	{
		add_options_page( $this->wpf_name . ' settings', $this->wpf_name, 'manage_options', $this->wpf_code, array( $this, 'plugin_fconfigure' ) );
	}

	public function add_fsubmenu_page()
	{
		// ref: http://codex.wordpress.org/Function_Reference/add_submenu_page
		$parent_slug = 'tools.php';
		$page_title = $this->wpf_name;
		$menu_title = $this->wpf_name;
		$capability = 'read';
		$menu_slug = $this->wpf_code;
		$function = array( $this, 'echo_fsubmenu' );
		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	// Sanitize and validate input. Accepts an array of arrays, return a sanitized array of arrays.
	function foptions_validate( $inputA )
	{
		if ( isset( $_POST['delete_eventid'] ) ) {
			return $inputA;
		}
		$cnt = 0;
		foreach ( $inputA as $input ) {
			// value is either 0 or 1
			if ( isset( $input['attivo'] ) ) {
				$inputA[$cnt]['attivo'] = ( $input['attivo'] == 1 ? 1 : 0 );
			} else {
				$inputA[$cnt]['attivo'] = 0;
			}
			if ( isset( $input['NotLoggedCanBook'] ) ) {
				$inputA[$cnt]['NotLoggedCanBook'] = ( $input['NotLoggedCanBook'] == 1 ? 1 : 0 );
			} else {
				$inputA[$cnt]['NotLoggedCanBook'] = 0;
			}

			// default altre opzioni
			if ( !isset( $input['userid'] ) ) {
				$inputA[$cnt]['userid'] = 1;
			}
			if ( !isset( $input['userid_adm'] ) ) {
				$inputA[$cnt]['userid_adm'] = 0;
			}
			if ( !isset( $input['user_total'] ) ) {
				$inputA[$cnt]['user_total'] = 1;
			}

			// must be safe text with no HTML tags
			$inputA[$cnt]['Descrizione'] = wp_filter_nohtml_kses( $input['Descrizione'] );
			//if ( $inputA[$cnt]['Descrizione'] == '' ) $inputA[$cnt]['Descrizione'] = 'new';
			// data scadenza
			if ( !isset( $input['Scadenza'] ) ) {
				$inputA[$cnt]['Scadenza'] = '';
			} else {
				$inputA[$cnt]['Scadenza'] = $input['Scadenza'];
				/* TODO //convert to UNIX datestamp!!
				  $dateformat = get_option('date_format');
				  $a = strptime( $input['Scadenza'], $dateformat );
				  $timestamp = mktime(0, 0, 0, $a['tm_mon']+1, $a['tm_mday'], $a['tm_year']+1900);
				  $inputA[$cnt]['Scadenza'] = $timestamp;
				 */
			}


			// default visibile ai non loggati
			if ( isset( $input['LoggedOnly'] ) ) {
				$inputA[$cnt]['LoggedOnly'] = ( $input['LoggedOnly'] == 1 ? 1 : 0 );
			} else {
				$inputA[$cnt]['LoggedOnly'] = 0;
			}
			$cnt++;
		}
		if ( $inputA[$cnt - 1]['Descrizione'] == '' ) {
			return array_slice( $inputA, 0, $cnt - 1 );
		}
		return $inputA;
	}

	public function plugin_fconfigure()
	{
		if ( isset( $_POST['delete_eventid'] ) ) {
			$this->delete_event( $_POST['delete_eventid'], $_POST['remove_event'] );
		}
		echo '<form name="wpfeventsbookdel" method="POST" action="' . $_SERVER["REQUEST_URI"] . '" enctype="multipart/form-data"  style="margin-bottom: 0px;">';
		echo '<input type="hidden" name="delete_eventid" value="-1">';
		echo '<input type="hidden" name="remove_event" value="NO">';
		echo '</form>';
		//evento defaults fields values
		$evento_default = array(
			'attivo' => '',
			'Colore' => '#fdf28e',
			'Descrizione' => '',
			'Data' => '',
			'Luogo' => '',
			'Scadenza' => '',
			'user_total' => '',
			'userid_adm' => '',
			'userid' => '',
			'usermail' => '',
			'scelta' => '',
			'teamlist' => '',
			'teamfirst' => '',
			'teamnumbers' => '',
			'maxusers' => '',
			'maxusersnr' => '',
			'scelte' => '',
			'userinfo' => '',
			'conferma' => '',
			'esporta' => '',
			'sceltatxt' => '',
			'scelta1' => '',
			'scelta2' => '',
			'scelta3' => '',
			'scelta4' => '',
			'Note' => '',
			'LoggedOnly' => '',
			'NotLoggedCanBook' => '',
		);
		?>
		<div class="wrap">
			<h2><?php echo $this->wpf_name . _e( ' Configuration Options ', 'wp-fevents-book' ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( $this->wpf_code . '_options' );
				$optionsA = stripslashes_deep( get_option( $this->wpf_code ) );
				if ( $optionsA == false ) {
					$cntA = 0;
				}
				else {
					$cntA = sizeof( $optionsA );
				}
				$cnt = 0;
				?>
				<table class="form-table">
					<?php
					while ( $cnt <=  $cntA) {
						$aridx = $this->wpf_code . '[' . $cnt . ']';
						if ( $cnt < $cntA ) {
							$options = array_merge( $evento_default, $optionsA[$cnt] );
						} else {
							$options = $evento_default;
						}
						?>
						<tr><th scope="row"><?php _e( 'Event ID', 'wp-fevents-book' ); ?></th>
							<td><input type="text" name="<?php echo $aridx; ?>[IDevento]" value="<?php echo $cnt; ?>" readonly="readonly"/></td>
						</tr>
						<tr><th scope="row"><?php _e( 'active Event', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[attivo]" type="checkbox" value="1" <?php checked( '1', $options['attivo'] ); ?> /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Color', 'wp-fevents-book' ); ?></th>
							<td><input type="text" name="<?php echo $aridx; ?>[Colore]" value="<?php echo $options['Colore']; ?>" class="my-color-field" data-default-color="#FDF28E"/></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Event Description', 'wp-fevents-book' ); ?></th>
							<td><span class="color: #FF0000">*</span><input type="text" name="<?php echo $aridx; ?>[Descrizione]" value="<?php echo $options['Descrizione']; ?>" /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Event Date', 'wp-fevents-book' ); ?></th>
							<td><input type="text" name="<?php echo $aridx; ?>[Data]" value="<?php echo $options['Data']; ?>" /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Event Place', 'wp-fevents-book' ); ?></th>
							<td><input type="text" name="<?php echo $aridx; ?>[Luogo]" value="<?php echo $options['Luogo']; ?>" /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Expire Date', 'wp-fevents-book' ); ?></th>
							<td><input type="text" name="<?php echo $aridx; ?>[Scadenza]" value="<?php echo $options['Scadenza']; ?>"  class="my-datepicker" /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'show user choices', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[scelte]" type="checkbox" value="1" <?php checked( '1', $options['scelte'] ); ?> />
								<?php _e( 'text to show:', 'wp-fevents-book' ); ?> <input type="text" name="<?php echo $aridx; ?>[sceltatxt]" value="<?php echo $options['sceltatxt']; ?>" /></td>
						</tr>
						<tr><th scope="row"></th><td>
								<?php _e( 'choice', 'wp-fevents-book' ); ?>#1:<input type="text" name="<?php echo $aridx; ?>[scelta1]" value="<?php echo $options['scelta1']; ?>" />
								<?php _e( 'choice', 'wp-fevents-book' ); ?>#2:<input type="text" name="<?php echo $aridx; ?>[scelta2]" value="<?php echo $options['scelta2']; ?>" />
								<?php _e( 'choice', 'wp-fevents-book' ); ?>#3:<input type="text" name="<?php echo $aridx; ?>[scelta3]" value="<?php echo $options['scelta3']; ?>" />
								<?php _e( 'choice', 'wp-fevents-book' ); ?>#4:<input type="text" name="<?php echo $aridx; ?>[scelta4]" value="<?php echo $options['scelta4']; ?>" /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'show Confirmation checkbox', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[conferma]" type="checkbox" value="1" <?php checked( '1', $options['conferma'] ); ?> /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'show User email', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[usermail]" type="checkbox" value="1" <?php checked( '1', $options['usermail'] ); ?> /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'show User info', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[userinfo]" type="checkbox" value="1" <?php checked( '1', $options['userinfo'] ); ?> /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'show User', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[userid]" type="radio" value="1" <?php checked( '1', $options['userid'] ); ?>> <?php _e( 'name and surname', 'wp-fevents-book' ); ?>
								<input name="<?php echo $aridx; ?>[userid]" type="radio" value="2" <?php checked( '2', $options['userid'] ); ?>> <?php _e( 'nickname', 'wp-fevents-book' ); ?>
								<input name="<?php echo $aridx; ?>[userid]" type="radio" value="3" <?php checked( '3', $options['userid'] ); ?>> <?php _e( 'login id', 'wp-fevents-book' ); ?>
								<input name="<?php echo $aridx; ?>[userid]" type="radio" value="4" <?php checked( '4', $options['userid'] ); ?>> <?php _e( 'display name', 'wp-fevents-book' ); ?>
							</td>
						</tr>
						<tr><th scope="row"><?php _e( 'show User', 'wp-fevents-book' ); ?>@admin</th>
							<td><input name="<?php echo $aridx; ?>[userid_adm]" type="radio" value="0" <?php checked( '0', $options['userid_adm'] ); ?>> <?php _e( 'all', 'wp-fevents-book' ); ?>
								<input name="<?php echo $aridx; ?>[userid_adm]" type="radio" value="1" <?php checked( '1', $options['userid_adm'] ); ?>> <?php _e( 'name and surname', 'wp-fevents-book' ); ?>
								<input name="<?php echo $aridx; ?>[userid_adm]" type="radio" value="2" <?php checked( '2', $options['userid_adm'] ); ?>> <?php _e( 'nickname', 'wp-fevents-book' ); ?>
								<input name="<?php echo $aridx; ?>[userid_adm]" type="radio" value="3" <?php checked( '3', $options['userid_adm'] ); ?>> <?php _e( 'login id', 'wp-fevents-book' ); ?>
								<input name="<?php echo $aridx; ?>[userid_adm]" type="radio" value="4" <?php checked( '4', $options['userid_adm'] ); ?>> <?php _e( 'display name', 'wp-fevents-book' ); ?>
							</td>
						</tr>
						<tr><th scope="row"><?php _e( 'show Total users', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[user_total]" type="radio" value="1" <?php checked( '1', $options['user_total'] ); ?>> <?php _e( 'booked', 'wp-fevents-book' ); ?>
								<input name="<?php echo $aridx; ?>[user_total]" type="radio" value="2" <?php checked( '2', $options['user_total'] ); ?>> <?php _e( 'confirmed', 'wp-fevents-book' ); ?>
								<input name="<?php echo $aridx; ?>[user_total]" type="radio" value="3" <?php checked( '3', $options['user_total'] ); ?>> <?php _e( 'both', 'wp-fevents-book' ); ?>
							</td>
						</tr>
						<tr><th scope="row"><?php _e( 'Event Info', 'wp-fevents-book' ); ?></th>
							<td><textarea rows="3" cols="80" style="height: 100px; width: 60%;" name="<?php echo $aridx; ?>[Note]"><?php echo $options['Note']; ?></textarea></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Allow data export', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[esporta]" type="checkbox" value="1" <?php checked( '1', $options['esporta'] ); ?> /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Team list', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[teamlist]" type="checkbox" value="1" <?php checked( '1', $options['teamlist'] ); ?> /> <?php _e( 'with', 'wp-fevents-book' ); ?> <input type="number" name="<?php echo $aridx; ?>[teamnumbers]" value="<?php echo $options['teamnumbers']; ?>"  min="1" max="50" /> <?php _e( 'elements.', 'wp-fevents-book' ); ?>
								<?php _e( 'First number:', 'wp-fevents-book' ); ?> <input type="number" name="<?php echo $aridx; ?>[teamfirst]" value="<?php echo $options['teamfirst']; ?>"  min="0"/></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Max users allowed to subscribe', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[maxusers]" type="checkbox" value="1" <?php checked( '1', $options['maxusers'] ); ?> />
								<?php _e( 'Max number:', 'wp-fevents-book' ); ?> <input type="number" name="<?php echo $aridx; ?>[maxusersnr]" value="<?php echo $options['maxusersnr']; ?>"  min="1"/></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Visible to Logged only users', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[LoggedOnly]" type="checkbox" value="1" <?php checked( '1', $options['LoggedOnly'] ); ?> /></td>
						</tr>
						<tr><th scope="row"><?php _e( 'Not Logged users can book', 'wp-fevents-book' ); ?></th>
							<td><input name="<?php echo $aridx; ?>[NotLoggedCanBook]" type="checkbox" value="1" <?php checked( '1', $options['NotLoggedCanBook'] ); ?> /></td>
						</tr>
						<tr><td colspan="2">
								<p class="submit">
									<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wp-fevents-book' ) ?>" />
									<?php
									if ( '' != $options['Descrizione'] ) {
										$evdesc = ' ID=' . $cnt . ' [' . $options['Descrizione'] . '] ';
										$question = __( 'Delete all Users booked to the event with ', 'wp-fevents-book' ) . $evdesc . '?';
										?>
										<input type="submit" class="button-primary" value="<?php _e( 'Clear booked users list', 'wp-fevents-book' ) ?>"
											   onclick="var r = confirm('<?php echo $question; ?>');
																	   if (r === true) {
																		   document.forms['wpfeventsbookdel'].elements[0].value =<?php echo $cnt; ?>;
																		   document.forms['wpfeventsbookdel'].submit();
																	   }
																	   return false;">
												<?php if ( $cnt == sizeof( $optionsA ) - 1 ) { //cancellabile solo l'ultimo evento! gli utenti contengono ID evento (sic!) ?>
											<input type="submit" class="button-primary" value="<?php _e( 'Delete Event and its booked users', 'wp-fevents-book' ) ?>" onclick="var r = confirm('<?php
													_e( 'Delete Event', 'wp-fevents-book' );
													echo $evdesc;
													_e( 'and all its booked users?', 'wp-fevents-book' );
													?>');
																		if (r === true) {
																			document.forms['wpfeventsbookdel'].elements[0].value =<?php echo $cnt; ?>;
																			document.forms['wpfeventsbookdel'].elements[1].value = 'YES';
																			document.forms['wpfeventsbookdel'].submit();
																		}
																		return false;">
													<?php
												}
											} else {
												echo _e( 'adding this new event', 'wp-fevents-book' );
											}
											?>
								</p>
							</td></tr>
						<tr><td colspan="2"><hr/></td></tr>
						<?php
						if ( '' == $options['Descrizione'] ) {
							break;
						}
						$cnt++;
					}
					?>
				</table>
			</form>
		</div>
		<?php
	}

	public function echo_fsubmenu()
	{
		echo 'PAGINA SUBMENU DI ' . $this->wpf_name;
	}

	public function add_fshortcode( $atts )
	{
		extract( shortcode_atts( array(
			'eventid' => -1
						), $atts ) );

		$options = stripslashes_deep( get_option( $this->wpf_code ) );
		//evento defaults fields values
		$evento_default = array(
			'attivo' => '',
			'usermail' => '',
			'scelta' => '',
			'teamlist' => '',
			'scelte' => '',
			'userinfo' => '',
			'conferma' => '',
			'esporta' => '',
			'maxusers' => '',
			'maxusersnr' => '',
		);

		//nessun evento!!
		if ( $options == null ) {
			return "";
		}
		if ( $eventid != -1 ) {
			$evento = array_merge( $evento_default, $options[$eventid] );
			return $this->show_fshortcode( $eventid, $evento );
		} else {
			// loop su tutti gli eventi esistenti; visualizza quelli attivi
			$rethtml = "";
			$cnt = 0;
			foreach ( $options as $eventidloop ) {
				$eventol = array_merge( $evento_default, $eventidloop );
				$rethtml .= $this->show_fshortcode( $cnt, $eventol );
				$cnt++;
			}
			return $rethtml;
		}
	}

	// $eventid=event_id, $evento=event_options
	private function show_fshortcode( $eventid, $evento )
	{
		if ( !$evento['attivo'] ) {
			return '<!-- SHORTCODE NON ATTIVO DI ' . $this->wpf_name . ' con ID=' . $eventid . ' VER. ' . $this->VER . ' --><br/>';
		}
		if ( $_POST ) {
			$idUser = $_POST['idUser'];
			$idEvent = $_POST['idEvent'];
			$txtComments = $_POST['txtComments'];
			$boolConfrm = isset( $_POST['boolConfrm'] ) ? $_POST['boolConfrm'] : '';
			$usteam = isset( $_POST['NrUserTeam'] ) ? $_POST['NrUserTeam'] : '';
			$scelta = isset( $_POST['scelta'] ) ? $_POST['scelta'] : '';
			$cmd = $_POST['cmdSubmit'];
			$idUserName = isset( $_POST['idUserName'] ) ? $_POST['idUserName'] : ''; // set only if NotLoggedCanBook=1
			$nuovoiscritto = array(
				'idUser' => $idUser,
				'note' => $txtComments,
				'confrm' => $boolConfrm,
				'usteam' => $usteam,
				'scelta' => $scelta,
				'ID' => $idEvent,
				'idUserName' => $idUserName );

			if ( $eventid == $idEvent ) {
				if ( isset( $cmd ) ) {
					$this->do_save( $nuovoiscritto, $cmd, $eventid );
				}
			}
		}
		//^^^ POST
		//info utente loggato ( 0=non loggato )
		$current_user = wp_get_current_user();
		$idUser = $current_user->ID;
		$isuseradmin = $current_user->has_cap( 'edit_users' );

		$iscritto_default = array(
			'scelta' => ''
		);
		$elencoiscritti = array();
		//estrae iscritti all'evento ID
		$iscrittieventi = get_option( $this->wpf_code . 'iscritti' );
		$cntiscritti = 0;
		$userzip = get_user_meta( $idUser, 'zip' );
		$usernote = '';
		$usteam = 0;
		$uschoice = '';
		if ( $iscrittieventi != null ) {
			foreach ( $iscrittieventi as $iscritto ) {
				//estrazione iscritti all'evento in oggetto
				if ( $iscritto['ID'] == $eventid ) {
					$iscritto = array_merge( $iscritto_default, $iscritto );
					$usteama = $iscritto['usteam'] == '' ? 0 : $iscritto['usteam'];
					$elencoiscritti[$cntiscritti++] = array( $iscritto['idUser'], $iscritto['note'], $iscritto['confrm'], $usteama, $iscritto['scelta'], $iscritto['ID'], $iscritto['idUserName'] );
					if ( $iscritto['idUser'] == $idUser ) {
						// almeno un blank se l'utente e' gia' iscritto
						$usernote = $iscritto['note'] == '' ? ' ' : $iscritto['note'];
						$usteam = $iscritto['usteam'] == '' ? 0 : $iscritto['usteam'];
						$uschoice = $iscritto['scelta'];
					}
				}
			}
		}
		//crea output html
		if ( 0 == $idUser && $evento['LoggedOnly'] ) {
			$out = __( 'Not logged User - access to the service denied', 'wp-fevents-book' );
			$out .= ' <a href="' . wp_login_url( get_permalink() ) . '" title="Login">login</a>';
		} else {
			$out = $this->out_form_input( $evento, $eventid, $current_user, $idUser, $usernote, $usteam, $uschoice, $isuseradmin, $cntiscritti );
			$out .= $this->out_form_list( $evento, $elencoiscritti, $cntiscritti, $isuseradmin );
		}
		return '<!-- SHORTCODE DI ' . $this->wpf_name . ' con ID=' . $eventid . ' VER. ' . $this->VER . '--><br/>' . $out;
	}

	public function uninstall_fplugin()
	{

	}

	private function do_save( $nuovoiscritto, $cmd, $eventid )
	{
		if (function_exists('siwp_check_captcha')) {
			// make sure plugin is enabled before calling function
			if (false == siwp_check_captcha($err)) {
				$errors['captcha'] = $err;
				die('captcha: '.$err);
			}
		}
		$addnew = true;
		$delete_booking = ( __( 'DELETE', 'wp-fevents-book' ) == $cmd || __( 'Delete my booking!', 'wp-fevents-book' )  == $cmd );
		if ( !add_option( $this->wpf_code . 'iscritti', array( $nuovoiscritto ) ) ) {
			$iscrittieventi = get_option( $this->wpf_code . 'iscritti' );
			$this->fplugin_log( "iscritti", $iscrittieventi, 3 );
			$cntisc = 0;
			foreach ( $iscrittieventi as $iscritto ) {
				//estrazione iscritti all'evento in oggetto
				if ( $iscritto['ID'] == $eventid ) {
					if ( $iscritto['idUser'] == $nuovoiscritto['idUser'] ) {
						// l'utente e' gia' iscritto:
						$addnew = false;
						if ( $delete_booking ) {
							// lo elimino
							unset( $iscrittieventi[$cntisc] );
							$this->fplugin_log( "delete", $cntisc, 3 );
						} else {
							// o aggiorno le note
							$iscrittieventi[$cntisc]['note'] = $nuovoiscritto['note'];
							// ... la conferma ...
							$iscrittieventi[$cntisc]['confrm'] = $nuovoiscritto['confrm'];
							// ... il numero di squadra ...
							$iscrittieventi[$cntisc]['usteam'] = $nuovoiscritto['usteam'];
							// ... e la scelta
							$iscrittieventi[$cntisc]['scelta'] = $nuovoiscritto['scelta'];
						}
					}
				}
				$cntisc++;
			}
			//ricompatta
			$iscrittieventi = array_values( $iscrittieventi );
			if ( $addnew && !$delete_booking) {
				array_push( $iscrittieventi, $nuovoiscritto );
			}
			update_option( $this->wpf_code . 'iscritti', $iscrittieventi );
			$this->fplugin_log( "update", $iscrittieventi, 3 );
		}
	}

	public function out_form_input( $evento, $eventid, $current_user, $idUser, $usernote, $usteam, $uschoice, $isuseradmin, $cntiscritti )
	{
		$enablesubmit = 'submit';
		$allscanbook = $evento['NotLoggedCanBook'];
		if ( $idUser == 0 && $allscanbook == 0 ) {
			$username = __( 'Not logged in user, please login to book!', 'wp-fevents-book' );
			$username .= ' <a href="' . wp_login_url( get_permalink() ) . '" title="Login">login</a>';
			$enablesubmit = 'hidden';
		} else {
			if ( $idUser == 0 ) {
				$idUser = -($cntiscritti + 1); //NotLoggedCanBook: idUser negativo sicuramente minore del minimo presente
				$username = '<input type="text" name="idUserName" value="" size="5" style="width:250px;">';
			} else {
				$username = $current_user->user_firstname . ' ' . $current_user->user_lastname;
			}
		}
		$today = strtotime( 'now' );
		$exp_date = strtotime( $evento['Scadenza'] );
		//$exp_date = $evento['Scadenza']; //UNIX datestamp!!
		$exp_str = date_i18n( get_option( 'date_format' ), $exp_date );
		//utente attuale non iscritto
		if ( $usernote == '' ) {
			$btnval = __( 'BOOK ME!', 'wp-fevents-book' );
			$btnvad = '';
			if ( $evento['maxusers'] ) {
				if ( $cntiscritti >= $evento['maxusersnr'] ) {
					$btnval = __( 'Book NOT POSSIBLE; max number reached!', 'wp-fevents-book' );
				}
			}
		} else {
			$btnval = __( 'Update my booking!', 'wp-fevents-book' );
			$btnvad = __( 'Delete my booking!', 'wp-fevents-book' );
		}
		$out = '<div class="table-responsive" style="margin-bottom: 0px; border: 2px solid ' . $evento['Colore'] . '; border-radius: 5px; padding: 5px;">';
		$out .= '<form name="wpfeventsbookform_' . $eventid . '" method="POST" action="' . $_SERVER["REQUEST_URI"] . '" enctype="multipart/form-data"  style="margin-bottom: 0px;">';
		if ( $isuseradmin ) {
			$out .= 'UserID = <input type="text" name="idUser" value="' . $idUser . '" size="5" style="width:50px;">
				<input type="submit" name="cmdSubmit" value="' . __( 'DELETE', 'wp-fevents-book' ) . '" style="background-color:' . $evento['Colore'] . '; color: #666666;" />
				<input type="submit" name="cmdSubmit" value="' . __( 'ADD/UPDATE', 'wp-fevents-book' ) . '" style="background-color:' . $evento['Colore'] . '; color: #666666;" />';
		} else {
			$out .= '<input type="hidden" name="idUser" value="' . $idUser . '">';
		}
		$out .= '<input type="hidden" name="idEvent" value="' . $eventid . '">
		<table id="wpfevent_' . $eventid . '" class="wpfevent_table" >
		<tbody>
		<tr><td class="tdheader">' . __( 'Description', 'wp-fevents-book' ) . ':</td><td style="white-space: nowrap; font-weight:bold; font-size:200%; background:' . $evento['Colore'] . '">' . $evento['Descrizione'] . '</td></tr>';
		if ( $exp_date > $today && $exp_date != '' ) {
			$out .= '<tr><td class="tdheader">' . __( 'Date (mm/dd/yyyy)', 'wp-fevents-book' ) . ':</td><td>' . $evento['Data'] . ' <span class="expirewarn">[' . __( 'please subscribe before', 'wp-fevents-book' ) . ' <span class="expired">' . $exp_str . '</span>]</span></td></tr>';
		} else if ( $evento['Data'] != '' ) {
			$out .= '<tr><td class="tdheader">' . __( 'Date (mm/dd/yyyy)', 'wp-fevents-book' ) . ':</td><td>' . $evento['Data'] . '</td></tr>';
		}
		if ( $evento['Luogo'] != '' ) {
			$out .= '<tr><td class="tdheader">' . __( 'Place', 'wp-fevents-book' ) . ':</td><td>' . $evento['Luogo'] . '</td></tr>';
		}
		if ( $evento['Note'] != '' ) {
			$out .= '<tr><td class="tdheader">' . __( 'Event Info', 'wp-fevents-book' ) . ':</td><td>' . $evento['Note'] . '</td></tr>';
		}
		//$out .= 'oggi='.$today.' scadenza='.$exp_date;
		if ( $exp_date < $today && $exp_date != '' ) {
			$out .= '<tr><td></td><td style="font-weight:bold; background:' . $evento['Colore'] . '; color: #666666;">' . __( 'Event EXPIRED', 'wp-fevents-book' ) . ' ' . $exp_str . '</td></tr>';
		} else {
			$out .= '<tr>
			<td class="tdheader">' . __( 'User', 'wp-fevents-book' ) . ':</td>
			<td>' . $username . ' <input type="' . $enablesubmit . '" name="cmdSubmit" value="' . $btnval . '" style="background-color:' . $evento['Colore'] . ';">';
			if ( $idUser != 0 && $usernote != '' ) {
				$out .= '<br/>' . __( '(if the user has already booked it is possible to modify the Notes and the Confirmation)', 'wp-fevents-book' );
			}
			if ( $btnvad != '') {
				$out .= '<br/>' . ' <input type="' . $enablesubmit . '" name="cmdSubmit" value="' . $btnvad . '" style="background-color:' . $evento['Colore'] . ';">';
			}
		}
		$out .= '</td>
		</tr>';
		if ( $evento['scelte'] ) {
			$out .= '<tr>
			<td class="tdheader" valign="top">' . $evento['sceltatxt'] . ':</td>
			<td>';
			$out .= '
			<input type="radio" name="scelta" value="' . $evento['scelta1'] . '" ' . checked( $evento['scelta1'], $uschoice, false ) . '>' . $evento['scelta1'] . '
			<input type="radio" name="scelta" value="' . $evento['scelta2'] . '" ' . checked( $evento['scelta2'], $uschoice, false ) . '>' . $evento['scelta2'] . '
			<input type="radio" name="scelta" value="' . $evento['scelta3'] . '" ' . checked( $evento['scelta3'], $uschoice, false ) . '>' . $evento['scelta3'] . '
			<input type="radio" name="scelta" value="' . $evento['scelta4'] . '" ' . checked( $evento['scelta4'], $uschoice, false ) . '>' . $evento['scelta4'];
			$out .= '</td>
			</tr>';
		}
		if ( $evento['teamlist'] ) {
			$tmx = $evento['teamfirst'] + $evento['teamnumbers'];
			$out .= '<tr>
			<td class="tdheader" valign="top">' . __( 'Team number', 'wp-fevents-book' ) . ':</td>
			<td><input type="number" name="NrUserTeam" value="' . $usteam . '"  min="' . $evento['teamfirst'] . '" max="' . $tmx . '"></td>
			</tr>';
		}
		$out .= '<tr>
		<td class="tdheader" valign="top">' . __( 'User notes', 'wp-fevents-book' ) . ':</td>
		<td><textarea name="txtComments" rows="3" >' . $usernote . '</textarea></td>
		</tr>';
		if ( $evento['conferma'] ) {
			$out .= '<tr>
			<td class="tdheader" valign="top">' . __( 'Confirmed', 'wp-fevents-book' ) . ':</td>
			<td><input type="checkbox" name="boolConfrm" /></td>
			</tr>';
		}
		if (function_exists('siwp_captcha_shortcode')) {
			$out .= '<tr>'.siwp_captcha_shortcode().'</tr>';
		}
		$out .= '</tbody>
		</table>
		</form>';
		return $out;
	}

	public function out_form_list( $evento, $elencoiscritti, $cntiscritti, $isuseradmin )
	{
		$cnt = 0;
		$cntconf = 0;
		$out = '<table><tbody><tr>';
		if ( $evento['esporta'] ) {
			$out .= '<th style="white-space: nowrap; font-weight:bold; background:' . $evento['Colore'] . '"><a href="#" title="' . __( 'CSV export', 'wp-fevents-book' ) . '" onclick="document.forms[\'wpfeventsbookform_' . $evento['IDevento'] . '\'].submit(); return false;">' . __( 'Booked users list', 'wp-fevents-book' ) . '</a></th>';
		} else {
			$out .= '<th style="white-space: nowrap; font-weight:bold; background:' . $evento['Colore'] . '">' . __( 'Booked users list', 'wp-fevents-book' ) . '</th>';
		}
		if ( $evento['usermail'] ) {
			$out .= '<th>' . __( 'Email', 'wp-fevents-book' ) . '</th>';
		}
		if ( $evento['userinfo'] ) {
			$out .= '<th>' . __( 'Info', 'wp-fevents-book' ) . '</th>';
		}
		if ( $evento['teamlist'] ) {
			$out .= '<th>' . __( 'Team Nr', 'wp-fevents-book' ) . '</th>';
		}
		$out .= '<th>' . __( 'Notes', 'wp-fevents-book' ) . '</th>';
		if ( $evento['conferma'] ) {
			$out .= '<th>' . __( 'Confirmed', 'wp-fevents-book' ) . '</th>';
		}
		if ( $evento['scelte'] ) {
			$out .= '<th>' . __( 'Choice', 'wp-fevents-book' ) . '</th>';
		}
		$out .= '</tr>';
		if ( $cntiscritti > 0 ) {
			//foreach ( $elencoiscritti as $key => $value ) {
			foreach ( $elencoiscritti as $value ) {
				//echo ('Key:' . $key . '; Values 0:' . $value[0] . ' 1:' . $value[1] . ' 2:' . $value[2] . ' 3:' . $value[3] . ' 4:' . $value[4] . ' 5:' . $value[5] . ' 6:' . $value[6] . '<br />');
				//array ($iscritto['idUser'], $iscritto['note'], $iscritto['confrm'], $usteam, $iscritto['scelta'], $iscritto['ID'], $iscritto['idUserName']);
				$username = '';
				if ( $value[0] > 0 ) {
					$user = get_userdata( $value[0] );
					if ( $isuseradmin ) {
						$username = '-FnLn: ' . $user->user_firstname . ' ' . $user->user_lastname . ' -L: ' . $user->user_login . ' -N: ' . $user->user_nicename . ' -D: ' . $user->display_name;
						switch ( $evento['userid_adm'] ) {
							case 1:
								$username = $user->user_firstname . ' ' . $user->user_lastname;
								break;
							case 2:
								$username = $user->user_nicename;
								break;
							case 3:
								$username = $user->user_login;
								break;
							case 4:
								$username = $user->display_name;
						}
						$username .= ' [' . $value[0] . '] ';
					} else {
						switch ( $evento['userid'] ) {
							case 1:
								$username = $user->user_firstname . ' ' . $user->user_lastname;
								break;
							case 2:
								$username = $user->user_nicename;
								break;
							case 3:
								$username = $user->user_login;
								break;
							case 4:
								$username = $user->display_name;
						}
					}
					$email = $user->user_email;
					$info = get_the_author_meta( 'annonascita', $user->ID ) . ' ' . get_the_author_meta( 'tessera', $user->ID ) . ' tg.' . get_the_author_meta( 'taglia', $user->ID );
				} else {
					$email = '';
					$info = '';
				}
				// idUserName, set only if NotLoggedCanBook=1
				if ( isset( $value[6] ) && $value[0] < 0 ) {
					$username .= $value[6];
					if ( $isuseradmin ) {
						//display the idUser
						$username .= ' [' . $value[0] . '] ';
					}
				}

				$out .= '<tr><td>' . $username . '</td>';
				if ( $evento['usermail'] ) {
					$out .= '<td>' . $email . '</td>';
				}
				if ( $evento['userinfo'] ) {
					$out .= '<td>' . $info . '</td>';
				}
				if ( $evento['teamlist'] ) {
					//TeamNumber usteam
					$out .= '<td>' . $value[3] . '</td>';
				}
				//UserNotes
				$out .= '<td>' . $value[1] . '</td>';
				if ( $evento['conferma'] ) {
					$chkcfg = '';
					if ( $value[2] == 'on' ) {
						$chkcfg = ' checked="true"';
						$cntconf++;
					}
					$out .= '<td><input type="checkbox" disabled="disabled"' . $chkcfg . '></td>';
				}
				//UserChoice
				if ( $evento['scelte'] ) {
					$out .= '<td>' . $value[4] . '</td>';
				}
				$out .= '</tr>';
				$cnt++;
			}
		}
		switch ( $evento['user_total'] ) {
			case 1:
				$out .= '<tr><td colspan="2">' . __( 'Total booked users: ', 'wp-fevents-book' ) . $cnt . '</td></tr></tbody></table></div>';
				break;
			case 2:
				$out .= '<tr><td colspan="2">' . __( 'Total confirmed users: ', 'wp-fevents-book' ) . $cntconf . '</td></tr></tbody></table></div>';
				break;
			default: //3 or not set
				$out .= '<tr><td colspan="2">' . __( 'Total confirmed / booked users: ', 'wp-fevents-book' ) . $cntconf . ' / ' . $cnt . '</td></tr></tbody></table></div>';
		}
		return $out;
	}

	public function delete_event( $delete_eventid, $remove_event )
	{
		//estrae iscritti all'evento ID
		$iscrittieventi = get_option( $this->wpf_code . 'iscritti' );
		$cntisc = 0;
		if ( $iscrittieventi != null ) {
			foreach ( $iscrittieventi as $iscritto ) {
				//estrazione iscritti all'evento in oggetto
				if ( $iscritto['ID'] == $delete_eventid ) {
					unset( $iscrittieventi[$cntisc] );
				}
				$cntisc++;
			}
		}
		//ricompatta
		$iscrittieventicmp = array_values( $iscrittieventi );
		update_option( $this->wpf_code . 'iscritti', $iscrittieventicmp );
		echo 'DELETED Users at Event ID=' . $delete_eventid;

		if ( $remove_event == 'YES' ) {
			//estrae elenco eventi
			$events = get_option( $this->wpf_code );
			unset( $events[$delete_eventid] );
			//NON ricompattare - ID utilizzato da $this->wpf_code.'iscritti'
			//$events = array_values( $events );
			update_option( $this->wpf_code, $events );
			echo '... DELETED Event ID=' . $delete_eventid;
		}
	}

	public function generate_csv()
	{
		if ( isset( $_POST['idEvent'] ) ) {
			if ( isset( $_POST['cmdSubmit'] ) ) {
				return;
			}

			$idEvent = $_POST['idEvent'];
			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( !empty( $sitename ) ) {
				$sitename .= '_';
			}
			$filename = $sitename . 'users@event#' . $idEvent . 'T' . date( 'Y-m-d-H-i-s' ) . '.csv';

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );

			echo '"IdUser", "User", "Email", "Info", "UserNotes", "Confirm", "Choice"' . chr( 13 );
			//estrae iscritti all'evento ID
			$iscrittieventi = get_option( $this->wpf_code . 'iscritti' );

			if ( $iscrittieventi != null ) {
				foreach ( $iscrittieventi as $iscritto ) {
					//estrazione iscritti all'evento in oggetto
					if ( $iscritto['ID'] == $idEvent ) {
						if ( $iscritto['idUser'] > 0 ) {
							$user = get_userdata( $iscritto['idUser'] );
							$username = $user->user_firstname . ' ' . $user->user_lastname;
							$email = $user->user_email;
							$info = get_the_author_meta( 'annonascita', $user->ID ) . ' ' . get_the_author_meta( 'tessera', $user->ID ) . ' tg.' . get_the_author_meta( 'taglia', $user->ID );
						} else {
							$username = $iscritto['idUserName'];
						}
						if ( isset( $iscritto['confrm'] ) ) {
							$cnf = $iscritto['confrm'] != '' ? $iscritto['confrm'] : 'NO';
						}
						$cnf = $cnf == 'on' ? 'YES' : 'NO';
						$choice = '';
						if ( isset( $iscritto['scelta'] ) ) {
							$choice = $iscritto['scelta'];
						}
						echo '"';
						echo implode( '","', array( $iscritto['idUser'], $username, $email, $info, $iscritto['note'], $cnf, $choice ) ) . '"' . chr( 13 );
					}
				}
			}
			exit;
		}
	}

	// Levels are: 1 for errors, 2 for normal activity, 3 for debug.
	public function fplugin_log( $msg = '', $var = '', $level = 2 )
	{
		if ( $this->wpf_loglevel < $level ) {
			return;
		}
		//$db = debug_backtrace(false);
		$time = date( 'd-m-Y H:i:s ' );
		switch ( $level ) {
			case 1: $time .= '- ERROR';
				break;
			case 2: $time .= '- INFO ';
				break;
			case 3: $time .= '- DEBUG';
				break;
		}
		if ( is_array( $var ) || is_object( $var ) ) {
			$var = print_r( $var, true );
		}
		file_put_contents( dirname( __FILE__ ) . '/log.txt', $time . ' : ' . $msg . ' - ' . $var . "\n", FILE_APPEND | FILE_TEXT );
		}
}
