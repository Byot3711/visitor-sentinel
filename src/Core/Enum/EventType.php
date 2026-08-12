<?php
declare( strict_types=1 );

namespace VisitorSentinel\Core\Enum;

final class EventType {
	const SUSPICIOUS_REQUEST      = 'suspicious_request';
	const SUSPICIOUS_QUERY        = 'suspicious_query';
	const SUSPICIOUS_USER_AGENT   = 'suspicious_user_agent';
	const HONEYPOT_TRIGGERED      = 'honeypot_triggered';
	const FAST_SUBMIT_BOT         = 'fast_submit_bot';
	const RATE_LIMIT              = 'rate_limit';
	const TRAFFIC_FLOOD           = 'traffic_flood';
	const LOGIN_FAILED            = 'login_failed';
	const BRUTE_FORCE_LOGIN       = 'brute_force_login';
	const CREDENTIAL_STUFFING     = 'credential_stuffing';
	const NOT_FOUND               = 'not_found';
	const NOT_FOUND_FLOOD         = 'not_found_flood';
	const COMMENT_SPAM            = 'comment_spam';
	const COMMENT_SPAM_KEYWORD    = 'comment_spam_keyword';
	const XMLRPC_ABUSE            = 'xmlrpc_abuse';
	const NON_BROWSER_CLIENT      = 'non_browser_client';
	const EMPTY_USER_AGENT        = 'empty_user_agent';
	const HONEYTOKEN_API_KEY_USED = 'honeytoken_api_key_used';
	const HONEYTOKEN_USERNAME     = 'honeytoken_username_used';
	const HONEYTOKEN_EMAIL        = 'honeytoken_email_harvested';
	const HONEYFILE_ACCESSED      = 'honeyfile_accessed';
	const ABUSEIPDB_HIT           = 'abuseipdb_hit';
	const TOR_EXIT_NODE           = 'tor_exit_node';
}
