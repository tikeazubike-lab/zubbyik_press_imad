<?php
/**
 * AI Chat Bot — Main Integration Class
 *
 * Enqueues assets, registers REST endpoints, handles chat + lead capture,
 * and manages portfolio context caching via transients.
 *
 * @package Malachy_Portfolio
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Malachy_AI_Chatbot {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private $namespace = 'malachy/v1';

	/**
	 * Transient key for portfolio context cache.
	 *
	 * @var string
	 */
	private $context_transient = 'malachy_chat_context';

	/**
	 * Transient TTL in seconds (1 hour).
	 *
	 * @var int
	 */
	private $context_ttl = HOUR_IN_SECONDS;

	/**
	 * Rate limit: max requests per IP per hour.
	 *
	 * @var int
	 */
	private $rate_limit = 20;

	/**
	 * Constructor — hook everything.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'save_post', array( $this, 'clear_context_cache' ), 10, 3 );
	}

	/**
	 * Enqueue chatbot CSS and JS on all frontend pages.
	 */
	public function enqueue_assets() {
		$version = defined( 'MALACHY_THEME_VERSION' ) ? MALACHY_THEME_VERSION : '1.2.0';

		wp_enqueue_style(
			'malachy-ai-chatbot',
			get_template_directory_uri() . '/assets/css/ai-chat-bot.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'malachy-ai-chatbot',
			get_template_directory_uri() . '/assets/js/ai-chat-bot.js',
			array(),
			$version,
			true
		);

		wp_localize_script(
			'malachy-ai-chatbot',
			'malachyChatbotConfig',
			array(
				'apiUrl'      => rest_url( $this->namespace . '/chat' ),
				'leadUrl'     => rest_url( $this->namespace . '/chat/lead' ),
				'nonce'       => wp_create_nonce( 'malachy_chat_nonce' ),
				'placeholder' => __( 'What would you like to fix?', 'malachy-portfolio' ),
				'assistant'   => __( "Assistant", 'malachy-portfolio' ),
				'welcome'     => __( "Hi! I'm Malachy — I help businesses fix email deliverability, secure their domains, and migrate to Microsoft 365 or Google Workspace. What are you struggling with?", 'malachy-portfolio' ),
				'contactUrl'  => home_url( '/#contact' ),
				'suggestions' => array(),
				'model'       => 'deepseek-v4-flash-free',
				'darkMode'    => $this->is_dark_mode(),
			)
		);
	}

	/**
	 * Detect if the site is currently in dark mode.
	 *
	 * @return bool
	 */
	private function is_dark_mode() {
		if ( isset( $_COOKIE['theme'] ) && 'dark' === $_COOKIE['theme'] ) {
			return true;
		}
		if ( isset( $_COOKIE['theme'] ) && 'light' === $_COOKIE['theme'] ) {
			return false;
		}
		return false;
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			$this->namespace,
			'/chat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_chat' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'message' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'history' => array(
						'type'    => 'array',
						'default' => array(),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/chat/lead',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_lead' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'name'  => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'email' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
					),
					'note'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
						'default'           => '',
					),
				),
			)
		);
	}

	/**
	 * Handle chat message — the main AI endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_chat( WP_REST_Request $request ) {
		// Rate limit check.
		$ip = $this->get_client_ip();
		if ( ! $this->check_rate_limit( $ip ) ) {
			return new WP_Error(
				'rate_limit',
				__( 'Too many messages. Please try again later.', 'malachy-portfolio' ),
				array( 'status' => 429 )
			);
		}

		$message = $request->get_param( 'message' );
		$history = $request->get_param( 'history' );

		if ( empty( $message ) ) {
			return new WP_Error(
				'empty_message',
				__( 'Please enter a message.', 'malachy-portfolio' ),
				array( 'status' => 400 )
			);
		}

		// If this IP was flagged as spammy in the last 5 minutes,
		// block follow-ups without re-checking (prevents LLM drain).
		$spam_key = 'malachy_spam_' . md5( $ip );
		if ( get_transient( $spam_key ) ) {
			return new WP_REST_Response(
				array(
					'reply'  => __(
						'I specialise in email deliverability, domain security, Microsoft 365/Google Workspace setup, and WordPress/AI chatbot integration. If you have a genuine question about any of these, feel free to ask — otherwise, you can reach me through the contact form.',
						'malachy-portfolio'
					),
					'source' => 'static',
				),
				200
			);
		}

		// Spam / bot query check — don't waste tokens on nonsense.
		$spam_reply = $this->check_spam( $message );
		if ( null !== $spam_reply ) {
			// Flag this IP so follow-up messages are blocked for 5 minutes.
			set_transient( $spam_key, 1, 5 * MINUTE_IN_SECONDS );
			return new WP_REST_Response(
				array(
					'reply'  => $spam_reply,
					'source' => 'static',
				),
				200
			);
		}

		// Check static Q&A cache first — instant answers, no LLM needed.
		$static = $this->get_static_answer( $message );
		error_log( 'Malachy Chatbot: static answer check for "' . $message . '" — result: ' . ( empty( $static ) ? 'EMPTY' : 'HIT' ) );
		if ( ! empty( $static ) ) {
			return new WP_REST_Response(
				array( 'reply' => $static, 'source' => 'static' ),
				200
			);
		}

		// Build portfolio context.
		$structured = $this->get_structured_context();
		$text_context = $this->get_portfolio_context();

		// Tier 1: Follow-up check — "tell me more" after a Tier 1 hit.
		$followup = $this->get_followup_match( $message, $history );
		if ( null !== $followup ) {
			$reply = $this->compose_reply_from_match( $followup );
			if ( ! empty( $reply ) ) {
				return new WP_REST_Response(
					array(
						'reply'           => $reply,
						'source'          => 'knowledge_followup',
						'_tier1_type'     => $followup['type'],
						'_tier1_payload'  => $followup['payload'],
					),
					200
				);
			}
		}

		// Tier 1: Knowledge retrieval — score against CPTs.
		$knowledge = $this->get_knowledge_match( $message, $structured );
		if ( null !== $knowledge ) {
			$reply = $this->compose_reply_from_match( $knowledge['match'] );
			if ( ! empty( $reply ) ) {
				return new WP_REST_Response(
					array(
						'reply'           => $reply,
						'source'          => 'knowledge',
						'score'           => $knowledge['score'],
						'_tier1_type'     => $knowledge['match']['type'],
						'_tier1_payload'  => $knowledge['match']['payload'],
					),
					200
				);
			}
		}

		// Tier 2: LLM fallback.

		// Build messages array for the LLM.
		$messages = array(
			array(
				'role'    => 'system',
				'content' => $this->build_system_prompt( $text_context ),
			),
		);

		// Add conversation history (last 10 messages to stay within context window).
		$history = array_slice( $history, -10 );
		foreach ( $history as $h ) {
			if ( isset( $h['role'] ) && isset( $h['content'] ) ) {
				$messages[] = array(
					'role'    => sanitize_text_field( $h['role'] ),
					'content' => sanitize_textarea_field( $h['content'] ),
				);
			}
		}

		// Add current user message.
		$messages[] = array(
			'role'    => 'user',
			'content' => $message,
		);

		// Call opencode.ai API.
		$api_key = defined( 'OPENCODE_API_KEY' ) ? OPENCODE_API_KEY : getenv( 'OPENCODE_API_KEY' );
		if ( empty( $api_key ) ) {
			return new WP_REST_Response(
				array(
					'reply'  => __(
						'Reach me through my [contact form](/#contact) or email malachy.egbuna@imadconsulting.co.uk',
						'malachy-portfolio'
					),
					'source' => 'fallback',
				),
				200
			);
		}

		$response = $this->call_opencode_api( $messages, $api_key );

		if ( is_wp_error( $response ) ) {
			error_log( 'Malachy Chatbot: LLM fallback failed — ' . $response->get_error_message() );
			return new WP_REST_Response(
				array(
					'reply'  => __(
						'Reach me through my [contact form](/#contact) or email malachy.egbuna@imadconsulting.co.uk',
						'malachy-portfolio'
					),
					'source' => 'fallback',
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'reply' => $response,
			),
			200
		);
	}

	/**
	 * Handle lead capture.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_lead( WP_REST_Request $request ) {
		$name  = $request->get_param( 'name' );
		$email = $request->get_param( 'email' );
		$note  = $request->get_param( 'note' );

		if ( empty( $name ) || empty( $email ) ) {
			return new WP_Error(
				'missing_fields',
				__( 'Please provide your name and email.', 'malachy-portfolio' ),
				array( 'status' => 400 )
			);
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Please enter a valid email address.', 'malachy-portfolio' ),
				array( 'status' => 400 )
			);
		}

		$to      = get_option( 'malachy_contact_email', get_option( 'admin_email', 'malachy.egbuna@imadconsulting.co.uk' ) );
		$subject = sprintf(
			/* translators: %s: lead name */
			__( '[Chatbot Lead] New inquiry from %s', 'malachy-portfolio' ),
			$name
		);

		$body  = sprintf( "Name: %s\nEmail: %s\n", $name, $email );
		$body .= sprintf( "IP: %s\nDate: %s\n", $this->get_client_ip(), wp_date( 'Y-m-d H:i:s' ) );
		if ( ! empty( $note ) ) {
			$body .= sprintf( "\nNote / Conversation:\n%s", $note );
		}

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $email,
		);

		$owner_email_sent = wp_mail( $to, $subject, $body, $headers );
		if ( ! $owner_email_sent ) {
			error_log( 'Malachy Chatbot: owner email failed for lead from ' . $email );
		}

		// Owner Telegram alert — real-time best-effort notification.
		$telegram_sent = $this->send_telegram_notification( $name, $email, $note );

		// Visitor confirmation email — best-effort, does not affect response status.
		$this->send_visitor_confirmation( $name, $email, $note );

		// Durable lead log — fallback record if every delivery channel fails.
		$this->log_lead( $name, $email, $note, $owner_email_sent, $telegram_sent );

		// Success if at least one owner-facing channel worked.
		if ( $owner_email_sent || $telegram_sent ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Thank you! Malachy will be in touch soon.', 'malachy-portfolio' ),
				),
				200
			);
		}

		return new WP_Error(
			'mail_failed',
			__( 'Could not send your details. Please try again later.', 'malachy-portfolio' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Send a Telegram notification to the site owner when a new lead is captured.
	 * Best-effort — failures are logged, never thrown, never block the visitor's response.
	 *
	 * @param string $name  Lead name.
	 * @param string $email Lead email.
	 * @param string $note  Conversation transcript (may be empty).
	 * @return bool True on successful delivery, false otherwise.
	 */
	private function send_telegram_notification( $name, $email, $note ) {
		$bot_token = defined( 'TELEGRAM_BOT_TOKEN' ) ? TELEGRAM_BOT_TOKEN : getenv( 'TELEGRAM_BOT_TOKEN' );
		$chat_id   = defined( 'TELEGRAM_CHAT_ID' ) ? TELEGRAM_CHAT_ID : getenv( 'TELEGRAM_CHAT_ID' );

		if ( empty( $bot_token ) || empty( $chat_id ) ) {
			error_log( 'Malachy Chatbot: Telegram not configured (missing token/chat ID) — skipping notification.' );
			return false;
		}

		// Telegram messages cap at 4096 chars; keep the excerpt well under that.
		$excerpt = ! empty( $note ) ? mb_substr( trim( $note ), 0, 500 ) : '(no conversation transcript)';

		$text = sprintf(
			"🔔 <b>New Chatbot Lead</b>\n\n<b>Name:</b> %s\n<b>Email:</b> %s\n<b>Time:</b> %s\n<b>IP:</b> %s\n\n<b>Conversation excerpt:</b>\n%s",
			esc_html( $name ),
			esc_html( $email ),
			esc_html( wp_date( 'Y-m-d H:i:s' ) ),
			esc_html( $this->get_client_ip() ),
			esc_html( $excerpt )
		);

		$url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 10,
				'body'    => array(
					'chat_id'    => $chat_id,
					'text'       => $text,
					'parse_mode' => 'HTML',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Malachy Chatbot Telegram error: ' . $response->get_error_message() );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			error_log( sprintf( 'Malachy Chatbot Telegram HTTP %d: %s', $code, wp_remote_retrieve_body( $response ) ) );
			return false;
		}

		return true;
	}

	/**
	 * Send a confirmation email to the visitor after a successful lead capture,
	 * including a brief recap of the conversation that led to it. Best-effort —
	 * failure here does not affect the REST response's success/failure status.
	 *
	 * @param string $name  Lead name.
	 * @param string $email Lead email.
	 * @param string $note  Conversation transcript (may be empty).
	 * @return bool True on successful delivery, false otherwise.
	 */
	private function send_visitor_confirmation( $name, $email, $note ) {
		$subject = sprintf(
			/* translators: %s: lead's first name */
			__( 'Thanks for reaching out, %s!', 'malachy-portfolio' ),
			$name
		);

		$reply_to = get_option( 'malachy_contact_email', get_option( 'admin_email', 'malachy.egbuna@imadconsulting.co.uk' ) );

		$body  = sprintf( "Hi %s,\n\n", $name );
		$body .= "Thanks for reaching out through my portfolio chatbot! I've received your details and will follow up with you personally as soon as I can.\n\n";

		$recap = $this->format_conversation_recap( $note );
		if ( ! empty( $recap ) ) {
			$body .= "Here's a quick recap of what we discussed, so we're both on the same page:\n\n";
			$body .= $recap . "\n\n";
		}

		$body .= "In the meantime, feel free to reply directly to this email with any extra details about your project — it'll come straight to me.\n\n";
		$body .= "Talk soon,\nMalachy Egbuna\n" . home_url( '/' );

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $reply_to,
		);

		$sent = wp_mail( $email, $subject, $body, $headers );

		if ( ! $sent ) {
			error_log( 'Malachy Chatbot: visitor confirmation email failed to send to ' . $email );
		}

		return $sent;
	}

	/**
	 * Lightly format the raw conversation transcript for inclusion in the
	 * visitor's confirmation email. No LLM call — this is a plain-text trim,
	 * not a generated summary, to avoid adding latency/cost to lead submission.
	 *
	 * @param string $note Raw transcript from the JS lead-capture flow.
	 * @return string Formatted recap, or empty string if no transcript.
	 */
	private function format_conversation_recap( $note ) {
		if ( empty( $note ) ) {
			return '';
		}
		// Cap length generously for an email body (unlike the Telegram excerpt,
		// which is capped tighter at 500 chars for the messenger UI).
		return trim( mb_substr( trim( $note ), 0, 1500 ) );
	}

	/**
	 * Log every lead submission attempt, independent of delivery outcome.
	 * Acts as a durable fallback record if all delivery channels fail —
	 * same low-overhead pattern as log_nearmiss().
	 *
	 * @param string $name           Lead name.
	 * @param string $email          Lead email.
	 * @param string $note           Conversation transcript.
	 * @param bool   $email_sent     Whether the owner email succeeded.
	 * @param bool   $telegram_sent  Whether the Telegram alert succeeded.
	 */
	private function log_lead( $name, $email, $note, $email_sent, $telegram_sent ) {
		$log = get_option( 'malachy_chat_leads_log', array() );

		$log[] = array(
			'name'          => sanitize_text_field( $name ),
			'email'         => sanitize_email( $email ),
			'note'          => mb_substr( sanitize_textarea_field( $note ), 0, 2000 ),
			'email_sent'    => $email_sent,
			'telegram_sent' => $telegram_sent,
			'time'          => current_time( 'mysql' ),
		);

		// Cap at 200 entries — leads are lower-volume than near-miss log entries,
		// but still bounded to avoid unbounded option growth.
		if ( count( $log ) > 200 ) {
			$log = array_slice( $log, -200 );
		}

		update_option( 'malachy_chat_leads_log', $log, false ); // false = don't autoload.
	}

	/**
	 * Get portfolio context — cached via transients.
	 *
	 * @return string Portfolio content as a text block.
	 */
	private function get_portfolio_context() {
		$cached = get_transient( $this->context_transient );
		if ( false !== $cached ) {
			return $cached;
		}

		$context = '';

		// --- Skills ---
		$skills = new WP_Query(
			array(
				'post_type'      => 'skill',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		if ( $skills->have_posts() ) {
			$context .= "SKILLS:\n";
			while ( $skills->have_posts() ) {
				$skills->the_post();
				$title = get_the_title();
				$desc  = get_the_excerpt();
				$level = get_post_meta( get_the_ID(), '_skill_level', true );
				$context .= "- {$title}";
				if ( $level ) {
					$context .= " (Level {$level}/5)";
				}
				if ( $desc ) {
					$context .= ": {$desc}";
				}
				$context .= "\n";
			}
			wp_reset_postdata();
			$context .= "\n";
		}

		// --- Projects ---
		$projects = new WP_Query(
			array(
				'post_type'      => 'project',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		if ( $projects->have_posts() ) {
			$context .= "PROJECTS:\n";
			while ( $projects->have_posts() ) {
				$projects->the_post();
				$title = get_the_title();
				$desc  = get_the_excerpt();
				$tag   = get_post_meta( get_the_ID(), '_project_tag', true );
				$tech  = get_post_meta( get_the_ID(), '_project_tech', true );
				$url   = get_post_meta( get_the_ID(), '_project_url', true );

				$context .= "- {$title}";
				if ( $tag ) {
					$context .= " [{$tag}]";
				}
				if ( $desc ) {
					$context .= ": {$desc}";
				}
				if ( is_array( $tech ) && ! empty( $tech ) ) {
					$context .= " Technologies: " . implode( ', ', $tech ) . '.';
				}
				if ( $url ) {
					$context .= " URL: {$url}";
				}
				$context .= "\n";
			}
			wp_reset_postdata();
			$context .= "\n";
		}

		// --- Experience ---
		$experience = new WP_Query(
			array(
				'post_type'      => 'experience',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		if ( $experience->have_posts() ) {
			$context .= "EXPERIENCE:\n";
			while ( $experience->have_posts() ) {
				$experience->the_post();
				$title = get_the_title();
				$desc  = get_the_excerpt();
				$org   = get_post_meta( get_the_ID(), '_exp_org', true );
				$year  = get_post_meta( get_the_ID(), '_exp_year', true );

				$context .= "- {$title}";
				if ( $org ) {
					$context .= " at {$org}";
				}
				if ( $year ) {
					$context .= " ({$year})";
				}
				if ( $desc ) {
					$context .= ": {$desc}";
				}
				$context .= "\n";
			}
			wp_reset_postdata();
			$context .= "\n";
		}

		// --- Blog Posts (latest 5) ---
		$posts = new WP_Query(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 5,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		if ( $posts->have_posts() ) {
			$context .= "RECENT BLOG POSTS:\n";
			while ( $posts->have_posts() ) {
				$posts->the_post();
				$title = get_the_title();
				$desc  = get_the_excerpt();
				$context .= "- {$title}";
				if ( $desc ) {
					$context .= ": {$desc}";
				}
				$context .= "\n";
			}
			wp_reset_postdata();
			$context .= "\n";
		}

		// --- About / Hero Info ---
		$context .= "ABOUT:\n";
		$context .= "Malachy Egbuna is a QA Engineer, SysAdmin & IT Support Specialist based in the UK.\n";
		$context .= "He specializes in test automation, Linux server administration, Docker containerization, and WordPress development.\n";
		$context .= "He is available for select engagements and can be contacted via the portfolio contact form.\n";

		// Cache it.
		set_transient( $this->context_transient, $context, $this->context_ttl );

		return $context;
	}

	/**
	 * Build the system prompt for the LLM.
	 *
	 * @param string $context Portfolio context.
	 * @return string System prompt.
	 */
	private function build_system_prompt( $context ) {
		$prompt = "You are Malachy Egbuna, a QA Engineer, SysAdmin, and IT Support Specialist. You are speaking directly to a visitor on your portfolio website.\n\n";
		$prompt .= "Your role is to help visitors learn about your skills, experience, and projects. Answer questions accurately using the portfolio content provided below.\n\n";
		$prompt .= "Voice & Personality:\n";
		$prompt .= "- ALWAYS speak in first person: \"I\", \"my\", \"me\" — NEVER third person (no \"Malachy is\", \"he\", \"his\").\n";
		$prompt .= "- Be friendly, concise, and professional.\n";
		$prompt .= "- Sound confident but approachable — like a real person chatting.\n\n";
		$prompt .= "Guidelines:\n";
		$prompt .= "- Use the portfolio context to answer factually about your skills, experience, and projects.\n";
		$prompt .= "- If asked about a specific showcase or demo that isn't in your portfolio, say something like: \"I'd love to walk you through that — let me connect you with a quick form so we can discuss your project.\" Then prompt them for their name and email.\n";
		$prompt .= "- If the visitor expresses interest in hiring, building something together, or needs help — ask for their name and email so you can follow up.\n";
		$prompt .= "- If asked about services you offer (chatbots, automation, websites, email deliverability), describe what you can do based on your skills and experience.\n";
		$prompt .= "- Do not make up facts not present in the context.\n";
		$prompt .= "- Keep responses to 2-3 short paragraphs when possible.\n\n";
		$prompt .= "ABOUT YOU:\n";
		$prompt .= "I am a Microsoft 365 email deliverability and domain security specialist based in the UK. I help small businesses fix business emails going to spam, set up Microsoft 365 and Google Workspace business email on custom domains, migrate email and websites between hosts, harden domain and DNS records against spoofing and fraud, and modernize WordPress sites for AI chatbot integration. I also have a background in QA automation, Linux system administration, Docker, and WordPress development. I am available for select engagements and can be contacted via the contact form.\n\n";
		$prompt .= "PORTFOLIO CONTEXT:\n";
		$prompt .= $context;

		return $prompt;
	}

	/**
	 * Static Q&A cache — instant answers for common questions.
	 * Returns a string answer if matched, empty string if not.
	 *
	 * @param string $message User message.
	 * @return string Static answer or empty string.
	 */
	private function get_static_answer( $message ) {
		$lower = strtolower( trim( $message ) );
		$word_count = str_word_count( $lower );

		// Greetings — only if message is short (pure greeting, not a question).
		if ( $word_count <= 4 && preg_match( '/^(hi|hello|hey|good morning|good afternoon|good evening|howdy|yo|sup|greetings)\b/i', $lower ) ) {
			return "Hello! I'm Malachy — QA Engineer, SysAdmin & developer. How can I help you today?";
		}

		// Who are you / about.
		if ( preg_match( '/(who are you|tell me about yourself|what do you do|what is your role|introduce yourself)/i', $lower ) ) {
			return "I'm **Malachy Egbuna**, a Microsoft 365 email deliverability and domain security specialist. I help small businesses stop legitimate emails going to spam, set up business email on a custom domain, migrate between providers, and harden DNS records against spoofing and fraud. I'm based in the UK and work with clients remotely.";
		}

		// Chatbot / AI integration — only when asking about BUILDING one.
		if ( preg_match( '/(build.*chatbot|create.*chatbot|make.*chatbot|need.*chatbot|want.*chatbot|chatbot.*integrat|ai.*integrat|build.*ai|create.*ai)/i', $lower ) ) {
			return "I can absolutely help with AI chatbot integration. I have experience with **LLM & Agentic Coding** — prompt engineering, context management, and multi-agent workflows. I can build custom chatbots using WordPress, Python, or Node.js backends. Share your **name** and **email** and I'll reach out to discuss your project.";
		}

		// Link to portfolio / website.
		if ( preg_match( '/(link to your|url of your|site address|where can i see|visit your)/i', $lower ) ) {
			return "You can view my portfolio at **" . home_url( '/' ) . "** — that's where you're chatting with me right now! Want to know about a specific project or skill?";
		}

		// Projects — more specific, checked before Skills.
		if ( preg_match( '/(what have you built|work sample|case study|showcase|tell me about your projects|list your projects)/i', $lower ) ) {
			return "Here are some projects I've worked on:\n\n**1. Automated QA Suite**\nPlaywright + TypeScript + Docker + GitHub Actions — full E2E test automation framework.\n\n**2. Infrastructure Console**\nLinux + Docker + Python + Nginx — server management dashboard.\n\n**3. WordPress Theme Framework**\nWordPress + PHP + Tailwind + Vite — custom theme without page builders.\n\nWant details on any of these?";
		}

		// Experience — more specific, checked before Skills.
		if ( preg_match( '/(experience|work history|career|job|role|employment)/i', $lower ) ) {
			return "I've run IMaD Consulting since 2020, covering email/DNS security, web support, and AI-assisted development. Before that, I spent close to two decades in QA and IT support roles across UK finance, telecoms, and insurance — Planixs, British Telecoms, Admiral Insurance, and others.";
		}

		// What I offer / services overview — highlighted offers first (HO-015).
		if ( preg_match( '/(what.*offer|all.*offer|services.*offer|offer.*services|list your services|your services|service catalog|all that you offer)/i', $lower ) ) {
			return "I offer focused, fixed-price help across three areas. My most requested services are marked with a **Most Requested** badge on the site:

**Email Deliverability & Security**
- Fix business emails going to spam — SPF, DKIM, DMARC audit & fix *(Most Requested)*
- Audit and report on your business email security & deliverability *(Most Requested)*
- Lock down your domain & DNS against spoofing, hijacking and email fraud
- Ongoing email & DNS health monitoring

**Microsoft 365, Google Workspace & Migrations**
- Set up Microsoft 365 business email with your custom domain *(Most Requested)*
- Migrate business email to Microsoft 365 or Google Workspace *(Most Requested)*
- Migrate your website and business email to a new host *(Most Requested)*

**AI & WordPress Modernization**
- Migrate and rebuild a WordPress site onto a modern stack for AI chatbot integration *(Most Requested)*
- Assess your WordPress site for AI chatbot integration

Want details on any of these? Or share your **name** and **email** and I'll reach out.";
		}

		// Skills — detailed list. Checked after Projects/Experience to avoid keyword collisions.
		if ( preg_match( '/(skill|what can you|what do you know|capabilities|expertise|services|technologies|stack)/i', $lower ) ) {
			return "Here's a quick overview of what I do:\n\n**Email Deliverability & Domain Security**\n- SPF, DKIM, DMARC setup and troubleshooting\n- Microsoft 365 & Google Workspace email configuration\n- DNS hardening against spoofing and hijacking\n- Email migration planning and cutover\n\n**Migrations & Hosting**\n- Website and email migration between hosts\n- DNS, SSL, and routing handover\n- Post-migration testing and verification\n\n**WordPress & AI Integration**\n- Custom WordPress themes without page builders\n- WordPress chatbot-readiness assessment\n- Modern stack rebuilds with AI chatbot integration\n\n**QA & Infrastructure**\n- Test automation (Playwright, Cypress, Pytest)\n- Linux server administration, Docker, CI/CD\n- Python and shell scripting\n\nWant to know more about any specific area?";
		}

		// Contact / hire / availability — tightened, no bare "email".
		if ( preg_match( '/(hire me|hire you|contact you|available for|booking|engagement|work together|reach you|your email address|how to contact|get in touch)/i', $lower ) ) {
			return "I'm available for select engagements — QA consulting, WordPress development, infrastructure work, and AI integration projects. Share your **name** and **email** and I'll follow up personally.";
		}

		// Website / web development.
		if ( preg_match( '/(build.*website|do (you|u) build|create.*website|make.*website|develop.*website|web development|web design|build.*site)/i', $lower ) ) {
			return "Yes — I build custom websites, primarily with WordPress. I create clean, maintainable themes without page builders, using native PHP, HTML, CSS, and JavaScript. I also handle website migrations, performance tuning, and AI chatbot integration. Want to discuss a project? Share your **name** and **email** and I'll reach out.";
		}

		// WordPress.
		if ( preg_match( '/(wordpress|wp|theme|plugin|cms)/i', $lower ) ) {
			return "I'm experienced with WordPress development — custom themes, custom post types, meta boxes, REST API integration, and performance tuning. I build without page builders for clean, maintainable code. Want to discuss a WordPress project?";
		}

		// Email deliverability — now includes "email migration(s)".
		if ( preg_match( '/(email deliverability|email migration|email migrations|spam|dkim|spf|dmarc|email authentication|email not delivered|email to spam)/i', $lower ) ) {
			return "I can help diagnose and fix email deliverability issues — SPF, DKIM, DMARC configuration, DNS record setup, and deliverability testing across Google Workspace and Microsoft 365. Share your **name** and **email** and I'll reach out with a detailed assessment.";
		}

		// Website / email / domain migration (Offer 3, Offer 4).
		if ( preg_match( '/(migrat|transfer).*(website|hosting|domain|email|google workspace|office 365|microsoft 365|inmotion|cpanel|hostgator|godaddy)/i', $lower ) ) {
			return "I can handle the full migration from InMotion to Google Workspace:\n\n- **Website** — migrate with minimal downtime\n- **Domain** — transfer safely\n- **Email** — move all mailboxes with DNS, SPF, DKIM\n- **SSL & DNS** — update records, configure certificates\n- **Testing** — verify routing, blacklist check, post-migration\n\nGoal: seamless transition, business stays online. Share your **name** and **email** and I'll discuss your specific setup.";
		}

		// Business email setup from scratch (Offer 2).
		if ( preg_match( '/(set.?up|configure).*(email|google workspace|office 365|microsoft 365|business email|professional email)/i', $lower ) ) {
			return "I can set up business email on Google Workspace or Microsoft 365:\n\n- Domain verification & DNS records\n- SPF, DKIM, DMARC authentication\n- User accounts & mailbox configuration\n- Delivery testing & verification\n\nYou'll have a professional, secure email environment ready to go. Share your **name** and **email** and I'll walk you through it.";
		}

		// Domain security — hijacking / spoofing / fraud prevention (Offer 6).
		if ( preg_match( '/(domain.*(secur|hijack|spoof|lock|protect|fraud)|spoofing|invoice fraud)/i', $lower ) ) {
			return "I can harden your domain against hijacking and spoofing:\n\n- Registrar-lock verification\n- DMARC policy enforcement (monitor → reject)\n- Typosquat/lookalike domain check\n- WHOIS privacy & security review\n\nShare your **name** and **email** and I'll review your current setup.";
		}

		// Email audit / health check (Offer 5).
		if ( preg_match( '/(email.*(audit|health|check|report|diagnos|inspect)|audit.*email)/i', $lower ) ) {
			return "I can audit your email infrastructure:\n\n- SPF, DKIM, DMARC authentication check\n- DNS health & routing review\n- Blacklist & sender reputation scan\n- Detailed report with prioritized fixes\n\nShare your **name** and **email** and I'll get started.";
		}

		// Automation.
		if ( preg_match( '/(automate|automation|workflow|ci\/cd|pipeline|devops)/i', $lower ) ) {
			return "I build automation workflows using Python, shell scripting, and CI/CD pipelines (GitHub Actions, Docker). I can automate testing, deployments, server management, and more. What would you like to automate?";
		}

		// Pricing / cost.
		if ( preg_match( '/(price|cost|rate|how much|pricing|budget|quote)/i', $lower ) ) {
			return "Pricing depends on the scope and complexity of the project. I typically quote after a quick discovery call to understand your needs. Share your **name** and **email** and I'll reach out with a tailored quote.";
		}

		// Reviews / testimonials — dynamically pulled from CPT.
		if ( preg_match( '/(review|testimonial|feedback|client.*say|what.*client|recommendation)/i', $lower ) ) {
			$context = $this->get_structured_context();
			$testimonials = $context['testimonials'] ?? array();
			if ( ! empty( $testimonials ) ) {
				$lines = array();
				foreach ( $testimonials as $t ) {
					$org = ! empty( $t['org'] ) ? ' (' . $t['org'] . ')' : '';
					$lines[] = sprintf( "**%s%s:** \"%s\"", $t['title'], $org, $t['content'] );
				}
				return "Here's what clients have said about working with me:\n\n"
					. implode( "\n\n", $lines )
					. "\n\nWant to see more or discuss a project?";
			}
			return ''; // fall through to Tier 1/2 if no testimonials exist
		}

		// Thank you.
		if ( preg_match( '/^(thanks|thank you|cheers|ta|appreciate)/i', $lower ) ) {
			return "You're welcome! Is there anything else I can help with?";
		}

		// No match — return empty to trigger LLM.
		return '';
	}

	// ─── Tier 1: Knowledge Retrieval (HO-003) ───────────────────────────────

	/**
	 * Get structured portfolio context as an array.
	 * Used by both Tier 1 knowledge index and Tier 2 LLM prompt.
	 *
	 * @return array Structured context with skills, projects, experience, posts.
	 */
	private function get_structured_context() {
		$cached = get_transient( 'malachy_chat_structured_context' );
		if ( false !== $cached ) {
			return $cached;
		}

		$context = array(
			'skills'      => array(),
			'projects'    => array(),
			'experience'  => array(),
			'posts'       => array(),
			'testimonials' => array(),
		);

		// Skills.
		$skills = new WP_Query( array(
			'post_type'      => 'skill',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
		if ( $skills->have_posts() ) {
			while ( $skills->have_posts() ) {
				$skills->the_post();
				$context['skills'][] = array(
					'title'       => html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ),
					'description' => html_entity_decode( get_the_excerpt(), ENT_QUOTES, 'UTF-8' ),
					'level'       => get_post_meta( get_the_ID(), '_skill_level', true ),
					'icon'        => get_post_meta( get_the_ID(), '_skill_icon', true ),
				);
			}
			wp_reset_postdata();
		}

		// Projects.
		$projects = new WP_Query( array(
			'post_type'      => 'project',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
		if ( $projects->have_posts() ) {
			while ( $projects->have_posts() ) {
				$projects->the_post();
				$context['projects'][] = array(
					'title'       => html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ),
					'description' => html_entity_decode( get_the_excerpt(), ENT_QUOTES, 'UTF-8' ),
					'tag'         => html_entity_decode( get_post_meta( get_the_ID(), '_project_tag', true ), ENT_QUOTES, 'UTF-8' ),
					'tech_stack'  => get_post_meta( get_the_ID(), '_project_tech', true ),
					'url'         => get_post_meta( get_the_ID(), '_project_url', true ),
					'github'      => get_post_meta( get_the_ID(), '_project_github', true ),
				);
			}
			wp_reset_postdata();
		}

		// Experience.
		$experience = new WP_Query( array(
			'post_type'      => 'experience',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
		if ( $experience->have_posts() ) {
			while ( $experience->have_posts() ) {
				$experience->the_post();
				$context['experience'][] = array(
					'title'       => html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ),
					'description' => html_entity_decode( get_the_excerpt(), ENT_QUOTES, 'UTF-8' ),
					'company'     => html_entity_decode( get_post_meta( get_the_ID(), '_exp_org', true ), ENT_QUOTES, 'UTF-8' ),
					'duration'    => get_post_meta( get_the_ID(), '_exp_year', true ),
				);
			}
			wp_reset_postdata();
		}

		// Posts.
		$posts = new WP_Query( array(
			'post_type'      => 'post',
			'posts_per_page' => 5,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		if ( $posts->have_posts() ) {
			while ( $posts->have_posts() ) {
				$posts->the_post();
				$context['posts'][] = array(
					'title'   => html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ),
					'excerpt' => html_entity_decode( get_the_excerpt(), ENT_QUOTES, 'UTF-8' ),
					'date'    => get_the_date(),
				);
			}
			wp_reset_postdata();
		}

		// Testimonials.
		$testimonials = new WP_Query( array(
			'post_type'      => 'testimonial',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		if ( $testimonials->have_posts() ) {
			while ( $testimonials->have_posts() ) {
				$testimonials->the_post();
				$context['testimonials'][] = array(
					'title'       => html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ),
					'content'     => html_entity_decode( get_the_content(), ENT_QUOTES, 'UTF-8' ),
					'role'        => get_post_meta( get_the_ID(), '_testimonial_role', true ),
					'org'         => html_entity_decode( get_post_meta( get_the_ID(), '_testimonial_org', true ), ENT_QUOTES, 'UTF-8' ),
				);
			}
			wp_reset_postdata();
		}

		set_transient( 'malachy_chat_structured_context', $context, HOUR_IN_SECONDS );

		return $context;
	}

	/**
	 * Tokenize text — lowercase, strip punctuation, split, drop stopwords,
	 * expand through synonym groups for domain-specific matching.
	 *
	 * @param string $text Input text.
	 * @return array Tokenized array.
	 */
	private function tokenize( $text ) {
		$text = strtolower( $text );
		$text = preg_replace( '/[^\p{L}\p{N}\s\/]/u', ' ', $text );
		$raw_tokens = preg_split( '/[\s\/]+/', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );

		$stopwords = array( 'a', 'an', 'the', 'is', 'are', 'do', 'does', 'you', 'your', 'have',
			'has', 'i', 'me', 'my', 'what', 'which', 'tell', 'about', 'can', 'could', 'would',
			'of', 'on', 'in', 'to', 'for', 'with', 'and', 'or', 'it', 'that', 'this' );

		$tokens = array_values( array_diff( $raw_tokens, $stopwords ) );

		return $this->expand_synonyms( $tokens );
	}

	/**
	 * Expand tokens through domain synonym groups.
	 *
	 * @param array $tokens Input tokens.
	 * @return array Expanded tokens.
	 */
	private function expand_synonyms( $tokens ) {
		$synonym_groups = array(
			array( 'dns', 'spf', 'dkim', 'dmarc', 'email', 'deliverability', 'spam', 'authentication' ),
			array( 'ci', 'cd', 'cicd', 'pipeline', 'automation', 'automate', 'devops', 'workflow' ),
			array( 'wordpress', 'wp', 'theme', 'plugin', 'cms' ),
			array( 'qa', 'test', 'testing', 'quality', 'assurance', 'bug', 'regression' ),
			array( 'infra', 'infrastructure', 'sysadmin', 'server', 'vps', 'docker', 'container', 'containerization' ),
			array( 'project', 'built', 'build', 'shipped', 'sample', 'showcase', 'case', 'study', 'portfolio' ),
			array( 'llm', 'ai', 'chatbot', 'chat', 'bot', 'assistant', 'artificial', 'intelligence' ),
		);

		$expanded = $tokens;
		foreach ( $synonym_groups as $group ) {
			if ( array_intersect( $tokens, $group ) ) {
				$expanded = array_merge( $expanded, $group );
			}
		}
		return array_unique( $expanded );
	}

	/**
	 * Build a scoreable knowledge index from portfolio context.
	 * Cached alongside malachy_chat_context under its own key.
	 *
	 * @param array $context Structured context array.
	 * @return array Index of candidates with keywords.
	 */
	private function build_knowledge_index( $context ) {
		$cached = get_transient( 'malachy_chat_knowledge_index' );
		if ( false !== $cached ) {
			return $cached;
		}

		$index = array();

		// Skills.
		foreach ( $context['skills'] ?? array() as $skill ) {
			$blob = $skill['title'] . ' ' . $skill['description'];
			$index[] = array(
				'type'     => 'skill',
				'payload'  => $skill,
				'keywords' => $this->tokenize( $blob ),
			);
		}

		// Projects.
		foreach ( $context['projects'] ?? array() as $project ) {
			$tech = implode( ' ', $project['tech_stack'] ?? array() );
			$blob = $project['title'] . ' ' . $project['description'] . ' ' . $tech;
			$index[] = array(
				'type'     => 'project',
				'payload'  => $project,
				'keywords' => $this->tokenize( $blob ),
			);
		}

		// Experience.
		foreach ( $context['experience'] ?? array() as $exp ) {
			$blob = $exp['title'] . ' ' . ( $exp['company'] ?? '' ) . ' ' . ( $exp['description'] ?? '' );
			$index[] = array(
				'type'     => 'experience',
				'payload'  => $exp,
				'keywords' => $this->tokenize( $blob ),
			);
		}

		// Posts.
		foreach ( $context['posts'] ?? array() as $post ) {
			$blob = $post['title'] . ' ' . $post['excerpt'];
			$index[] = array(
				'type'     => 'post',
				'payload'  => $post,
				'keywords' => $this->tokenize( $blob ),
			);
		}

		// Testimonials.
		foreach ( $context['testimonials'] ?? array() as $testimonial ) {
			$blob = $testimonial['title'] . ' ' . $testimonial['content'] . ' ' . ( $testimonial['org'] ?? '' );
			$index[] = array(
				'type'     => 'testimonial',
				'payload'  => $testimonial,
				'keywords' => $this->tokenize( $blob ),
			);
		}

		set_transient( 'malachy_chat_knowledge_index', $index, HOUR_IN_SECONDS );

		return $index;
	}

	/**
	 * Score incoming message against knowledge index.
	 * Returns best match + score, or null if below threshold.
	 *
	 * @param string $message User message.
	 * @param array  $context Structured context array.
	 * @return array|null Best match with score, or null.
	 */
	private function get_knowledge_match( $message, $context ) {
		$tokens = $this->tokenize( $message );
		if ( empty( $tokens ) ) {
			return null;
		}

		$index = $this->build_knowledge_index( $context );

		$best       = null;
		$best_score = 0.0;

		foreach ( $index as $candidate ) {
			if ( empty( $candidate['keywords'] ) ) {
				continue;
			}
			$overlap = count( array_intersect( $tokens, $candidate['keywords'] ) );
			if ( 0 === $overlap ) {
				continue;
			}
			$denominator = min( count( $tokens ), count( $candidate['keywords'] ) );
			$score       = $overlap / max( $denominator, 1 );

			if ( $score > $best_score ) {
				$best_score = $score;
				$best       = $candidate;
			}
		}

		$threshold = apply_filters( 'malachy_chat_tier1_threshold', 0.35 );

		if ( null !== $best ) {
			$this->log_nearmiss( $message, $best['type'], $best_score, $best_score >= $threshold );
		}

		if ( null !== $best && $best_score >= $threshold ) {
			return array( 'match' => $best, 'score' => $best_score );
		}

		return null;
	}

	/**
	 * Compose a first-person reply from a Tier 1 match.
	 *
	 * @param array $candidate Matched candidate.
	 * @return string Reply text.
	 */
	private function compose_reply_from_match( $candidate ) {
		$item = $candidate['payload'];

		switch ( $candidate['type'] ) {
			case 'skill':
				return sprintf(
					"Yes — **%s** is one of my core skills. %s",
					$item['title'],
					$item['description']
				);

			case 'project':
				$tech = ! empty( $item['tech_stack'] )
					? ' Built with ' . implode( ', ', $item['tech_stack'] ) . '.'
					: '';
				return sprintf(
					"One example is **%s**: %s%s",
					$item['title'],
					$item['description'],
					$tech
				);

			case 'experience':
				return sprintf(
					"That's from my time as **%s** at %s (%s). %s",
					$item['title'],
					$item['company'] ?? '',
					$item['duration'] ?? '',
					$item['description'] ?? ''
				);

			case 'post':
				return sprintf(
					"I've actually written about this — **%s**. %s",
					$item['title'],
					$item['excerpt']
				);

			case 'testimonial':
				$org = ! empty( $item['org'] ) ? ' — ' . $item['org'] : '';
				return sprintf(
					"Here's what a client said about working with me: \"%s\"%s",
					$item['content'],
					$org
				);

			default:
				return '';
		}
	}

	/**
	 * Detect follow-up messages ("tell me more", "why", etc.)
	 * and resolve against previous Tier 1 context.
	 *
	 * @param string $message User message.
	 * @param array  $history Conversation history.
	 * @return array|null Previous Tier 1 match, or null.
	 */
	private function get_followup_match( $message, $history ) {
		$followup_patterns = array( 'tell me more', 'more about that', 'go on', 'and that', 'why', 'how so', 'elaborate' );
		$normalized        = strtolower( trim( $message ) );

		$is_followup = false;
		foreach ( $followup_patterns as $pattern ) {
			if ( str_contains( $normalized, $pattern ) ) {
				$is_followup = true;
				break;
			}
		}
		if ( ! $is_followup || empty( $history ) ) {
			return null;
		}

		$last_source  = end( $history )['_tier1_type'] ?? null;
		$last_payload = end( $history )['_tier1_payload'] ?? null;
		if ( ! $last_source || ! $last_payload ) {
			return null;
		}

		return array( 'type' => $last_source, 'payload' => $last_payload );
	}

	/**
	 * Log Tier 1 attempts for tuning.
	 *
	 * @param string $message User message.
	 * @param string $type    Candidate type.
	 * @param float  $score   Match score.
	 * @param bool   $hit     Whether it cleared threshold.
	 */
	private function log_nearmiss( $message, $type, $score, $hit ) {
		$log = get_option( 'malachy_chat_nearmiss_log', array() );

		$log[] = array(
			'message' => mb_substr( sanitize_text_field( $message ), 0, 200 ),
			'type'    => $type,
			'score'   => round( $score, 3 ),
			'hit'     => $hit,
			'time'    => current_time( 'mysql' ),
		);

		if ( count( $log ) > 500 ) {
			$log = array_slice( $log, -500 );
		}

		update_option( 'malachy_chat_nearmiss_log', $log, false );
	}

	// ─── End Tier 1 ─────────────────────────────────────────────────────────

	/**
	 * Call the opencode.ai API.
	 *
	 * @param array  $messages Array of message objects.
	 * @param string $api_key  API key.
	 * @return string|WP_Error Response content or error.
	 */
	private function call_opencode_api( $messages, $api_key ) {
		$endpoint = 'https://opencode.ai/zen/v1/chat/completions';

		$body = wp_json_encode(
			array(
				'model'       => 'deepseek-v4-flash-free',
				'messages'    => $messages,
			'max_tokens'  => 1024,
			'temperature' => 0.7,
			)
		);

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => $body,
			'timeout' => 30,
		);

		$response = wp_remote_post( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'Malachy Chatbot LLM connection error: ' . $response->get_error_message() );
			return new WP_Error(
				'api_error',
				__( 'Could not connect to AI service. Please try again later.', 'malachy-portfolio' )
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			error_log( sprintf( 'Malachy Chatbot LLM HTTP %d: %s', $code, $body ) );
			$data = json_decode( $body, true );
			$error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'AI service returned an error.', 'malachy-portfolio' );

			// Detect auth/credit errors for frontend fallback.
			if ( in_array( $code, array( 401, 403, 402 ), true ) ) {
				return new WP_Error( 'credit_exhausted', $error_msg, array( 'status' => $code ) );
			}
			if ( $code === 429 ) {
				return new WP_Error( 'rate_limited', $error_msg, array( 'status' => 429 ) );
			}
			return new WP_Error( 'api_error', $error_msg, array( 'status' => $code ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['choices'][0]['message']['content'] ) ) {
			error_log( 'Malachy Chatbot LLM returned empty content. Raw response: ' . $body );
			return new WP_Error(
				'api_error',
				__( 'AI service returned an empty response. Please try again.', 'malachy-portfolio' )
			);
		}

		return sanitize_textarea_field( $data['choices'][0]['message']['content'] );
	}

	/**
	 * Detect low-effort / spam / bot queries to avoid wasting LLM tokens.
	 * Returns a canned "can't help" response instead of reaching knowledge
	 * retrieval or the LLM API.
	 *
	 * @param string $message User message.
	 * @return string|null Canned reply if spam, null if legitimate.
	 */
	private function check_spam( $message ) {
		$lower = strtolower( trim( $message ) );
		$len   = strlen( $lower );

		// Empty or single-char messages — not worth responding to.
		if ( $len <= 1 ) {
			return __( 'Hi — if you have a tech question, feel free to ask. Otherwise, you can reach me through the contact form.', 'malachy-portfolio' );
		}

		// Gibberish: mostly symbols/digits, very few real letters.
		$letter_count = strlen( preg_replace( '/[^a-z]/', '', $lower ) );
		if ( $len > 4 && ( $letter_count / $len ) < 0.3 ) {
			return __(
				"I specialise in email deliverability, domain security, Microsoft 365/Google Workspace setup, and WordPress/AI chatbot integration. If you have a genuine question about any of these, feel free to ask — otherwise, you can reach me through the contact form.",
				'malachy-portfolio'
			);
		}

		// Single character repeated (noise / keyboard mashing).
		if ( preg_match( '/^(.)\1{4,}$/', $lower ) ) {
			return __(
				"I specialise in email deliverability, domain security, Microsoft 365/Google Workspace setup, and WordPress/AI chatbot integration. If you have a genuine question about any of these, feel free to ask — otherwise, you can reach me through the contact form.",
				'malachy-portfolio'
			);
		}

		// Self-identifying bot / test patterns (start of message only).
		if ( preg_match( '/^(i am a bot|i.m a bot|this is (a |just )?test\b|just test(?:ing)?\b)/i', $lower ) ) {
			return __(
				"I specialise in email deliverability, domain security, Microsoft 365/Google Workspace setup, and WordPress/AI chatbot integration — I don't handle product sales or shopping queries. If you have a genuine question, feel free to ask. Otherwise, you can reach me through the contact form.",
				'malachy-portfolio'
			);
		}

		// Known unrelated / shopping / spam keywords.
		$unrelated = array(
			'candy', 'sweets', 'chocolate', 'crypto', 'bitcoin', 'nft',
			'pizza', 'food', 'drink', 'buy.*email', 'sell.*email',
			'candy.*email', 'email.*candy',
		);

		foreach ( $unrelated as $pattern ) {
			if ( preg_match( '/' . $pattern . '/i', $lower ) ) {
				return __(
					"I specialise in email infrastructure, WordPress development, QA automation, and system administration — I don't handle product sales or shopping queries. If you have a genuine tech question, feel free to ask. Otherwise, you can reach me through the contact form.",
					'malachy-portfolio'
				);
			}
		}

		return null;
	}

	/**
	 * Check rate limit for an IP.
	 *
	 * @param string $ip Client IP.
	 * @return bool True if within limit, false if exceeded.
	 */
	private function check_rate_limit( $ip ) {
		$key    = 'malachy_chat_rl_' . md5( $ip );
		$count  = (int) get_transient( $key );
		if ( $count >= $this->rate_limit ) {
			return false;
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string IP address.
	 */
	private function get_client_ip() {
		$keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP );
				if ( $ip ) {
					return $ip;
				}
			}
		}
		return '127.0.0.1';
	}

	/**
	 * Clear portfolio context cache when a relevant post is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 */
	public function clear_context_cache( $post_id, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$post_types = array( 'project', 'experience', 'skill', 'post' );
		if ( in_array( $post->post_type, $post_types, true ) ) {
			delete_transient( $this->context_transient );
			delete_transient( 'malachy_chat_knowledge_index' );
			delete_transient( 'malachy_chat_structured_context' );
		}
	}
}

// Initialize.
new Malachy_AI_Chatbot();
