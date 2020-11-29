<?php
#####################################################################################################
# SXVLink Config Class
#####################################################################################################

class SVXLink {

	private $settingsArray;
	private $portsArray;
	private $modulesArray;
	private $modulesListArray;
	private $logics = array();
	private $links = array();
	public $macros = array();
	public $location;
	private $web_path = '/var/www/openrepeater/';
	public $logicFullPrefix = 'ORP_FullDuplexLogic_Port';
	public $logicHalfPrefix = 'ORP_HalfDuplexLogic_Port';

	public function __construct($settingsArray, $portsArray, $modulesArray) {
		$this->settingsArray = $settingsArray;
		$this->portsArray = $portsArray;
		$this->modulesArray = $modulesArray;
	}


	###############################################
	# Build Module Config Files
	###############################################

	public function build_module_list() {

		$this->write_module_configs();

		// Build Module List from Array
		if(!empty($this->modulesListArray)) {
			return array( 'MODULES' => implode(",", $this->modulesListArray) );
		} else {
			return array( '#MODULES' => 'NONE' );
		}

	}


	public function write_module_configs() {
		$this->modulesListArray = array();
		foreach($this->modulesArray as $cur_mod) {
			if ($cur_mod['moduleEnabled']==1) {
				$module_config_array = array();

				// Add Module name to array to output list in logic section
				$this->modulesListArray[] = 'Module'.$cur_mod['svxlinkName'];

				// Build Module Configuration
				$mod_build_file = $this->web_path . 'modules/' . $cur_mod['svxlinkName'] . '/build_config.php';
				if (file_exists($mod_build_file)) {
					// Module has a build file...use it.
					include($mod_build_file);

				} else {
					// Module doesn't have a build file so create minimal configuration
					$module_config_array['Module'.$cur_mod['svxlinkName']] = [
					'NAME' => $cur_mod['svxlinkName'],
					'ID' => $cur_mod['svxlinkID'],
					'TIMEOUT' => '60',
					];
				}

				// Write out Module Config File for SVXLink
				$classFunctions = new Functions();
				$classFunctions->write_config($module_config_array, 'Module'.$cur_mod['svxlinkName'].'.conf', 'ini');

			}
		}
	}



	###############################################
	# Build Global Section
	###############################################

	public function build_global() {
		$logicsList = implode(",", $this->logics); // Convert array to CSV.

		$global_array['MODULE_PATH'] = '/usr/lib/arm-linux-gnueabihf/svxlink';
		$global_array['LOGICS'] = $logicsList;
		$global_array['CFG_DIR'] = 'svxlink.d';
		$global_array['TIMESTAMP_FORMAT'] = '"%c"';
		$global_array['CARD_SAMPLE_RATE'] = '48000';
		//$global_array['LOCATION_INFO'] = 'LocationInfo';

		// Add Link Sections if defined
		if (!empty($this->links)) {
			$linksList = implode(",", $this->links); // Convert array to CSV.
			$global_array['LINKS'] = $linksList;
		}

		return $global_array;
	}



	###############################################
	# Build Local RX Port
	###############################################

	public function build_rx($curPort, $curPortType = 'GPIO') {
		$audio_dev = explode("|", $this->portsArray[$curPort]['rxAudioDev']);

		$rx_array['RX_Port'.$curPort] = [
		'TYPE' => 'Local',
		'AUDIO_DEV' => $audio_dev[0],
		'AUDIO_CHANNEL' => $audio_dev[1],
		];

		// If rxMode is not defined for current port, then set to 'cos'.
		if ( !isset($this->portsArray[$curPort]['rxMode']) ) { $this->portsArray[$curPort]['rxMode'] = 'cos'; }

		if (strtolower($this->portsArray[$curPort]['rxMode']) == 'vox') {
			// VOX Squelch Mode
			$rx_array['RX_Port'.$curPort]['SQL_DET'] = 'VOX';
			$rx_array['RX_Port'.$curPort]['VOX_FILTER_DEPTH'] = '150';
			$rx_array['RX_Port'.$curPort]['VOX_THRESH'] = '300';
			$rx_array['RX_Port'.$curPort]['SQL_HANGTIME'] = '1000';

		} else {

			// COS Squelch Mode
			switch ($curPortType) {
			case 'GPIO':
				$rx_array['RX_Port'.$curPort]['SQL_DET'] = 'GPIO';
				$rx_array['RX_Port'.$curPort]['GPIO_SQL_PIN'] = 'gpio' . $this->portsArray[$curPort]['rxGPIO'];
				$rx_array['RX_Port'.$curPort]['SQL_HANGTIME'] = '10';
				break;

			case 'HiDraw':
				$hidDev = trim( $this->portsArray[$curPort]['hidrawDev'] );
				if ($this->portsArray[$curPort]['hidrawRX_cos_invert'] == true) {
					$hid_pin = '!' . $this->portsArray[$curPort]['hidrawRX_cos']; // Inverted Logic
				} else {
					$hid_pin = $this->portsArray[$curPort]['hidrawRX_cos']; // Normal Logic
				}
				$rx_array['RX_Port'.$curPort]['SQL_DET'] = 'HIDRAW';
				$rx_array['RX_Port'.$curPort]['HID_DEVICE'] = $hidDev;
				$rx_array['RX_Port'.$curPort]['HID_SQL_PIN'] = $hid_pin;
				$rx_array['RX_Port'.$curPort]['SQL_HANGTIME'] = '10';
				break;

			case 'Serial':
				$serialDev = trim( $this->portsArray[$curPort]['serialDev'] );
				if ($this->portsArray[$curPort]['serialRX_cos_invert'] == true) {
					$serial_pin = '!' . $this->portsArray[$curPort]['serialRX_cos']; // Inverted Logic
				} else {
					$serial_pin = $this->portsArray[$curPort]['serialRX_cos']; // Normal Logic
				}
				$rx_array['RX_Port'.$curPort]['SQL_DET'] = 'SERIAL';
				$rx_array['RX_Port'.$curPort]['SERIAL_PORT'] = $serialDev;
				$rx_array['RX_Port'.$curPort]['SERIAL_PIN'] = $serial_pin;
				$rx_array['RX_Port'.$curPort]['SQL_HANGTIME'] = '10';
				break;
			}

		}

		# Fixed Settings
		$rx_array['RX_Port'.$curPort]['SQL_START_DELAY'] = '1';
		$rx_array['RX_Port'.$curPort]['SQL_DELAY'] = '10';
		$rx_array['RX_Port'.$curPort]['SIGLEV_SLOPE'] = '1';
		$rx_array['RX_Port'.$curPort]['SIGLEV_OFFSET'] = '0';
		$rx_array['RX_Port'.$curPort]['SIGLEV_OPEN_THRESH'] = '30';
		$rx_array['RX_Port'.$curPort]['SIGLEV_CLOSE_THRESH'] = '10';
		$rx_array['RX_Port'.$curPort]['DEEMPHASIS'] = '1';
		$rx_array['RX_Port'.$curPort]['PEAK_METER'] = '0';
		$rx_array['RX_Port'.$curPort]['DTMF_DEC_TYPE'] = 'INTERNAL';
		$rx_array['RX_Port'.$curPort]['DTMF_MUTING'] = '1';
		$rx_array['RX_Port'.$curPort]['DTMF_HANGTIME'] = '100';
		$rx_array['RX_Port'.$curPort]['DTMF_SERIAL'] = '/dev/ttyS0';


		### APPEND ADVANCED SVXLINK LOCAL RX SETTINGS...IF THEY EXIST ###
		if ( isset($this->portsArray[$curPort]['SVXLINK_ADVANCED_RX']) ) {
			$svxlink_advanced_rx_array = $this->portsArray[$curPort]['SVXLINK_ADVANCED_RX'];
			foreach ($svxlink_advanced_rx_array as $settingName => $settingValue) {
				$rx_array['RX_Port'.$curPort][trim($settingName)] = trim($settingValue);
			}
		}

		return $rx_array;
	}



	###############################################
	# Build Local TX Port
	###############################################

	public function build_tx($curPort, $curPortType = 'GPIO') {
		$audio_dev = explode("|", $this->portsArray[$curPort]['txAudioDev']);

		$tx_array['TX_Port'.$curPort] = [
		'TYPE' => 'Local',
		'AUDIO_DEV' => $audio_dev[0],
		'AUDIO_CHANNEL' => $audio_dev[1],
		'PTT_HANGTIME' => ($this->settingsArray['txTailValueSec'] * 1000),
		'TIMEOUT' => '300',
		'TX_DELAY' => '50',
		];

		switch ($curPortType) {
		case 'GPIO':
			$tx_array['TX_Port'.$curPort]['PTT_TYPE'] = 'GPIO';
			$tx_array['TX_Port'.$curPort]['PTT_PORT'] = 'GPIO';
			$tx_array['TX_Port'.$curPort]['PTT_PIN'] = 'gpio'.$this->portsArray[$curPort]['txGPIO'];
			break;

		case 'HiDraw':
			$hidDev = trim( $this->portsArray[$curPort]['hidrawDev'] );
			if ($this->portsArray[$curPort]['hidrawTX_ptt_invert'] == true) {
				$hid_pin = '!' . $this->portsArray[$curPort]['hidrawTX_ptt']; // Inverted Logic
			} else {
				$hid_pin = $this->portsArray[$curPort]['hidrawTX_ptt']; // Normal Logic
			}
			$tx_array['TX_Port'.$curPort]['PTT_TYPE'] = 'Hidraw';
			$tx_array['TX_Port'.$curPort]['HID_DEVICE'] = $hidDev;
			$tx_array['TX_Port'.$curPort]['HID_PTT_PIN'] = $hid_pin;
			break;

		case 'Serial':
			$serialDev = trim( $this->portsArray[$curPort]['serialDev'] );
			if ($this->portsArray[$curPort]['serialTX_ptt_invert'] == true) {
				$serial_pin = '!' . $this->portsArray[$curPort]['serialTX_ptt']; // Inverted Logic
			} else {
				$serial_pin = $this->portsArray[$curPort]['serialTX_ptt']; // Normal Logic
			}
			$tx_array['TX_Port'.$curPort]['PTT_TYPE'] = 'SerialPin';
			$tx_array['TX_Port'.$curPort]['PTT_PORT'] = $serialDev;
			$tx_array['TX_Port'.$curPort]['PTT_PIN'] = $serial_pin;
			break;

		}

		if ($this->settingsArray['txTone']) {
			$tx_array['TX_Port'.$curPort]['CTCSS_FQ'] = $this->settingsArray['txTone'];
			$tx_array['TX_Port'.$curPort]['CTCSS_LEVEL'] = '9';
		}

		# Fixed Settings
		$tx_array['TX_Port'.$curPort]['PREEMPHASIS'] = '0';
		$tx_array['TX_Port'.$curPort]['DTMF_TONE_LENGTH'] = '100';
		$tx_array['TX_Port'.$curPort]['DTMF_TONE_SPACING'] = '50';
		$tx_array['TX_Port'.$curPort]['DTMF_TONE_PWR'] = '-18';


		### APPEND ADVANCED SVXLINK LOCAL TX SETTINGS...IF THEY EXIST ###
		if ( isset($this->portsArray[$curPort]['SVXLINK_ADVANCED_TX']) ) {
			$svxlink_advanced_tx_array = $this->portsArray[$curPort]['SVXLINK_ADVANCED_TX'];
			foreach ($svxlink_advanced_tx_array as $settingName => $settingValue) {
				$tx_array['TX_Port'.$curPort][trim($settingName)] = trim($settingValue);
			}
		}

		return $tx_array;
	}



	###############################################
	# Build Full Duplex Logic (Repeater)
	###############################################

	public function build_full_duplex_logic($curPort) {
		$logicName = 	$this->logicFullPrefix . $curPort;
		$this->logics[] = $logicName; // Add this logic to list for Globals Section

		$logic_array[$logicName] = [
		'TYPE' => 'Repeater',
		'RX' => 'RX_Port' . $curPort,
		'TX' => 'TX_Port' . $curPort,
		];

		$logic_array[$logicName] += $this->build_module_list();

		$logic_array[$logicName]['CALLSIGN'] = $this->settingsArray['callSign'];

		# Short ID
		if ($this->settingsArray['ID_Short_Mode'] == 'disabled') {
			$logic_array[$logicName]['#SHORT_IDENT_INTERVAL'] = '0';
		} else {
			$logic_array[$logicName]['SHORT_IDENT_INTERVAL'] = $this->settingsArray['ID_Short_IntervalMin'];
		}

		# ID only if there is activity, only affect short IDs
		if ($this->settingsArray['ID_Only_When_Active'] == 'True') {
			$logic_array[$logicName]['IDENT_ONLY_AFTER_TX'] = '4';
		}

		# Long ID
		if ($this->settingsArray['ID_Long_Mode'] == 'disabled') {
			$logic_array[$logicName]['#LONG_IDENT_INTERVAL'] = '0';
		} else {
			$logic_array[$logicName]['LONG_IDENT_INTERVAL'] = $this->settingsArray['ID_Long_IntervalMin'];
		}

		# Fixed Settings
		$logic_array[$logicName]['EVENT_HANDLER'] = '/usr/share/svxlink/events.tcl';
		$logic_array[$logicName]['DEFAULT_LANG'] = 'en_US';
		$logic_array[$logicName]['RGR_SOUND_DELAY'] = '1';
		$logic_array[$logicName]['REPORT_CTCSS'] = $this->settingsArray['rxTone'];
		$logic_array[$logicName]['TX_CTCSS'] = 'ALWAYS';
		$logic_array[$logicName]['FX_GAIN_NORMAL'] = '0';
		$logic_array[$logicName]['FX_GAIN_LOW'] = '-12';
		$logic_array[$logicName]['IDLE_TIMEOUT'] = '1';
		$logic_array[$logicName]['OPEN_ON_SQL'] = '1';
		$logic_array[$logicName]['OPEN_SQL_FLANK'] = 'OPEN';
		$logic_array[$logicName]['IDLE_SOUND_INTERVAL'] = '0';
		$logic_array[$logicName]['DTMF_CTRL_PTY'] = '/tmp/ORP_PORT'.$curPort;
		$logic_array[$logicName]['ANNOUNCE_CMD'] = '96'.$curPort;

		# Macro Section
		$logic_array[$logicName]['MACROS'] = '';

		if ($this->settingsArray['repeaterDTMF_disable'] == 'True') {
			$logic_array[$logicName]['ONLINE_CMD'] = $this->settingsArray['repeaterDTMF_disable_pin'];
		}


		### APPEND ADVANCED SVXLINK LOGIC SETTINGS...IF THEY EXIST ###
		if ( isset($this->portsArray[$curPort]['SVXLINK_ADVANCED_LOGIC']) ) {
			$svxlink_advanced_logic_array = $this->portsArray[$curPort]['SVXLINK_ADVANCED_LOGIC'];
			foreach ($svxlink_advanced_logic_array as $settingName => $settingValue) {
				$logic_array[$logicName][trim($settingName)] = trim($settingValue);
			}
		}

		return $logic_array;
	}



	###############################################
	# Build Half Duplex Logic (Simplex)
	###############################################

	public function build_half_duplex_logic($curPort,$modules=false) {
		$logicName = 	$this->logicHalfPrefix . $curPort;
		$this->logics[] = $logicName; // Add this logic to list for Globals Section

		$logic_array[$logicName] = [
		'TYPE' => 'Simplex',
		'RX' => 'RX_Port' . $curPort,
		'TX' => 'TX_Port' . $curPort,
		];

		if ($modules) {
			$logic_array[$logicName] += $this->build_module_list();
		}

		$logic_array[$logicName]['CALLSIGN'] = $this->settingsArray['callSign'];

		# Short ID
		if ($this->settingsArray['ID_Short_Mode'] == 'disabled') {
			$logic_array[$logicName]['#SHORT_IDENT_INTERVAL'] = '0';
		} else {
			$logic_array[$logicName]['SHORT_IDENT_INTERVAL'] = $this->settingsArray['ID_Short_IntervalMin'];
		}

		# ID only if there is activity, only affect short IDs
		if ($this->settingsArray['ID_Only_When_Active'] == 'True') {
			$logic_array[$logicName]['IDENT_ONLY_AFTER_TX'] = '4';
		}

		#Long ID
		if ($this->settingsArray['ID_Long_Mode'] == 'disabled') {
			$logic_array[$logicName]['#LONG_IDENT_INTERVAL'] = '0';
		} else {
			$logic_array[$logicName]['LONG_IDENT_INTERVAL'] = $this->settingsArray['ID_Long_IntervalMin'];
		}

		# Fixed Settings
		$logic_array[$logicName]['EVENT_HANDLER'] = '/usr/share/svxlink/events.tcl';
		$logic_array[$logicName]['DEFAULT_LANG'] = 'en_US';
		$logic_array[$logicName]['RGR_SOUND_DELAY'] = '1';
		$logic_array[$logicName]['REPORT_CTCSS'] = $this->settingsArray['rxTone'];
		$logic_array[$logicName]['TX_CTCSS'] = 'ALWAYS';
		$logic_array[$logicName]['FX_GAIN_NORMAL'] = '0';
		$logic_array[$logicName]['FX_GAIN_LOW'] = '-12';
		$logic_array[$logicName]['IDLE_TIMEOUT'] = '1';
		$logic_array[$logicName]['OPEN_ON_SQL'] = '1';
		$logic_array[$logicName]['OPEN_SQL_FLANK'] = 'OPEN';
		$logic_array[$logicName]['IDLE_SOUND_INTERVAL'] = '0';

		# Macro Section
		$logic_array[$logicName]['MACROS'] = '';

		/*
		if ($this->settingsArray['repeaterDTMF_disable'] == 'True') {
			$logic_array[$logicName] += [
				'ONLINE_CMD' => $this->settingsArray['repeaterDTMF_disable_pin'],
			];
		}
		*/

		### APPEND ADVANCED SVXLINK LOGIC SETTINGS...IF THEY EXIST ###
		if ( isset($this->portsArray[$curPort]['SVXLINK_ADVANCED_LOGIC']) ) {
			$svxlink_advanced_logic_array = $this->portsArray[$curPort]['SVXLINK_ADVANCED_LOGIC'];
			foreach ($svxlink_advanced_logic_array as $settingName => $settingValue) {
				$logic_array[$logicName][trim($settingName)] = trim($settingValue);
			}
		}

		return $logic_array;
	}



	###############################################
	# Build Network RX Port
	###############################################

	public function build_netRX($curPort) {

		$netRX_array['NetRX_Port'.$curPort] = [
			'TYPE' => 'Net',
			'HOST' => $this->portsArray[$curPort]['netHost'],
			'TCP_PORT' => $this->portsArray[$curPort]['netPort'],
			'AUTH_KEY' => $this->portsArray[$curPort]['netKey'],
			'CODEC' => $this->portsArray[$curPort]['netCodec'], // RAW, S16, GSM, SPEEX, OPUS
		];

		# Other Settings
		$netRX_array['NetRX_Port'.$curPort]['LOG_DISCONNECTS_ONCE'] = $this->portsArray[$curPort]['netLogDisconnectsOnce'];

		if ( $this->portsArray[$curPort]['netCodec'] == 'SPEEX' ) {
			$netRX_array['NetRX_Port'.$curPort]['SPEEX_ENC_FRAMES_PER_PACKET'] = $this->portsArray[$curPort]['netSpeexEncFramesPerPacket'];
			$netRX_array['NetRX_Port'.$curPort]['SPEEX_ENC_QUALITY'] = $this->portsArray[$curPort]['netSpeexEncQuality'];
			$netRX_array['NetRX_Port'.$curPort]['SPEEX_ENC_BITRATE'] = $this->portsArray[$curPort]['netSpeexEncBitrate'];
			$netRX_array['NetRX_Port'.$curPort]['SPEEX_ENC_COMPLEXITY'] = $this->portsArray[$curPort]['netSpeexEncComplexity'];
			$netRX_array['NetRX_Port'.$curPort]['SPEEX_ENC_VBR'] = $this->portsArray[$curPort]['netSpeexEncVbr'];
			$netRX_array['NetRX_Port'.$curPort]['SPEEX_ENC_VBR_QUALITY'] = $this->portsArray[$curPort]['netSpeexEncVbrQuality'];
			$netRX_array['NetRX_Port'.$curPort]['SPEEX_ENC_ABR'] = $this->portsArray[$curPort]['netSpeexEncAbr'];
			$netRX_array['NetRX_Port'.$curPort]['SPEEX_DEC_ENHANCER'] = $this->portsArray[$curPort]['netSpeexDecEnhancer'];
		}

		if ( $this->portsArray[$curPort]['netCodec'] == 'OPUS' ) {
			$netRX_array['NetRX_Port'.$curPort]['OPUS_ENC_FRAME_SIZE'] = $this->portsArray[$curPort]['netOpusEncFrameSize'];
			$netRX_array['NetRX_Port'.$curPort]['OPUS_ENC_COMPLEXITY'] = $this->portsArray[$curPort]['netOpusEncComplexity'];
			$netRX_array['NetRX_Port'.$curPort]['OPUS_ENC_BITRATE'] = $this->portsArray[$curPort]['netOpusEncBitrate'];
			$netRX_array['NetRX_Port'.$curPort]['OPUS_ENC_VBR'] = $this->portsArray[$curPort]['netOpusEncVbr'];
		}

		return $netRX_array;
	}



	###############################################
	# Build Network TX Port
	###############################################

	public function build_netTX($curPort) {

		$netTX_array['NetTX_Port'.$curPort] = [
		'TYPE' => 'Net',
		'HOST' => $this->portsArray[$curPort]['netHost'],
		'TCP_PORT' => $this->portsArray[$curPort]['netPort'],
		'AUTH_KEY' => $this->portsArray[$curPort]['netKey'],
		'CODEC' => $this->portsArray[$curPort]['netCodec'],
		];

		# Other Settings
		$netTX_array['NetTX_Port'.$curPort]['LOG_DISCONNECTS_ONCE'] = $this->portsArray[$curPort]['netLogDisconnectsOnce'];

		if ( $this->portsArray[$curPort]['netCodec'] == 'SPEEX' ) {
			$netTX_array['NetTX_Port'.$curPort]['SPEEX_ENC_FRAMES_PER_PACKET'] = $this->portsArray[$curPort]['netSpeexEncFramesPerPacket'];
			$netTX_array['NetTX_Port'.$curPort]['SPEEX_ENC_QUALITY'] = $this->portsArray[$curPort]['netSpeexEncQuality'];
			$netTX_array['NetTX_Port'.$curPort]['SPEEX_ENC_BITRATE'] = $this->portsArray[$curPort]['netSpeexEncBitrate'];
			$netTX_array['NetTX_Port'.$curPort]['SPEEX_ENC_COMPLEXITY'] = $this->portsArray[$curPort]['netSpeexEncComplexity'];
			$netTX_array['NetTX_Port'.$curPort]['SPEEX_ENC_VBR'] = $this->portsArray[$curPort]['netSpeexEncVbr'];
			$netTX_array['NetTX_Port'.$curPort]['SPEEX_ENC_VBR_QUALITY'] = $this->portsArray[$curPort]['netSpeexEncVbrQuality'];
			$netTX_array['NetTX_Port'.$curPort]['SPEEX_ENC_ABR'] = $this->portsArray[$curPort]['netSpeexEncAbr'];
			$netTX_array['NetTX_Port'.$curPort]['SPEEX_DEC_ENHANCER'] = $this->portsArray[$curPort]['netSpeexDecEnhancer'];
		}

		if ( $this->portsArray[$curPort]['netCodec'] == 'OPUS' ) {
			$netTX_array['NetTX_Port'.$curPort]['OPUS_ENC_FRAME_SIZE'] = $this->portsArray[$curPort]['netOpusEncFrameSize'];
			$netTX_array['NetTX_Port'.$curPort]['OPUS_ENC_COMPLEXITY'] = $this->portsArray[$curPort]['netOpusEncComplexity'];
			$netTX_array['NetTX_Port'.$curPort]['OPUS_ENC_BITRATE'] = $this->portsArray[$curPort]['netOpusEncBitrate'];
			$netTX_array['NetTX_Port'.$curPort]['OPUS_ENC_VBR'] = $this->portsArray[$curPort]['netOpusEncVbr'];
		}

		return $netTX_array;
	}



	###############################################
	# Build LINK Section
	###############################################

	public function build_link($linkGroupNum, $logicsArray) {
		$linkGroupSettingsArray = @unserialize( $this->settingsArray['LinkGroup_Settings'] );
		// only build this section if a serialized settings array is retrived.
		if ($this->settingsArray['LinkGroup_Settings'] === 'b:0;' || $linkGroupSettingsArray !== false) {
			$linkName = 'LinkGroup' . $linkGroupNum;
			$this->links[] = $linkName; // Add this link section to link list for declaration in Globals Section
	
			foreach($logicsArray as $currLogicKey => $currLogicName) {
				$currLinkString = $currLogicName;
				$currLinkString .= ':8' . $linkGroupNum;
				$currLinkString .= ':' . $this->settingsArray['callSign'];
				$outputLogicArray[$currLogicKey] =  $currLinkString;
			}
	
			$link_array[$linkName]['CONNECT_LOGICS'] = implode(",", $outputLogicArray);
	
			if ($linkGroupSettingsArray[$linkGroupNum]['defaultActive'] == '1') {
				$link_array[$linkName]['DEFAULT_ACTIVE'] = $linkGroupSettingsArray[$linkGroupNum]['defaultActive'];
			}
	
			if ($linkGroupSettingsArray[$linkGroupNum]['timeout'] > 0) {
				$link_array[$linkName]['TIMEOUT'] = $linkGroupSettingsArray[$linkGroupNum]['timeout']; // In seconds
			}
	
			return $link_array;
		} else {
			return false;
		}

	}



	###############################################
	# Build MACRO Section
	###############################################

	public function build_macro($macrosArray) {
		$existPorts = $this->portsArray;
		$modulesArray = $this->modulesArray;
		$macro_array = [];
		$macroNamePrefix = 'Macros_Port';

		foreach ($macrosArray as $currMacroArray) {
			$currMacroKey = $currMacroArray['macroKey'];
			$currMacroEnabled = $currMacroArray['macroEnabled'];
			$currMacroNum = $currMacroArray['macroNum'];
			$currMacroModuleKey = $currMacroArray['macroModuleKey'];
			$currMacroString = $currMacroArray['macroString'];
			$currMacroPorts = $currMacroArray['macroPorts'];
			

			if ( $currMacroEnabled == 1 && $modulesArray[$currMacroModuleKey]['moduleEnabled'] == 1 ) {
	
				$currMacroModuleName = $modulesArray[$currMacroModuleKey]['svxlinkName'];

				if ($currMacroPorts != 'ALL') {
					if ($existPorts[$currMacroPorts]['portEnabled'] == 1) {
						$currMacroName = $macroNamePrefix . $currMacroPorts;
						if ($existPorts[$currMacroPorts]['portDuplex'] == 'full') {
							$currLogicSection = $this->logicFullPrefix . $currMacroPorts;
						} else if ($existPorts[$currMacroPorts]['portDuplex'] == 'half') {
							$currLogicSection = $this->logicHalfPrefix . $currMacroPorts;
						}
						$this->macros[$currLogicSection] = $currMacroName;
						$macro_array[$currMacroName][$currMacroNum] = $currMacroModuleName . ':' . trim($currMacroString);
					}

				} else if ($currMacroPorts == 'ALL') {
					foreach ($existPorts as $curPort => $curPortArray) {
						if ($curPortArray['portEnabled'] == 1) {
							$currMacroName = $macroNamePrefix . $curPort;
							if ($curPortArray['portDuplex'] == 'full') {
								$currLogicSection = $this->logicFullPrefix . $curPort;
							} else if ($curPortArray['portDuplex'] == 'half') {
								$currLogicSection = $this->logicHalfPrefix . $curPort;
							}
							$this->macros[$currLogicSection] = $currMacroName;
							$macro_array[$currMacroName][$currMacroNum] = $currMacroModuleName . ':' . trim($currMacroString);
						}
					}
				}
			}
		}

		return $macro_array;
	}



	###############################################
	# Build LOCATION Section
	###############################################

	public function build_location() {
		$locationSettings = @unserialize( $this->settingsArray['Location_Info'] );
		// only build this section if a serialized settings array is retrived.
		if ($this->settingsArray['Location_Info'] === 'b:0;' || $locationSettings !== false) {
			if ( !empty($locationSettings['Echolink_Status_Servers']) || !empty($locationSettings['APRS_ServerList']) ) {
	
				$locSection = 'LocationInfo';
				$this->location = $locSection; // used to set in global seciton
	
				$classFunctions = new Functions();
				$convertGeo = $classFunctions->geo_convert($locationSettings['Latitude'], $locationSettings['Longitude'], 'svxlink');
	
				if ($locationSettings['Echolink_Status_Servers'] != '') {
					$location_array[$locSection]['STATUS_SERVER_LIST'] = $locationSettings['Echolink_Status_Servers'];
				}
				if ($locationSettings['APRS_ServerList'] != '') {
					$location_array[$locSection]['APRS_SERVER_LIST'] = $locationSettings['APRS_ServerList'];
				}
				$location_array[$locSection]['LAT_POSITION'] = $convertGeo['latitude'];
				$location_array[$locSection]['LON_POSITION'] = $convertGeo['longitude'];
				switch ($locationSettings['APRS_Station_Type']) {
				case "repeater":
					$location_array[$locSection]['CALLSIGN'] = 'ER-' . $this->settingsArray['callSign']; // Repeater
					break;
				case "link":
					$location_array[$locSection]['CALLSIGN'] = 'EL-' . $this->settingsArray['callSign']; // Link
					break;
				}
				$location_array[$locSection]['FREQUENCY'] = $locationSettings['Frequency'];
				$location_array[$locSection]['TONE'] = $locationSettings['Tone'];
				$location_array[$locSection]['TX_POWER'] = $locationSettings['TX_Power'];
				$location_array[$locSection]['ANTENNA_GAIN'] = $locationSettings['Antenna_Gain'];
				$location_array[$locSection]['ANTENNA_HEIGHT'] = $locationSettings['Antenna_Height'] . $locationSettings['Antenna_Height_Units'];
				$location_array[$locSection]['ANTENNA_DIR'] = $locationSettings['Antenna_Dir'];
				$location_array[$locSection]['PATH'] = $locationSettings['APRS_Path'];
				$location_array[$locSection]['BEACON_INTERVAL'] = $locationSettings['Beacon_Interval'];
				$location_array[$locSection]['STATISTICS_INTERVAL'] = $locationSettings['Statistics_Interval'];
				$location_array[$locSection]['COMMENT'] = '[ORP] Powered by openrepeater.com';
			
				return $location_array;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}



	###############################################
	# Delete ALL Custom Events
	###############################################

	public function delete_custom_evnets() {
		$files = glob('/usr/share/svxlink/events.d/' . 'ORP_*');
		array_map('unlink', $files);
	}


}
?>
