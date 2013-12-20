<?php

/**
 * テーマ関数集
 **/
class pxtheme_funcs{

	private $px;

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
		$this->px->path_theme_files('css/common.css');
		$this->px->path_theme_files('css/layout.css');
		$this->px->path_theme_files('css/modules_custom.css');
	}//__construct()


	/**
	 * カラースキームを返す
	 */
	public function get_color_scheme(){
		$colors = array();
		$colors['main'] = $this->px->get_conf('colors.main');
		$hsb = t::color_hex2hsb( $colors['main'] );

		$colors['thin'] = t::color_hsb2hex($hsb['h'], $hsb['s']-($hsb['s']/4*3), $hsb['b']+((100-$hsb['b'])/4*3));

		$colors['link'] = $colors['main'];
		$colors['text'] = '#333';
		$colors['white'] = '#fff';
		$colors['black'] = '#333';

		if( $hsb['s'] < 50 && $hsb['b'] > 50 ){
			// $colors['link'] = '#00f';
			$colors['thin'] = $colors['main'];
			$colors['link'] = '#000';
			$colors['white'] = '#333';
			$colors['black'] = '#fff';
		}
		return $colors;
	}

	/**
	 * PxFWのSVGロゴソースを返す
	 */
	public function create_src_pxfw_logo_svg($opt = array()){
		$colors = $this->get_color_scheme();
		if( strlen($opt['color']) ){
			$colors['mainx'] = $opt['color'];
		}
		ob_start();
		?>
<svg version="1.1" id="Pickles Framework LOGO" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px"
	 y="0px" width="30px" height="40px" viewBox="0 0 40 50" enable-background="new 0 0 40 50" xml:space="preserve">
<g>
	<g>
		<path fill="<?php print t::h($colors['mainx']); ?>" d="M38.514,17.599c-0.049-0.831-0.191-1.571-0.174-2.405c0.02-0.777-0.002-1.581-0.338-2.298
			c-0.484-1.033-0.473-2.235-1.027-3.287c-0.484-0.914-1.334-1.693-1.936-2.541c-0.295-0.415-0.656-0.776-0.934-1.201
			c-0.246-0.373-0.557-0.422-0.902-0.69c-0.465-0.357-0.826-0.874-1.26-1.273c-0.408-0.373-0.887-0.258-1.326-0.533
			c-0.26-0.163-0.346-0.414-0.643-0.567c-0.412-0.21-0.777-0.318-1.23-0.358c-0.408-0.035-0.838-0.001-1.18-0.271
			c-0.914-0.718-1.96-0.61-3.042-0.7C23.729,1.407,22,0.836,21.185,0.799c-0.786-0.035-1.667-0.157-2.448-0.045
			c-0.264,0.039-0.496,0.154-0.766,0.184c-0.377,0.044-0.711-0.049-1.083-0.061c-0.403-0.014-0.876,0.133-1.267,0.229
			c-0.956,0.231-1.884,0.577-2.776,0.988c-0.461,0.213-0.973,0.291-1.451,0.453C10.89,2.718,10.471,2.987,9.993,3.2
			c-0.51,0.224-0.869,0.512-1.307,0.824c-0.48,0.342-1.065,0.559-1.509,0.953C6.622,5.475,6.1,6.013,5.598,6.564
			C5.369,6.818,5.01,7.11,4.853,7.416C4.701,7.711,4.773,8.061,4.531,8.35c-0.143,0.168-0.382,0.229-0.51,0.422
			c-0.167,0.25-0.188,0.565-0.353,0.821c-0.178,0.271-0.28,0.583-0.438,0.872c-0.433,0.79-0.564,1.293-0.741,2.134
			c-0.155,0.74-0.709,1.344-0.817,2.084c-0.101,0.683,0.11,1.388-0.067,2.103c-0.242,0.986-0.489,1.966-0.586,2.979
			c-0.087,0.902,0.154,1.771,0.051,2.67c-0.118,1.014,0.223,2.089,0.328,3.104c0.293,2.828,1.2,5.59,1.459,8.362
			c0.021,0.246-0.042,0.48-0.002,0.716c0.1,0.548,0.489,0.836,0.354,1.445c-0.128,0.557,0.148,0.861,0.275,1.408
			c0.049,0.217,0.012,0.26-0.027,0.502c-0.096,0.642,0.247,1.352,0.39,1.971c0.174,0.758,0.254,1.544,0.305,2.323
			c0.07,1.016,0.655,1.801,0.942,2.736c0.272,0.879,0.281,1.827,0.851,2.588c0.518,0.688,1.422,1.182,2.276,1.282
			c0.784,0.094,1.937,0.21,2.708,0.01c0.749-0.196,1.319-0.579,1.881-1.099c0.411-0.384,0.567-0.89,0.798-1.389
			c0.57-1.24,0.088-2.425,0.387-3.699c0.104-0.453,0.322-0.924,0.381-1.376c0.04-0.332-0.104-0.626-0.096-0.954
			c0.009-0.384,0.223-0.65,0.299-1.001c0.065-0.283-0.029-0.58-0.035-0.876c-0.012-0.486-0.161-1.018-0.082-1.495
			c0.105-0.664,0.578-1.341,1.112-1.713c0.358-0.25,0.545-0.225,0.995-0.225c0.924,0.003,1.783-0.15,2.7-0.205
			c0.842-0.047,1.596,0.182,2.461,0.087c1.75-0.199,4.988-0.023,6.546-0.924c0.449-0.261,0.643-0.836,1-1.005
			c0.342-0.16,1.004,0,1.387-0.09c1.086-0.257,1.557-1.38,2.334-2.111c1.012-0.954,1.996-2.079,2.941-3.053
			c0.051-0.052,0.078-0.086,0.113-0.145c0.055-0.099,0.098-0.2,0.154-0.295c0.047-0.073,0.125-0.15,0.154-0.234
			c0.342-0.961,1.244-1.649,1.293-2.756c0.025-0.543,0.107-1.077,0.213-1.603C38.203,21.037,38.611,19.331,38.514,17.599z
			 M35.098,22.087c-0.258,0.668,0.141,1.435-0.146,2.104c-0.096,0.215-0.414,0.369-0.527,0.596
			c-0.109,0.217-0.061,0.545-0.145,0.756c-0.443,1.093-1.465,2.137-2.35,2.888c-0.879,0.743-1.701,1.713-2.799,2.144
			c-0.408,0.16-0.811,0.164-1.207,0.358c-0.527,0.262-1.025,0.301-1.584,0.47c-1.107,0.327-2.757,0.184-3.89,0.359
			c-0.246,0.039-0.475,0.031-0.695,0.065c-0.21,0.032-0.479,0.043-0.668,0.036c-0.071-0.004-0.131-0.039-0.213-0.036
			c-0.736,0-1.473,0.021-2.21,0.021c-0.871,0-1.714-0.075-2.587,0.008c-0.459,0.048-0.734,0.282-1.149,0.492
			c-0.353,0.184-0.706,0.109-1.056,0.337c-0.321,0.203-0.63,0.413-0.916,0.668c-0.531,0.47-0.945,0.958-1.086,1.669
			c-0.108,0.548-0.1,1.1-0.247,1.635c-0.141,0.508-0.215,1.025-0.176,1.567c0.033,0.457,0.174,0.966,0.266,1.428
			c0.077,0.368,0.084,0.767,0.012,1.15c-0.096,0.504-0.272,0.868-0.195,1.392c0.141,0.938,0.081,3.8-1.263,3.929
			c-0.324,0.036-0.688-0.051-1.026-0.03c-0.335,0.02-0.642,0.004-0.966-0.074c-0.558-0.134-0.485-0.317-0.599-0.828
			c-0.048-0.228-0.224-0.416-0.269-0.595c-0.045-0.186-0.016-0.39-0.062-0.579c-0.021-0.094-0.04-0.181-0.059-0.25
			c-0.121-0.556-0.047-0.625,0.671-0.725c0.554-0.069,1.437-0.117,1.689-0.707c0.114-0.269,0.097-0.727-0.123-0.938
			c-0.202-0.194-0.74-0.231-1.028-0.247C8,41.123,6.985,41.373,6.727,40.803c-0.225-0.505-0.284-1.136,0.193-1.486
			c0.353-0.263,0.792-0.22,1.207-0.243c0.379-0.022,0.794-0.078,1.056-0.392c0.233-0.281,0.094-0.968-0.088-1.274
			c-0.176-0.297-0.475-0.409-0.803-0.452c-0.66-0.087-1.508,0.167-2.062-0.313c-0.397-0.341-0.462-1.001-0.093-1.385
			c0.276-0.285,0.714-0.354,1.086-0.415c0.684-0.105,1.775-0.118,1.819-1.036c0.053-1.036-0.631-1.475-1.546-1.475
			c-0.59,0-1.255,0.031-1.795-0.257c-0.383-0.205-0.467-0.458-0.503-0.859c-0.044-0.544-0.16-1.076-0.311-1.597
			c-0.151-0.53-0.205-1.039-0.286-1.588c-0.17-1.129-0.265-2.27-0.361-3.407c-0.044-0.492-0.049-0.878-0.23-1.34
			c-0.394-1.001-0.091-1.954-0.106-2.999c-0.012-1.009,0.237-1.757,0.389-2.726c0.104-0.659-0.143-1.457,0.07-2.085
			c0.093-0.274,0.315-0.438,0.463-0.674c0.178-0.28,0.172-0.502,0.278-0.793c0.224-0.62,0.645-1.147,0.778-1.808
			c0.083-0.411-0.062-0.841,0.1-1.236c0.147-0.356,0.467-0.721,0.688-1.04c0.521-0.75,1.121-1.446,1.796-2.06
			c0.526-0.48,1.123-1.011,1.764-1.333c0.637-0.319,1.223-0.835,1.892-1.104c0.434-0.174,0.744-0.078,1.129-0.108
			c0.34-0.026,0.552-0.292,0.804-0.485c0.401-0.306,0.651-0.252,1.125-0.34c0.746-0.137,0.995-0.938,1.853-0.833
			c0.755,0.094,1.357,0.2,2.1,0.062c0.822-0.155,1.717-0.081,2.547-0.073c1.545,0.012,4.1,0.655,5.48,1.314
			c0.525,0.252,1.092,0.509,1.6,0.78c0.756,0.403,1.598,0.935,2.309,1.417c1.014,0.692,1.643,1.578,2.309,2.584
			c0.363,0.55,0.838,1.023,1.166,1.596c0.309,0.538,0.564,1.154,0.844,1.708c0.205,0.406,0.254,0.79,0.254,1.245
			c-0.002,1.163,0.121,2.386,0.291,3.53C36.135,19.17,35.566,20.866,35.098,22.087z M22.27,13.678
			c-0.289,0.532-0.24,1.13,0.135,1.619c0.556,0.724,1.805,1.087,2.525,0.359c0.61-0.614,0.524-1.585,0.034-2.248
			C24.333,12.552,23.008,12.32,22.27,13.678z M15.898,12.908c-0.136-0.036-0.29-0.053-0.461-0.049
			c-0.559,0.005-1.304,0.231-1.507,0.794c-0.258,0.706-0.104,1.556,0.476,2.064c0.629,0.549,2.205,0.448,2.47-0.454
			C17.108,14.475,16.807,13.143,15.898,12.908z"/>
	</g>
</g>
<g>
	<path fill="<?php print t::h($colors['mainx']); ?>" d="M30.088,48.687c-0.18,0.021-0.371,0.004-0.57-0.04c-0.748-0.175-1.4-0.707-2.01-1.157
		c-0.115-0.082-0.18-0.244-0.275-0.346c-0.279-0.311-0.795-0.827-1.217-0.929c-0.028-0.012-0.059-0.017-0.088-0.017
		c-0.207,0.009-0.357,0.195-0.479,0.341c-0.14,0.168-0.307,0.324-0.469,0.473c-0.133,0.124-0.301,0.227-0.412,0.375
		c-0.076,0.098-0.132,0.158-0.229,0.244c-0.611,0.546-0.982,1.238-1.835,1.188c-0.748-0.039-1.44-0.212-1.912-0.798
		c-0.603-0.742-1.419-1.275-1.249-2.369c0.117-0.763,0.773-1.385,1.231-1.925c0.265-0.305,0.659-0.539,0.96-0.817l0.026-0.022
		c0.236-0.212,0.236-0.212,0.058-0.439c-0.146-0.187-0.335-0.347-0.516-0.507c-0.101-0.083-0.195-0.17-0.289-0.259
		c-0.733-0.7-1.257-0.942-1.527-1.955c-0.256-0.965,0.139-1.764,0.928-2.351c0.168-0.123,0.416-0.23,0.535-0.406
		c0.017-0.032,0.028-0.082,0.058-0.105c0.577-0.466,1.276-0.692,2.023-0.421c0.78,0.276,1.329,1.133,1.975,1.652
		c0.194,0.156,0.391,0.32,0.577,0.485c0.139,0.126,0.288,0.345,0.497,0.359c0.231,0.017,0.45-0.346,0.596-0.492
		c0.234-0.227,0.455-0.453,0.697-0.68c0.053-0.051,0.09-0.118,0.143-0.164c0.055-0.051,0.119-0.118,0.176-0.149
		c0.191-0.109,0.326-0.29,0.465-0.453c0.436-0.494,1.279-1.001,1.957-0.677c0.279,0.132,0.58,0.312,0.797,0.531
		c0.166,0.168,0.248,0.387,0.428,0.544c0.207,0.181,0.375,0.384,0.582,0.57c0.203,0.187,0.377,0.375,0.453,0.649
		c0.102,0.391,0.217,1.079,0,1.448c-0.188,0.318-0.725,0.57-1.008,0.82c-0.201,0.186-0.252,0.345-0.408,0.547
		c-0.008,0.006-0.166,0.219-0.186,0.236c-0.336,0.327-0.945,0.796-0.443,1.254c0.242,0.219,0.48,0.422,0.699,0.673
		c0.121,0.137,0.297,0.203,0.43,0.332c0.551,0.539,1.199,1.155,1.295,1.964c0.08,0.656-0.369,0.813-0.738,1.231
		C31.301,47.71,30.984,48.576,30.088,48.687z M22.495,38.755c-0.048,0.004-0.097,0.012-0.143,0.023
		c-0.52,0.125-0.934,0.821-0.586,1.294c0.18,0.246,0.553,0.383,0.773,0.602c0.268,0.271,0.471,0.606,0.676,0.919
		c0.408,0.638,0.953,0.814,0.518,1.571c-0.471,0.814-1.285,1.338-1.857,2.066c-0.357,0.453-0.429,1.031,0.139,1.313
		c0.76,0.375,1.166-0.537,1.625-0.977c0.293-0.282,0.582-0.572,0.898-0.831c0.254-0.206,0.385-0.425,0.596-0.66
		c0.548-0.606,1.187-0.278,1.632,0.235c0.24,0.282,0.574,0.544,0.85,0.805c0.258,0.246,0.562,0.403,0.842,0.618
		c0.408,0.313,0.822,0.99,1.379,0.676c0.297-0.163,0.559-0.766,0.234-1.012c-0.072-0.056-0.41-0.301-0.471-0.363
		c-0.014-0.017-0.016-0.035-0.031-0.047c-0.012-0.018-0.037-0.024-0.051-0.04c-0.018-0.016-0.021-0.038-0.037-0.058
		s-0.043-0.02-0.057-0.035c-0.336-0.329-0.732-0.689-1.127-0.979c-0.4-0.297-1-0.625-1.039-1.188
		c-0.01-0.149,0.045-0.294,0.129-0.416c0.223-0.327,0.67-0.562,0.971-0.823c0.426-0.369,0.857-0.709,1.146-1.188
		c0.152-0.255,0.324-0.337,0.377-0.639c0.025-0.156,0.109-0.313,0-0.445c-0.07-0.084-0.191-0.102-0.273-0.173
		c-0.117-0.093-0.129-0.233-0.258-0.32c-0.158-0.108-0.25-0.069-0.396,0.028c-0.191,0.132-0.404,0.261-0.57,0.421
		c-0.195,0.191-0.354,0.42-0.561,0.599c-0.242,0.207-0.492,0.407-0.725,0.626c-0.342,0.323-0.91,1.305-1.486,0.934
		c-0.161-0.105-0.3-0.247-0.455-0.363C24.461,40.412,23.482,38.695,22.495,38.755z"/>
</g>
</svg>
<?php
		return ob_get_clean();
	}//create_src_pxfw_logo_svg()

	/**
	 * リンクアイコンのSVGロゴソースを返す
	 */
	public function create_src_link_icon_uri($type, $opt = array()){
		$colors = $this->get_color_scheme();
		if( is_array($opt['colors']) ){
			foreach( $opt['colors'] as $key=>$val ){
				$colors[$key] = $val;
			}
		}
		$colors['linkx'] = '#fff';
		switch($type){
			case 'blank':
				$tpl = 'blank';
				break;
			case 'download':
				$tpl = 'download';
				break;
			case 'pdf':
				$tpl = 'pdf';
				break;
			case 'up':
				$points = '3.631,9.183 7.001,3.817 10.369,9.183';
				break;
			case 'down':
				$points = '10.369,3.817 6.999,9.183 3.631,3.817';
				break;
			case 'back':
				$points = '9.683,9.869 4.317,6.499 9.683,3.131';
				break;
			case 'icon':
			default:
				$type = 'icon';
				$tpl = 'icon';
				$points = '4.317,3.131 9.683,6.501 4.317,9.869';
				break;
		}
		ob_start();
		if( $tpl == 'blank' ){?>
<svg version="1.1" id="link_<?php print t::h($type); ?>" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="14px" height="13px" viewBox="0 0 14 13" enable-background="new 0 0 14 13" xml:space="preserve">
<g><path fill="<?php print t::h($colors['link']); ?>" d="M0,3v10h10V3H0z M9,12H1V6h8V12z"/><rect x="1" y="6" fill="<?php print t::h($colors['linkx']); ?>" width="8" height="6" /></g>
<g><path fill="<?php print t::h($colors['link']); ?>" d="M4,0v10h10V0H4z M13,9H5V3h8V9z"/><rect x="5" y="3" fill="<?php print t::h($colors['linkx']); ?>" width="8" height="6" /></g>
</svg>
<?php }elseif( $tpl == 'download' ){ ?>
<svg version="1.1" id="link_<?php print t::h($type); ?>" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="14px" height="13px" viewBox="0 0 14 13" enable-background="new 0 0 14 13" xml:space="preserve">
<polygon fill="<?php print t::h($colors['link']); ?>" points="13,8 13,12 1,12 1,8 0,8 0,13 14,13 14,8 "/>
<polygon fill="<?php print t::h($colors['link']); ?>" points="10.062,7.093 10.062,0.968 3.938,0.968 3.938,7.093 1.824,7.093 7,11.031 12.176,7.093 "/>
</svg>
<?php }elseif( $tpl == 'pdf' ){ ?>
<svg version="1.1" id="link_<?php print t::h($type); ?>" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 width="14px" height="13px" viewBox="0 0 14 13" enable-background="new 0 0 14 13" xml:space="preserve">
<path fill="<?php print t::h($colors['link']); ?>" d="M1,0v13h12V0H1z"/>
<g>
	<path fill="<?php print t::h($colors['linkx']); ?>" d="M2.021,8.398V4.602h1.433c0.252,0,0.444,0.012,0.578,0.036C4.218,4.669,4.375,4.729,4.5,4.814
		c0.126,0.088,0.227,0.21,0.304,0.367C4.882,5.34,4.92,5.512,4.92,5.7c0,0.321-0.103,0.595-0.308,0.82
		C4.406,6.743,4.035,6.855,3.498,6.855H2.524v1.543H2.021z M2.524,6.407h0.982c0.324,0,0.555-0.062,0.691-0.183
		s0.205-0.29,0.205-0.51c0-0.159-0.04-0.295-0.12-0.408C4.201,5.194,4.096,5.12,3.964,5.083C3.88,5.061,3.723,5.049,3.496,5.049
		H2.524V6.407z"/>
	<path fill="<?php print t::h($colors['linkx']); ?>" d="M5.56,8.398V4.602h1.308c0.295,0,0.521,0.018,0.676,0.054c0.218,0.051,0.403,0.141,0.557,0.272
		c0.2,0.168,0.351,0.385,0.449,0.649c0.1,0.264,0.149,0.563,0.149,0.901c0,0.288-0.033,0.545-0.101,0.768
		C8.531,7.468,8.445,7.653,8.34,7.799S8.118,8.061,7.993,8.144S7.717,8.292,7.54,8.333C7.363,8.378,7.16,8.398,6.93,8.398H5.56z
		 M6.062,7.95h0.811c0.25,0,0.447-0.023,0.59-0.069s0.256-0.113,0.341-0.197c0.118-0.119,0.211-0.279,0.277-0.481
		c0.067-0.201,0.101-0.444,0.101-0.731c0-0.396-0.065-0.701-0.196-0.915C7.855,5.343,7.697,5.2,7.51,5.127
		C7.376,5.075,7.159,5.049,6.86,5.049H6.062V7.95z"/>
	<path fill="<?php print t::h($colors['linkx']); ?>" d="M9.417,8.398V4.602h2.562v0.447H9.92v1.176h1.782v0.448H9.92v1.726H9.417z"/>
</g>
</svg>
<?php }else{ ?>
<svg version="1.1" id="link_<?php print t::h($type); ?>" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="14px" height="13px" viewBox="0 0 14 13" enable-background="new 0 0 14 13" xml:space="preserve">
<rect y="2" width="14" height="9" fill="<?php print t::h($colors['link']); ?>" />
<polygon fill="<?php print t::h($colors['linkx']); ?>" points="<?php print t::h($points); ?>"/>
</svg>
<?php
		}
		return 'data:image/svg+xml;base64,'.base64_encode( ob_get_clean() );
	}//create_src_link_icon_uri()

	/**
	 * セットアップを検証する
	 */
	public function setup_test(){
		$errors = array();

		// システムディレクトリの確認
		$realpath = $this->px->dbh()->get_realpath( $this->px->get_conf('paths.px_dir').'_sys' );
		if( !is_dir( $realpath ) ){
			array_push( $errors, 'システムディレクトリ '.t::h($realpath).' が存在しません。' );
		}elseif( !is_writable( $this->px->get_conf('paths.px_dir').'_sys' ) ){
			array_push( $errors, 'システムディレクトリ '.t::h($realpath).' に書き込み許可がありません。' );
		}

		// 公開キャッシュディレクトリの確認
		$realpath = $this->px->dbh()->get_realpath( './_caches/' );
		if( !is_dir( $realpath ) ){
			array_push( $errors, '公開キャッシュディレクトリ '.t::h($realpath).' が存在しません。' );
		}elseif( !is_writable( './_caches/' ) ){
			array_push( $errors, '公開キャッシュディレクトリ '.t::h($realpath).' に書き込み許可がありません。' );
		}
		return $errors;
	}

	/**
	 * セットアップ検証結果を表示する
	 */
	public function mk_setup_test( $errors = array() ){
		// 結果のエラーメッセージ(または成功メッセージ)を生成して返す。
		$rtn = '';
		if( count($errors) ){
			$rtn .= '<p class="error">Pickles Framework のセットアップに、一部不備があります。次の項目を確認してください。</p>';
			$rtn .= '<ul class="error">';
			foreach( $errors as $error ){
				$rtn .= '<li>'.t::h($error).'</li>';
			}
			$rtn .= '</ul>';

		}else{
			$rtn .= '<p>おめでとうございます！ Pickles Framework のセットアップは正常に完了しました。</p>';
		}
		return $rtn;
	}

}

?>