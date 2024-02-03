<?php
namespace clickwhale\includes\front\tracking;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_OS {

	public function __construct( $ua ) {
		$this->detectUnix( $ua );
		$this->detectLinux( $ua );
		$this->detectBsd( $ua );
		$this->detectDarwin( $ua );
		$this->detectWindows( $ua );
		$this->detectAndroid( $ua );
		$this->detectChromeos( $ua );
		$this->detectBlackberry( $ua );
		$this->detectWebos( $ua );
		$this->detectKaiOS( $ua );
		$this->detectSymbian( $ua );
		$this->detectNokiaOs( $ua );
		$this->detectTizen( $ua );
		$this->detectSailfish( $ua );
		$this->detectBada( $ua );
		$this->detectBrew( $ua );
		$this->detectQtopia( $ua );
		$this->detectOpenTV( $ua );
		$this->detectRemainingOperatingSystems( $ua );
	}


	/* Darwin */

	private function detectDarwin( $ua ) {
		/* iOS */

		if ( preg_match( '/\(iOS;/u', $ua ) ) {
			$this->name = 'iOS';
			$this->type = 'mobile';
		}

		if ( preg_match( '/(iPhone|iPad|iPod)/u', $ua ) && ! preg_match( '/like iPhone/u', $ua ) ) {
			$this->name = 'iOS';
			$this->type = 'mobile';

			if ( preg_match( '/CPU like Mac OS X/u', $ua, $match ) ) {
				$this->version = '1.0';
			}

			if ( preg_match( '/OS (.*) like Mac OS X/u', $ua, $match ) ) {
				$this->version = str_replace( '_', '.', $match[1] );
			}

			if ( preg_match( '/iPhone OS ([0-9._]*);/u', $ua, $match ) ) {
				$this->version = str_replace( '_', '.', $match[1] );
			}

			if ( preg_match( '/iOS ([0-9.]*);/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			if ( preg_match( '/iPhone Simulator;/u', $ua ) ) {
				$this->type = 'emulator';
			}

		} elseif ( preg_match( '/Mac OS X/u', $ua ) || preg_match( '/;os=Mac/u', $ua ) ) {
			/* OS X */

			$this->name = 'OS X';

			if ( preg_match( '/Mac OS X (1[0-9][0-9\._]*)/u', $ua, $match ) ) {
				$this->version = str_replace( '_', '.', $match[1] );
			}

			if ( preg_match( '/;os=Mac (1[0-9][0-9[\.,]*)/u', $ua, $match ) ) {
				$this->version = str_replace( ',', '.', $match[1] );
			}

			if ( $this->version && $this->version === '10.16' ) {
				$this->version = '11.0';
			}

			$this->type = 'desktop';
		}

		/* Darwin */

		if ( preg_match( '/Darwin(?:\/([0-9]+).[0-9]+)?/u', $ua, $match ) ) {
			if ( preg_match( '/\(X11;/u', $ua ) ) {
				/* Darwin */
				$this->name = 'Darwin';
				$this->type = 'desktop';
			} elseif ( preg_match( '/\((?:x86_64|i386|Power%20Macintosh)\)/u', $ua ) ) {
				/* OS X */
				$this->name = 'OS X';
				$this->type = 'desktop';

			} else {
				/* iOS */
				$this->name = 'iOS';
				$this->type = 'mobile';

			}
		}

		/* Mac OS */

		if ( preg_match( '/(; |\()Macintosh;/u', $ua ) && ! preg_match( '/OS X/u', $ua ) ) {
			$this->name = 'Mac OS';
			$this->type = 'desktop';
		}
	}


	/* Android */

	private function detectAndroid( $ua ) {
		/* Android */

		if ( preg_match( '/Andr[0o]id/ui', $ua ) ) {
			$falsepositive = false;

			/* Prevent the Mobile IE 11 Franken-UA from matching Android */
			if ( preg_match( '/IEMobile\/1/u', $ua ) ) {
				$falsepositive = true;
			}
			if ( preg_match( '/Windows Phone 10/u', $ua ) ) {
				$falsepositive = true;
			}

			/* Prevent Windows 10 IoT Core from matching Android */
			if ( preg_match( '/Windows IoT/u', $ua ) ) {
				$falsepositive = true;
			}

			/* Prevent from OSes that claim to be 'like' Android from matching */
			if ( preg_match( '/like Android/u', $ua ) ) {
				$falsepositive = true;
			}
			if ( preg_match( '/COS like Android/u', $ua ) ) {
				$falsepositive = false;
			}

			if ( ! $falsepositive ) {
				$this->name = 'Android';

				if ( preg_match( '/Andr[0o]id(?: )?(?:AllPhone_|CyanogenMod_|OUYA )?(?:\/)?v?([0-9.]+)/ui',
					str_replace( '-update', ',', $ua ), $match ) ) {
					$this->version = $match[1];
				}

				if ( preg_match( '/Android [0-9][0-9].[0-9][0-9].[0-9][0-9]\(([^)]+)\);/u',
					str_replace( '-update', ',', $ua ), $match ) ) {
					$this->version = $match[1];
				}

				if ( preg_match( '/Android Eclair/u', $ua ) ) {
					$this->version = '2.0';
				}

				if ( preg_match( '/Android KeyLimePie/u', $ua ) ) {
					$this->version = '4.4';
				}

				if ( preg_match( '/Android (?:L|4.4.99);/u', $ua ) ) {
					$this->version = '5';
				}

				if ( preg_match( '/Android (?:M|5.[01].99);/u', $ua ) ) {
					$this->version = '6';
				}

				if ( preg_match( '/Android (?:N|6.0.99);/u', $ua ) ) {
					$this->version = '7';
				}
				$this->type = 'mobile';

				if ( floatval( $this->version ) >= 3 ) {
					$this->type = 'tablet';
				}
				if ( floatval( $this->version ) >= 4 && preg_match( '/Mobile/u', $ua ) ) {
					$this->type = 'mobile';
				}

				$candidates = [];

				if ( preg_match( '/Build/ui', $ua ) && ( ! preg_match( '/AppleWebKit.*Build/ui',
							$ua ) || preg_match( '/Build.*AppleWebKit/ui', $ua ) ) ) {
					/* Normal Android useragent strings */

					if ( preg_match( '/; [a-z][a-zA-Z][-_][a-zA-Z][a-zA-Z] ([^;]*[^;\s])\s+(?:BUILD|Build|build)/u',
						$ua, $match ) ) {
						$candidates[] = $match[1];
					}

					if ( preg_match( '/Android [A-Za-z]+; (?:[a-zA-Z][a-zA-Z](?:[-_][a-zA-Z][a-zA-Z])?) Build\/([^\/]*)\//u',
						$ua, $match ) ) {
						$candidates[] = $match[1];
					}

					if ( preg_match( '/;\+? ?(?:\*\*)?([^;]*[^;\s]);?\s+(?:BUILD|Build|build)/u', $ua, $match ) ) {
						$candidates[] = $match[1];
					}
				} elseif ( preg_match( '/\(Linux; Android [0-9\.]+; ([^\/]+)(; wv)?\) AppleWebKit/u', $ua, $match ) ) {
					/* New style minimal Android useragent string */

					$candidates[] = $match[1];

				} elseif ( preg_match( '/Mozilla\//ui', $ua ) ) {
					/* Old Android useragent strings */

					if ( preg_match( '/Linux; (?:arm; |arm_64; )?(?:U; )?Android [^;]+; (?:[a-zA-Z][a-zA-Z](?:[-_][a-zA-Z][a-zA-Z])?; )?(?:[^;]+; ?)?([^\/;]+)\) /u',
						$ua, $match ) ) {
						$candidates[] = $match[1];
					} elseif ( preg_match( '/\(([^;]+);U;Android\/[^;]+;[0-9]+\*[0-9]+;CTC\/2.0\)/u', $ua, $match ) ) {
						$candidates[] = $match[1];
					}
				} else {
					/* Other applications */

					if ( preg_match( '/[34]G Explorer\/[0-9.]+ \(Linux;Android [0-9.]+,([^\)]+)\)/u', $ua, $match ) ) {
						$candidates[] = $match[1];
					}

					if ( preg_match( '/GetJarSDK\/.*android\/[0-9.]+ \([^;]+; [^;]+; ([^\)]+)\)$/u', $ua, $match ) ) {
						$candidates[] = $match[1];
					}
				}

				$candidates = array_unique( $candidates );

				for ( $c = 0; $c < count( $candidates ); $c ++ ) {
					if ( preg_match( '/^[a-zA-Z][a-zA-Z](?:[-_][a-zA-Z][a-zA-Z])?$/u', $candidates[ $c ] ) ) {
						unset( $candidates[ $c ] );
						continue;
					}

					if ( preg_match( '/^Android [0-9\.]+$/u', $candidates[ $c ] ) ) {
						unset( $candidates[ $c ] );
						continue;
					}

					/* Ignore "K" or "Unspecified Device" as a device, as it is a dummy value used by Chrome UA reduction */

					if ( $candidates[ $c ] == 'K' || $candidates[ $c ] == 'Unspecified Device' ) {
						unset( $candidates[ $c ] );
						continue;
					}

					$candidates[ $c ] = preg_replace( '/^[a-zA-Z][a-zA-Z][-_][a-zA-Z][a-zA-Z]\s+/u', '',
						$candidates[ $c ] );
					$candidates[ $c ] = preg_replace( '/(.*) - [0-9\.]+ - (?:with Google Apps - )?API [0-9]+ - [0-9]+x[0-9]+/',
						'\\1', $candidates[ $c ] );
					$candidates[ $c ] = preg_replace( '/^sprd-/u', '', $candidates[ $c ] );
					$candidates[ $c ] = preg_replace( '/^HarmonyOS; /u', '', $candidates[ $c ] );
					$candidates[ $c ] = preg_replace( '/; GMSCore.*/u', '', $candidates[ $c ] );
					$candidates[ $c ] = preg_replace( '/; HMSCore.*/u', '', $candidates[ $c ] );
				}

				$candidates = array_unique( $candidates );

				if ( count( $candidates ) ) {
					$this->type  = 'mobile';
					$this->model = $candidates[0];
				}

				if ( preg_match( '/HP eStation/u', $ua ) ) {
					$this->manufacturer = 'HP';
					$this->model        = 'eStation';
					$this->type         = 'printer';
				}
			}
		}

		if ( preg_match( '/\(Linux; (?:U; )?(?:([0-9.]+); )?(?:[a-zA-Z][a-zA-Z](?:[-_][a-zA-Z][a-zA-Z])?; )?([^;]+) Build/u',
			$ua, $match ) ) {
			$falsepositive = false;

			if ( $match[2] == 'OpenTV' ) {
				$falsepositive = true;
			}

			if ( ! $falsepositive ) {
				$this->type = 'mobile';
				$this->name = 'Android';

				if ( ! empty( $match[1] ) ) {
					$this->version = $match[1];
				}
			}
		}

		if ( preg_match( '/Linux x86_64; ([^;\)]+)(?:; [a-zA-Z][a-zA-Z](?:[-_][a-zA-Z][a-zA-Z])?)?\) AppleWebKit\/534.24 \(KHTML, like Gecko\) Chrome\/11.0.696.34 Safari\/534.24/u',
			$ua, $match ) ) {
			$this->name = 'Android';
		}

		if ( preg_match( '/\(Linux; U; Linux Ventana; [^;]+; ([^;]+) Build/u', $ua, $match ) ) {
			$this->type = 'mobile';
		}


		/* Harmony OS */

		if ( preg_match( '/HarmonyOS/u', $ua ) ) {
			$this->name = 'Harmony OS';

			if ( preg_match( '/; Android ([0-9\.]+);/u', $ua, $match ) ) {
				$this->version = $match[1];
			}
		}


		/* Aliyun OS */

		if ( preg_match( '/Aliyun/u', $ua ) || preg_match( '/YunOs/ui', $ua ) ) {
			$this->name = 'Aliyun OS';

			if ( preg_match( '/YunOs[ \/]([0-9.]+)/iu', $ua, $match ) ) {
				$this->version = $match[1];
			}

			if ( preg_match( '/AliyunOS ([0-9.]+)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'mobile';
		}

		if ( preg_match( '/Android/u', $ua ) ) {
			if ( preg_match( '/Android v(1.[0-9][0-9])_[0-9][0-9].[0-9][0-9]-/u', $ua, $match ) ) {
				$this->name    = 'Aliyun OS';
				$this->version = $match[1];
			}

			if ( preg_match( '/Android[ \/](1.[0-9].[0-9].[0-9]+)-R?T/u', $ua, $match ) ) {
				$this->name    = 'Aliyun OS';
				$this->version = $match[1];
			}

			if ( preg_match( '/Android ([12].[0-9].[0-9]+)-R-20[0-9]+/u', $ua, $match ) ) {
				$this->name    = 'Aliyun OS';
				$this->version = $match[1];
			}

			if ( preg_match( '/Android 20[0-9]+\./u', $ua, $match ) ) {
				$this->name = 'Aliyun OS';
			}
		}

		/* Baidu Yi */

		if ( preg_match( '/Baidu Yi/u', $ua ) ) {
			$this->name    = 'Baidu Yi';
			$this->version = null;
		}

		/* Google TV */

		if ( preg_match( '/GoogleTV/u', $ua ) ) {
			$this->name = 'Google TV';
			$this->type = 'tv';
		}

		/* LeOS */

		if ( preg_match( '/LeOS/u', $ua ) ) {
			$this->name = 'LeOS';

			if ( preg_match( '/LeOS([0-9\.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'tablet';
		}

		/* WoPhone */

		if ( preg_match( '/WoPhone/u', $ua ) ) {
			$this->name = 'WoPhone';

			if ( preg_match( '/WoPhone\/([0-9\.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'mobile';
		}

		/* COS */

		if ( preg_match( '/(COS|(China|Chinese) Operating System)/ui', $ua ) ) {
			if ( preg_match( '/COS[\/ ]?([0-9]\.[0-9.]+)/ui', $ua, $match ) ) {
				$this->name    = 'COS';
				$this->version = $match[1];
			} elseif ( preg_match( '/(?:\(|; )(?:China|Chinese) Operating System ([0-9]\.[0-9.]*);/ui', $ua,
				$match ) ) {
				$this->name    = 'COS';
				$this->version = $match[1];
			} elseif ( preg_match( '/COS like Android/ui', $ua, $match ) ) {
				$this->name    = 'COS';
				$this->version = null;
				$this->type    = 'mobile';
			} elseif ( preg_match( '/(COS like Android|COSBrowser\/|\(COS;|\(COS 998;)/ui', $ua, $match ) ) {
				$this->name = 'COS';
			}
		}

		/* RemixOS */

		if ( preg_match( '/RemixOS/u', $ua ) ) {
			$this->name    = 'Remix OS';
			$this->version = null;

			if ( preg_match( '/RemixOS ([0-9]\.[0-9])/u', $ua, $match ) ) {
				switch ( $match[1] ) {
					case '5.1':
						$this->version = '1.0';
						break;
					case '6.0':
						$this->version = '2.0';
						break;
				}
			}

			$this->type = 'desktop';
		}
	}

	/* Windows */

	private function detectWindows( $ua ) {
		if ( preg_match( '/(Windows|WinNT|WinCE|WinMobile|Win ?[9MX]|Win(16|32))/u', $ua ) ) {
			$this->name = 'Windows';
			$this->type = 'desktop';


			/* Windows NT */

			if ( preg_match( '/Windows 2000/u', $ua ) ) {
				$this->version = '2000';
			}

			if ( preg_match( '/(Windows XP|WinXP)/u', $ua ) ) {
				$this->version = 'XP';
			}

			if ( preg_match( '/Windows Vista/u', $ua ) ) {
				$this->version = 'Vista';
			}

			if ( preg_match( '/(?:Windows NT |WinNT)([0-9][0-9]?\.[0-9])/u', $ua, $match ) ) {
				switch ( $match[1] ) {
					case '10.1':
					case '10.0':
					case '6.4':
						$this->version = '10';
						break;

					case '6.3':
						if ( preg_match( '/; ARM;/u', $ua ) ) {
							$this->version = 'RT 8.1';
						} else {
							$this->version = '8.1';
						}
						break;

					case '6.2':
						if ( preg_match( '/; ARM;/u', $ua ) ) {
							$this->version = 'RT';
						} else {
							$this->version = '8';
						}
						break;

					case '6.1':
						$this->version = '7';
						break;
					case '6.0':
						$this->version = 'Vista';
						break;
					case '5.2':
						$this->version = 'Server 2003';
						break;
					case '5.1':
						$this->version = 'XP';
						break;
					case '5.0':
						$this->version = '2000';
						break;
					default:
						$this->version = 'NT ' . $match[1];
						break;
				}
			}


			/* Windows 10 IoT Core */

			if ( preg_match( '/Windows IoT (1[0-9]\.[0-9]);/u', $ua, $match ) ) {
				$this->version = '10 IoT Core';
			}


			/* Windows */

			if ( preg_match( '/(Windows 95|Win95)/u', $ua ) ) {
				$this->version = '95';
			}

			if ( preg_match( '/(Windows 98|Win98)/u', $ua ) ) {
				$this->version = '98';
			}

			if ( preg_match( '/(Windows M[eE]|WinME)/u', $ua ) ) {
				$this->version = 'ME';
			}

			if ( preg_match( '/(?:Windows|Win 9x) (([1234]\.[0-9])[0-9\.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];

				switch ( $match[2] ) {
					case '4.0':
						$this->version = '95';
						break;
					case '4.1':
						$this->version = '98';
						break;
					case '4.9':
						$this->version = 'ME';
						break;
				}
			}


			/* Windows Mobile and Windows Phone */

			if ( preg_match( '/WPDesktop/u', $ua ) ) {
				$this->name    = 'Windows Phone';
				$this->version = '8.0';
				$this->type    = 'mobile';
			}

			if ( preg_match( '/WP7/u', $ua ) ) {
				$this->name    = 'Windows Phone';
				$this->version = '7';
				$this->type    = 'mobile';
			}

			if ( preg_match( '/WinMobile/u', $ua ) ) {
				$this->name = 'Windows Mobile';
				$this->type = 'mobile';

				if ( preg_match( '/WinMobile\/([0-9.]*)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}
			}

			if ( preg_match( '/(Windows CE|WindowsCE|WinCE)/u', $ua ) ) {
				$this->type = 'mobile';

				if ( preg_match( '/ IEMobile/u', $ua ) ) {
					$this->name = 'Windows Mobile';

					if ( preg_match( '/ IEMobile\/9/u', $ua ) ) {
						$this->name    = 'Windows Phone';
						$this->version = '7.5';
					}

					if ( preg_match( '/ IEMobile 8/u', $ua ) ) {
						$this->version = '6.5';
					}

					if ( preg_match( '/ IEMobile 7/u', $ua ) ) {
						$this->version = '6.1';
					}

					if ( preg_match( '/ IEMobile 6/u', $ua ) ) {
						$this->version = '6.0';
					}
				} else {
					$this->name = 'Windows CE';

					if ( preg_match( '/WindowsCEOS\/([0-9.]*)/u', $ua, $match ) ) {
						$this->version = $match[1];
					}

					if ( preg_match( '/Windows CE ([0-9.]*)/u', $ua, $match ) ) {
						$this->version = $match[1];
					}
				}
			}

			if ( preg_match( '/Microsoft Windows; (PPC|Smartphone)/u', $ua ) ) {
				$this->name = 'Windows Mobile';
				$this->type = 'mobile';
			}

			if ( preg_match( '/Windows CE; (PPC|Smartphone)/u', $ua ) ) {
				$this->name = 'Windows Mobile';
				$this->type = 'mobile';
			}


			/* Detect models in common places */

			if ( preg_match( '/Windows ?Mobile/u', $ua ) ) {
				$this->name = 'Windows Mobile';
				$this->type = 'mobile';

				if ( preg_match( '/Windows ?Mobile[\/ ]([0-9.]*)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}
			}

			if ( preg_match( '/(Windows Phone|Windows NT 1[0-9]\.[0-9]; ARM|WPDesktop|ZuneWP7)/u', $ua ) ) {
				$this->name = 'Windows Phone';
				$this->type = 'mobile';

				if ( preg_match( '/Windows Phone(?: OS)?[ \/]([0-9.]*)/u', $ua, $match ) ) {
					$this->version = $match[1];

					if ( intval( $match[1] ) < 7 ) {
						$this->name = 'Windows Mobile';
					}
				}

				/* Windows Phone 10 Continuum */
				if ( preg_match( '/Windows NT 1[0-9]\.[0-9]; ARM; ([^;\)\s][^;\)]*)\)/u', $ua, $match ) ) {
					$this->type = 'desktop';
				}
			}
		} elseif ( preg_match( '/WMPRO/u', $ua ) ) {
			$this->name = 'Windows Mobile';
			$this->type = 'mobile';
		}
	}

	/* Jolla Sailfish */

	private function detectSailfish( $ua ) {
		if ( preg_match( '/Sailfish;/u', $ua ) ) {
			$this->name    = 'Sailfish';
			$this->version = null;

			if ( preg_match( '/Mobile/u', $ua ) ) {
				$this->type = 'mobile';
			}

			if ( preg_match( '/Tablet/u', $ua ) ) {
				$this->type = 'tablet';
			}
		}
	}


	/* Bada */

	private function detectBada( $ua ) {
		if ( preg_match( '/[b|B]ada/u', $ua ) ) {
			$this->name = 'Bada';

			if ( preg_match( '/[b|B]ada[\/ ]([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'mobile';
		}
	}


	/* Tizen */

	private function detectTizen( $ua ) {
		if ( preg_match( '/Tizen/u', $ua ) ) {
			$this->name = 'Tizen';

			if ( preg_match( '/Tizen[\/ ]?([0-9.]*[0-9])/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			if ( ! $this->type && preg_match( '/Mobile/iu', $ua, $match ) ) {
				$this->type = 'mobile';
			}
		}

		if ( preg_match( '/Linux\; U\; Android [0-9.]+\; ko\-kr\; SAMSUNG\; (NX[0-9]+[^\)]]*)/u', $ua, $match ) ) {
			$this->name    = 'Tizen';
			$this->version = null;
			$this->type    = 'camera';
		}
	}


	/* Symbian */

	private function detectSymbian( $ua ) {
		if ( ! preg_match( '/(EPOC|Series|Symbian|S60|UIQ)/ui', $ua ) ) {
			return;
		}

		/* EPOC */

		if ( preg_match( '/EPOC(?:32)?[;\-\)]/u', $ua, $match ) ) {
			$this->name = 'EPOC';
			$this->type = 'pda';

			if ( preg_match( '/Crystal\/([0-9.]*)/u', $ua, $match ) || preg_match( '/Nokia\/Series-9200/u', $ua ) ) {
				$this->name    = 'Series80';
				$this->version = '1.0';
				$this->type    = 'mobile';
			}
		}

		/* Series 80 */

		if ( preg_match( '/Series80\/([0-9.]*)/u', $ua, $match ) ) {
			$this->name    = 'Series80';
			$this->version = $match[1];
			$this->type    = 'mobile';
		}

		/* Series 60 */

		if ( preg_match( '/Symbian\/3/u', $ua ) ) {
			$this->name    = 'Series60';
			$this->version = '5.2';
			$this->type    = 'mobile';
		}

		if ( preg_match( '/Series[ ]?60/u', $ua ) || preg_match( '/S60[V\/;]/u', $ua ) || preg_match( '/S60 Symb/u',
				$ua ) ) {
			$this->name = 'Series60';
			$this->type = 'mobile';

			if ( preg_match( '/Series60\/([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			if ( preg_match( '/S60\/([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			if ( preg_match( '/S60V([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}
		}

		/* UIQ */

		if ( preg_match( '/UIQ\/([0-9.]*)/u', $ua, $match ) ) {
			$this->name    = 'UIQ';
			$this->version = $match[1];
			$this->type    = 'mobile';
		}

		/* Symbian */

		if ( preg_match( '/Symbian/u', $ua ) ) {
			$this->type = 'mobile';
		}
	}

	private function detectNokiaOs( $ua ) {
		if ( ! preg_match( '/(Series|MeeGo|Maemo|Geos)/ui', $ua ) ) {
			return;
		}

		/* Series 40 */

		if ( preg_match( '/Series40/u', $ua ) ) {
			$this->name = 'Series40';
			$this->type = 'mobile';
		}

		/* Series 30+ */

		if ( preg_match( '/Series30Plus/u', $ua ) ) {
			$this->name = 'Series30+';
			$this->type = 'mobile';
		}

		/* Meego */

		if ( preg_match( '/MeeGo/u', $ua ) ) {
			$this->name = 'MeeGo';
			$this->type = 'mobile';
		}

		/* Maemo */

		if ( preg_match( '/Maemo/u', $ua ) ) {
			$this->name = 'Maemo';
			$this->type = 'mobile';
		}

		/* GEOS */

		if ( preg_match( '/Geos ([0-9.]+)/u', $ua, $match ) ) {
			$this->name    = 'GEOS';
			$this->version = $match[1];
			$this->type    = 'mobile';
		}
	}


	/* WebOS */

	private function detectWebos( $ua ) {
		if ( preg_match( '/(?:web|hpw)OS\/(?:HP webOS )?([0-9.]*)/u', $ua, $match ) ) {
			$this->name    = 'webOS';
			$this->version = $match[1];
			$this->type    = preg_match( '/Tablet/iu', $ua ) ? 'tablet' : 'mobile';
		}

		if ( preg_match( '/(?:Spark|elite)\/fzz/u', $ua, $match ) || preg_match( '/webOSBrowser/u', $ua, $match ) ) {
			$this->name = 'webOS';
			$this->type = preg_match( '/Tablet/iu', $ua ) ? 'tablet' : 'mobile';
		}

		if ( preg_match( '/ (Pre|Pixi|TouchPad|P160UN?A?)\/[0-9\.]+$/u', $ua, $match ) ) {
			$this->name = 'webOS';
			$this->type = $match[1] == 'TouchPad' ? 'tablet' : 'mobile';
		}
	}


	/* Kai OS */

	private function detectKaiOS( $ua ) {
		if ( preg_match( '/Kai(OS)?\/([0-9.]+)/i', $ua, $match ) ) {
			$this->name = 'KaiOS';
			$this->type = $match[2];
		}
	}

	/* BlackBerry */

	private function detectBlackberry( $ua ) {
		/* BlackBerry OS */

		if ( preg_match( '/RIM([0-9]{3,3})/u', $ua, $match ) ) {
			$this->name = 'BlackBerry OS';
			$this->type = 'mobile';
		}

		if ( preg_match( '/BlackBerry/u', $ua ) && ! preg_match( '/BlackBerry Runtime for Android Apps/u', $ua ) ) {
			$this->name = 'BlackBerry OS';
			$this->type = 'mobile';
		}

		/* BlackBerry 10 */

		if ( preg_match( '/\(BB(1[^;]+); ([^\)]+)\)/u', $ua, $match ) ) {
			$this->name    = 'BlackBerry';
			$this->version = $match[1];
			$this->type    = preg_match( '/Mobile/u', $ua ) ? 'mobile' : 'tablet';

			if ( preg_match( '/Version\/([0-9.]+)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}
		}

		/* BlackBerry Tablet OS */

		if ( preg_match( '/RIM Tablet OS ([0-9.]*)/u', $ua, $match ) ) {
			$this->name    = 'BlackBerry Tablet OS';
			$this->version = $match[1];
			$this->type    = 'tablet';
		} elseif ( preg_match( '/\(PlayBook;/u', $ua ) && preg_match( '/PlayBook Build\/([0-9.]*)/u', $ua, $match ) ) {
			$this->name    = 'BlackBerry Tablet OS';
			$this->version = $match[1];
			$this->type    = 'tablet';
		} elseif ( preg_match( '/PlayBook/u', $ua ) && ! preg_match( '/Android/u', $ua ) ) {
			if ( preg_match( '/Version\/([0-9.]*)/u', $ua, $match ) ) {
				$this->name    = 'BlackBerry Tablet OS';
				$this->version = $match[1];
				$this->type    = 'tablet';
			}
		}
	}


	/* Chrome OS */

	private function detectChromeos( $ua ) {
		/* ChromeCast */

		if ( preg_match( '/CrKey/u', $ua ) && ! preg_match( '/Espial/u', $ua ) ) {
			$this->type = 'tv';
		}

		/* Chrome OS */

		if ( preg_match( '/CrOS/u', $ua ) ) {
			$this->name = 'Chrome OS';
			$this->type = 'desktop';
		}
	}


	/* Open TV */

	private function detectOpenTV( $ua ) {
		if ( preg_match( '/OpenTV/ui', $ua, $match ) ) {
			$this->type    = 'tv';
			$this->name    = 'OpenTV';
			$this->version = null;

			if ( preg_match( '/OpenTV Build\/([0-9\.]+)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			if ( preg_match( '/OpenTV ([0-9\.]+)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			if ( preg_match( '/Opentv([0-9]+)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			if ( preg_match( '/OTV([0-9\.]+)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}
		}
	}


	/* Qtopia */

	private function detectQtopia( $ua ) {
		if ( preg_match( '/Qtopia/u', $ua ) ) {
			$this->name = 'Qtopia';

			if ( preg_match( '/Qtopia\/([0-9.]+)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}
		}
	}


	/* Unix */

	private function detectUnix( $ua ) {
		if ( ! preg_match( '/(UNIX|OSF|ULTRIX|HP-UX|SunOS|Solaris|AIX|IRIX|NEWS-OS|GENIX)/ui', $ua ) ) {
			return;
		}

		/* Unix */

		if ( preg_match( '/Unix/iu', $ua ) ) {
			$this->name = 'Unix';
		}

		/* Unix System V */

		if ( preg_match( '/(?:UNIX_System_V|UNIX_SV) ([0-9.]*)/u', $ua, $match ) ) {
			$this->name    = 'UNIX System V';
			$this->version = $match[1];
			$this->type    = 'desktop';
		}

		/* Digital Unix */

		if ( preg_match( '/OSF1?[ _]/u', $ua ) ) {
			$this->name = 'Digital Unix';

			if ( preg_match( '/OSF1?[ _]V?([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'desktop';
		}

		/* Digital ULTRIX */

		if ( preg_match( '/ULTRIX/u', $ua ) ) {
			$this->name = 'ULTRIX';

			if ( preg_match( '/ULTRIX ([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'desktop';
		}

		/* HP-UX */

		if ( preg_match( '/HP-UX/u', $ua ) ) {
			$this->name = 'HP-UX';

			if ( preg_match( '/HP-UX [A-Z].0?([1-9][0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'desktop';
		}

		/* Solaris */

		if ( preg_match( '/SunOS/u', $ua ) ) {
			$this->name = 'Solaris';

			if ( preg_match( '/SunOS ([1234]\.[0-9\.]+)/u', $ua, $match ) ) {
				$this->name    = 'SunOS';
				$this->version = $match[1];

				if ( preg_match( '/SunOS 4\.1\.([1234])/u', $ua, $match ) ) {
					$this->name = 'Solaris';

					switch ( $match[1] ) {
						case '1':
							$this->version = '1.0';
							break;
						case '2':
							$this->version = '1.0.1';
							break;
						case '3':
							$this->version = '1.1';
							break;
						case '4':
							$this->version = '1.1.2';
							break;
					}
				}
			}

			if ( preg_match( '/SunOS 5\.([123456](?:\.[0-9\.]*)?) /u', $ua, $match ) ) {
				$this->version = '2';
			} elseif ( preg_match( '/SunOS 5\.([0-9\.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'desktop';
		}

		if ( preg_match( '/Solaris(?: ([0-9\.]+))?;/u', $ua, $match ) ) {
			$this->name = 'Solaris';

			if ( preg_match( '/Solaris ([0-9\.]+);/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'desktop';
		}

		/* AIX */

		if ( preg_match( '/AIX/u', $ua ) ) {
			$this->name = 'AIX';

			if ( preg_match( '/AIX ([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'desktop';
		}

		/* IRIX */

		if ( preg_match( '/IRIX/u', $ua ) ) {
			$this->name = 'IRIX';

			if ( preg_match( '/IRIX ([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			if ( preg_match( '/IRIX;?(?:64|32) ([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'desktop';
		}

		/* Sony NEWS OS */

		if ( preg_match( '/NEWS-OS ([0-9\.]+)/u', $ua, $match ) ) {
			$this->name    = 'NEWS OS';
			$this->version = $match[1];
			$this->type    = 'desktop';
		}

		/* NEC EWS-UX */

		if ( preg_match( '/EWS-UNIX rev ([0-9\.]+)/u', $ua, $match ) ) {
			$this->name    = 'EWS-UX';
			$this->version = $match[1];
			$this->type    = 'desktop';
		}

		/* National Semiconductors GENIX */

		if ( preg_match( '/GENIX ([0-9\.]+)/u', $ua, $match ) ) {
			$this->name    = 'GENIX';
			$this->version = $match[1];
			$this->type    = 'desktop';
		}
	}


	/* BSD */

	private function detectBsd( $ua ) {
		if ( ! preg_match( '/(BSD|DragonFly)/ui', $ua ) ) {
			return;
		}

		if ( preg_match( '/X11/u', $ua ) ) {
			$this->type = 'desktop';
		}

		/* BSD/OS */

		if ( preg_match( '/BSD\/386/u', $ua ) ) {
			$this->name = 'BSD/OS';
		}

		if ( preg_match( '/BSD\/OS/u', $ua ) ) {
			$this->name = 'BSD/OS';

			if ( preg_match( '/BSD\/OS ([0-9.]*)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}
		}

		/* FreeBSD */

		if ( preg_match( '/FreeBSD/iu', $ua ) ) {
			$this->name = 'FreeBSD';

			if ( preg_match( '/FreeBSD[ -\/]?([0-9.]*)/iu', $ua, $match ) ) {
				$this->version = $match[1];
			}
		}

		/* OpenBSD */

		if ( preg_match( '/OpenBSD/iu', $ua ) ) {
			$this->name = 'OpenBSD';

			if ( preg_match( '/OpenBSD ?([0-9.]*)/iu', $ua, $match ) ) {
				$this->version = $match[1];
			}
		}

		/* NetBSD */

		if ( preg_match( '/NetBSD/iu', $ua ) ) {
			$this->name = 'NetBSD';

			if ( preg_match( '/NetBSD ?([0-9.]*)/iu', $ua, $match ) ) {
				$this->version = $match[1];
			}
		}

		/* DragonFly */

		if ( preg_match( '/DragonFly/iu', $ua ) ) {
			$this->name = 'DragonFly BSD';
		}
	}


	/* Linux */

	private function detectLinux( $ua ) {
		if ( preg_match( '/Linux/u', $ua ) ) {
			$this->name = 'Linux';

			if ( preg_match( '/X11/u', $ua ) ) {
				$this->type = 'desktop';
			}

			if ( preg_match( '/Antergos Linux/u', $ua ) ) {
				$this->name = 'Antergos Linux';
				$this->type = 'desktop';
			}

			if ( preg_match( '/Arch ?Linux/u', $ua ) ) {
				$this->name = 'Arch Linux';
				$this->type = 'desktop';
			}

			if ( preg_match( '/Black Lab Linux/u', $ua ) ) {
				$this->name = 'Black Lab Linux';
				if ( preg_match( '/Black Lab Linux ([0-9\.]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/CentOS/u', $ua ) ) {
				$this->name = 'CentOS';
				if ( preg_match( '/CentOS\/[0-9\.\-]+el([0-9_]+)/u', $ua, $match ) ) {
					$this->version = str_replace( '_', '.', $match[1] );
				}

				if ( preg_match( '/CentOS Linux release ([0-9\.]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Debian/u', $ua ) ) {
				$this->name = 'Debian';
				if ( preg_match( '/Debian\/([0-9.]*)/iu', $ua, $match ) ) {
					$this->version = $match[1];
				}

				if ( preg_match( '/Debian GNU\/Linux ([0-9\.]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Fedora/u', $ua ) ) {
				$this->name = 'Fedora';
				if ( preg_match( '/Fedora\/[0-9\.\-]+fc([0-9]+)/u', $ua, $match ) ) {
					$this->version = str_replace( '_', '.', $match[1] );
				}

				if ( preg_match( '/Fedora release ([0-9\.]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Gentoo/u', $ua ) ) {
				$this->name = 'Gentoo';
				if ( preg_match( '/Gentoo Base System release ([0-9\.]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/gNewSense/u', $ua ) ) {
				$this->name = 'gNewSense';
				if ( preg_match( '/gNewSense\/[^\(]+\(([0-9\.]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Kubuntu/u', $ua ) ) {
				$this->name = 'Kubuntu';
				if ( preg_match( '/Kubuntu[ \/]([0-9.]*)/iu', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Linux Mint/u', $ua ) ) {
				$this->name = 'Linux Mint';
				if ( preg_match( '/Linux Mint ([0-9\.]+)/iu', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Mandriva Linux/u', $ua ) ) {
				$this->name = 'Mandriva';
				if ( preg_match( '/Mandriva Linux\/[0-9\.\-]+mdv([0-9]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Mageia/u', $ua ) ) {
				$this->name = 'Mageia';
				if ( preg_match( '/Mageia\/[0-9\.\-]+mga([0-9]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				if ( preg_match( '/Mageia ([0-9\.]+)/iu', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Mandriva/u', $ua ) ) {
				$this->name = 'Mandriva';
				if ( preg_match( '/Mandriva\/[0-9\.\-]+mdv([0-9]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/moonOS/u', $ua ) ) {
				$this->name = 'moonOS';
				if ( preg_match( '/moonOS\/([0-9.]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Red Hat/u', $ua ) ) {
				$this->name = 'Red Hat';
				if ( preg_match( '/Red Hat[^\/]*\/[0-9\.\-]+el([0-9_]+)/u', $ua, $match ) ) {
					$this->version = str_replace( '_', '.', $match[1] );
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Slackware/u', $ua ) ) {
				$this->name = 'Slackware';
				if ( preg_match( '/Slackware[ \/](1[0-9.]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/SUSE/u', $ua ) ) {
				$this->name = 'SUSE';
				if ( preg_match( '/SUSE\/([0-9]\.[0-9]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				if ( preg_match( '/openSUSE ([0-9\.]+)/iu', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Turbolinux/u', $ua ) ) {
				$this->name = 'Turbolinux';
				if ( preg_match( '/Turbolinux\/([0-9]\.[0-9]+)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/Ubuntu/u', $ua ) ) {
				$this->name = 'Ubuntu';
				if ( preg_match( '/Ubuntu\/([0-9.]*)/u', $ua, $match ) ) {
					$this->version = $match[1];
				}

				if ( preg_match( '/Ubuntu ([0-9\.]+)/iu', $ua, $match ) ) {
					$this->version = $match[1];
				}

				$this->type = 'desktop';
			}

			if ( preg_match( '/《붉은별》\/([0-9.]*)/iu', $ua, $match ) ) {
				$this->name    = 'Red Star';
				$this->version = $match[1];
				$this->type    = 'desktop';
			}

			if ( preg_match( '/Fedora\/[0-9\.\-]+rs([0-9\.]+)/u', $ua, $match ) ) {
				$this->name    = 'Red Star';
				$this->version = str_replace( '_', '.', $match[1] );
				$this->type    = 'desktop';
			}

			if ( preg_match( '/Linux\/X2\/R1/u', $ua ) ) {
				$this->name = 'LiMo';
				$this->type = 'mobile';
			}


			if ( preg_match( '/Linux\/SLP\/([0-9.]+)/u', $ua, $match ) ) {
				$this->name    = 'Linux SLP';
				$this->version = $match[1];
				$this->type    = 'mobile';
			}


			if ( preg_match( '/LinuxOS\//u', $ua ) && preg_match( '/Software\/R5/u', $ua ) ) {
				$this->name = 'EZX Linux';
				$this->type = 'mobile';
			}
		}

		if ( preg_match( '/elementary OS/u', $ua ) ) {
			$this->name = 'elementary OS';
			if ( preg_match( '/elementary OS ([A-Za-z]+)/u', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'desktop';
		}

		if ( preg_match( '/\(Ubuntu; (Mobile|Tablet)/u', $ua ) ) {
			$this->name = 'Ubuntu Touch';

			if ( preg_match( '/\(Ubuntu; Mobile/u', $ua ) ) {
				$this->type = 'mobile';
			}
			if ( preg_match( '/\(Ubuntu; Tablet/u', $ua ) ) {
				$this->type = 'tablet';
			}
		}

		if ( preg_match( '/(?:\(|; )Ubuntu ([0-9.]+) like Android/u', $ua, $match ) ) {
			$this->name    = 'Ubuntu Touch';
			$this->version = $match[1];
			$this->type    = 'mobile';
		}

		if ( preg_match( '/Lindows ([0-9.]+)/u', $ua, $match ) ) {
			$this->name    = 'Lindows';
			$this->version = $match[1];
			$this->type    = 'desktop';
		}
	}


	/* Brew */

	private function detectBrew( $ua ) {
		if ( preg_match( '/REX; U/ui', $ua ) || preg_match( '/REXL4/ui', $ua ) ) {
			$this->name = 'REX';
			$this->type = 'mobile';
		}

		if ( preg_match( '/[\(\s\-;]BREW[\s\/\-;]/ui', $ua ) || preg_match( '/BMP( [0-9.]*)?; U/u',
				$ua ) || preg_match( '/B(?:rew)?MP\/([0-9.]*)/u', $ua ) ) {
			$this->name = 'Brew';

			if ( preg_match( '/BREW MP/iu', $ua ) || preg_match( '/B(rew)?MP/iu', $ua ) ) {
				$this->name = 'Brew MP';
			}

			if ( preg_match( '/; Brew ([0-9.]+);/iu', $ua, $match ) ) {
				$this->version = $match[1];
			} elseif ( preg_match( '/BREW; U; ([0-9.]+)/iu', $ua, $match ) ) {
				$this->version = $match[1];
			} elseif ( preg_match( '/[\(;]BREW[\/ ]([0-9.]+)/iu', $ua, $match ) ) {
				$this->version = $match[1];
			} elseif ( preg_match( '/BREW MP ([0-9.]*)/iu', $ua, $match ) ) {
				$this->version = $match[1];
			} elseif ( preg_match( '/BMP ([0-9.]*); U/iu', $ua, $match ) ) {
				$this->version = $match[1];
			} elseif ( preg_match( '/B(?:rew)?MP\/([0-9.]*)/iu', $ua, $match ) ) {
				$this->version = $match[1];
			}

			$this->type = 'mobile';
		}
	}


	/* Remaining operating systems */

	private function detectRemainingOperatingSystems( $ua ) {
		if ( ! preg_match( '/(BeOS|Haiku|AmigaOS|MorphOS|AROS|VMS|RISC|Joli|OS\/2|Inferno|Syllable|Grid|MTK|MRE|MAUI|Nucleus|QNX|VRE|SpreadTrum|ThreadX)/ui',
			$ua ) ) {
			return;
		}

		$patterns = [
			[ 'name' => 'BeOS', 'regexp' => [ '/BeOS/iu' ], 'type' => 'desktop' ],
			[ 'name' => 'Haiku', 'regexp' => [ '/Haiku/iu' ], 'type' => 'desktop' ],
			[
				'name'   => 'AmigaOS',
				'regexp' => [ '/AmigaOS ?([0-9.]+)/iu', '/AmigaOS/iu' ],
				'type'   => 'desktop'
			],
			[
				'name'   => 'MorphOS',
				'regexp' => [ '/MorphOS(?: ([0-9.]*))?/iu' ],
				'type'   => 'desktop'
			],
			[ 'name' => 'AROS', 'regexp' => [ '/AROS/iu' ], 'type' => 'desktop' ],
			[
				'name'   => 'OpenVMS',
				'regexp' => [ '/OpenVMS V([0-9.]+)/iu', '/OpenVMS/iu' ],
				'type'   => 'desktop'
			],
			[
				'name'   => 'RISC OS',
				'regexp' => [ '/RISC OS(?:-NC)? ([0-9.]*)/iu', '/RISC OS/iu' ],
				'type'   => 'desktop'
			],
			[ 'name' => 'Joli OS', 'regexp' => [ '/Joli OS\/([0-9.]*)/iu' ], 'type' => 'desktop' ],
			[
				'name'   => 'OS/2',
				'regexp' => [ '/OS\/2;(?: (?:U; )?Warp ([0-9.]*))?/iu' ],
				'type'   => 'desktop'
			],
			[ 'name' => 'Inferno', 'regexp' => [ '/Inferno/iu' ], 'type' => 'desktop' ],
			[ 'name' => 'Syllable', 'regexp' => [ '/Syllable/iu' ], 'type' => 'desktop' ],

			[ 'name' => 'Grid OS', 'regexp' => [ '/Grid OS ([0-9.]*)/iu' ], 'type' => 'tablet' ],

			[ 'name' => 'MRE', 'regexp' => [ '/\(MTK;/iu', '/\/MTK /iu' ], 'type' => 'mobile' ],
			[ 'name' => 'MRE', 'regexp' => [ '/MRE\\\\/iu' ], 'type' => 'mobile' ],
			[
				'name'   => 'MRE',
				'regexp' => [ '/MAUI[-_ ](?:Browser|Runtime)/iu' ],
				'type'   => 'mobile'
			],
			[ 'name' => 'MRE', 'regexp' => [ '/Browser\/MAUI/iu' ], 'type' => 'mobile' ],
			[ 'name' => 'MRE', 'regexp' => [ '/Nucleus RTOS\//iu' ], 'type' => 'mobile' ],
			[ 'name' => 'MRE', 'regexp' => [ '/\/Nucleus/iu' ], 'type' => 'mobile' ],
			[ 'name' => 'MRE', 'regexp' => [ '/Nucleus\//iu' ], 'type' => 'mobile' ],

			[ 'name' => 'QNX', 'regexp' => [ '/QNX/iu' ], 'type' => 'mobile' ],
			[ 'name' => 'VRE', 'regexp' => [ '/\(VRE;/iu' ], 'type' => 'mobile' ],
			[ 'name' => 'SpreadTrum', 'regexp' => [ '/\(SpreadTrum;/iu' ], 'type' => 'mobile' ],

			[ 'name' => 'ThreadX', 'regexp' => [ '/ThreadX(?:_OS)?\/([0-9.]*)/iu' ] ],
		];

		$count = count( $patterns );
		for ( $b = 0; $b < $count; $b ++ ) {
			for ( $r = 0; $r < count( $patterns[ $b ]['regexp'] ); $r ++ ) {
				if ( preg_match( $patterns[ $b ]['regexp'][ $r ], $ua, $match ) ) {
					$this->name = $patterns[ $b ]['name'];

					if ( isset( $match[1] ) && $match[1] ) {
						$this->version = $match[1];
					} else {
						$this->version = null;
					}

					if ( isset( $patterns[ $b ]['type'] ) ) {
						$this->type = $patterns[ $b ]['type'];
					}

					break;
				}
			}
		}
	}
}