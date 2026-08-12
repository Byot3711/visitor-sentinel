<?php
declare( strict_types=1 );

namespace VisitorSentinel\Detection;

use VisitorSentinel\Core\Enum\EventType;

class RulesEngine {
	private array $rules = array();

	public function __construct() {
		$this->loadCoreRules();
		$this->rules = apply_filters( 'visitor_sentinel_rules', $this->rules );
	}

	private function loadCoreRules(): void {
		$this->rules = array(
			array( 'id' => 'core_sqli_01',    'pattern' => '#union\s+select#i',                         'target' => 'uri', 'score' => 40, 'action' => 'log', 'type' => EventType::SUSPICIOUS_REQUEST ),
			array( 'id' => 'core_sqli_02',    'pattern' => '#information_schema#i',                   'target' => 'uri', 'score' => 40, 'action' => 'log', 'type' => EventType::SUSPICIOUS_REQUEST ),
			array( 'id' => 'core_xss_01',     'pattern' => '#<script|javascript:|onerror=#i',         'target' => 'uri', 'score' => 35, 'action' => 'log', 'type' => EventType::SUSPICIOUS_REQUEST ),
			array( 'id' => 'core_shell_01',   'pattern' => '#(system|passthru|proc_open|assert)\s*\(#i', 'target' => 'uri', 'score' => 50, 'action' => 'log', 'type' => EventType::SUSPICIOUS_REQUEST ),
			array( 'id' => 'core_tool_01',    'pattern' => '#(sqlmap|nikto|nmap|wpscan|acunetix|gobuster|zgrab)#i', 'target' => 'ua', 'score' => 45, 'action' => 'log', 'type' => EventType::SUSPICIOUS_USER_AGENT ),
			array( 'id' => 'core_tool_02',    'pattern' => '#(headlesschrome|phantomjs|selenium|puppeteer|scrapy)#i', 'target' => 'ua', 'score' => 40, 'action' => 'log', 'type' => EventType::SUSPICIOUS_USER_AGENT ),
			array( 'id' => 'core_lib_01',     'pattern' => '#(python-requests|python-urllib|axios/|node-fetch|aiohttp/|go-http-client)#i', 'target' => 'ua', 'score' => 25, 'action' => 'log', 'type' => EventType::SUSPICIOUS_USER_AGENT ),
			array( 'id' => 'core_brute_01',   'pattern' => '#wp-json/wp/v2/users#',                   'target' => 'uri', 'score' => 15, 'action' => 'log', 'type' => EventType::SUSPICIOUS_REQUEST ),
			array( 'id' => 'core_bkp_01',     'pattern' => '#\.(sql|bak|zip|tar\.gz)\b#i',             'target' => 'uri', 'score' => 25, 'action' => 'log', 'type' => EventType::SUSPICIOUS_REQUEST ),
			array( 'id' => 'core_path_01',    'pattern' => '#(wp-config\.php|\.env|\.git/|id_rsa|\.htpasswd|shell\.php)#i', 'target' => 'uri', 'score' => 45, 'action' => 'log', 'type' => EventType::SUSPICIOUS_REQUEST ),
			array( 'id' => 'core_traversal',  'pattern' => '#\.\.%2f|\.\./|\.%00#i',                   'target' => 'uri', 'score' => 40, 'action' => 'log', 'type' => EventType::SUSPICIOUS_REQUEST ),
		);
	}

	public function evaluate( string $uri, string $ua, array $server ): array {
		$events = array();
		foreach ( $this->rules as $rule ) {
			switch ( $rule['target'] ) {
				case 'uri':
					$haystack = strtolower( rawurldecode( $uri ) );
					break;
				case 'ua':
					$haystack = strtolower( $ua );
					break;
				case 'referer':
					$haystack = strtolower( sanitize_text_field( wp_unslash( isset( $server['HTTP_REFERER'] ) ? $server['HTTP_REFERER'] : '' ) ) );
					break;
				default:
					$haystack = '';
			}
			if ( '' === $haystack ) {
				continue;
			}
			if ( preg_match( $rule['pattern'], $haystack, $m ) ) {
				$events[] = array(
					'rule_id' => $rule['id'],
					'type'    => $rule['type'],
					'score'   => (int) $rule['score'],
					'action'  => $rule['action'],
					'matched' => $m[0],
				);
			}
		}
		return $events;
	}
}
