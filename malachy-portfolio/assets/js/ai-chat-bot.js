/**
 * AI Chat Bot Widget — Vanilla JavaScript
 *
 * Three-stage progressive disclosure: launcher → input bar → panel.
 * Exit-intent modal overlay. REST API integration. Lead capture.
 * localStorage persistence. Keyboard + accessibility support.
 *
 * @package Malachy_Portfolio
 * @since 1.2.0
 */

(function () {
	'use strict';

	/**
	 * Chatbot Widget Class.
	 */
	class AIChatbotWidget {
		constructor() {
			this.config = window.malachyChatbotConfig || {};
			this.apiUrl = this.config.apiUrl || '';
			this.leadUrl = this.config.leadUrl || '';
			this.nonce = this.config.nonce || '';
			this.placeholder = this.config.placeholder || 'Ask about your portfolio...';
			this.assistantTitle = this.config.assistant || 'Assistant';
			this.welcomeMessage = this.config.welcome || "Hi! I'm Malachy's AI assistant. Ask me about his skills, projects, or experience.";
			this.suggestions = this.config.suggestions || [];
			this.contactUrl = this.config.contactUrl || '/#contact';
			this.darkMode = this.config.darkMode || false;

			// State.
			this.stage = 'launcher'; // launcher | input | panel
			this.messages = [];
			this.draftText = '';
			this.hasInteracted = false;
			this.isLoading = false;
			this.exitModalShown = false;
			this.pageLoadTime = Date.now();
			this.hasScrolled = false;
			this.mouseInViewport = false;
			this.mouseEnterTime = 0;

			// DOM refs.
			this.root = null;
			this.launcher = null;
			this.inputBar = null;
			this.panel = null;
			this.messagesArea = null;
			this.exitModal = null;

			// Bind methods.
			this.handleLauncherClick = this.handleLauncherClick.bind(this);
			this.handleInputKeydown = this.handleInputKeydown.bind(this);
			this.handleSendClick = this.handleSendClick.bind(this);
			this.handlePanelClose = this.handlePanelClose.bind(this);
			this.handleClickOutside = this.handleClickOutside.bind(this);
			this.handleEscape = this.handleEscape.bind(this);
			this.handleExitIntent = this.handleExitIntent.bind(this);
			this.handleModalClose = this.handleModalClose.bind(this);
			this.handleSuggestionClick = this.handleSuggestionClick.bind(this);

			// Init.
			this.buildDOM();
			this.attachEvents();
			this.detectDarkMode();
		}

		/**
		 * Detect dark mode from <html> class or system preference.
		 */
		detectDarkMode() {
			const html = document.documentElement;
			if (html.classList.contains('dark')) {
				this.darkMode = true;
			} else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
				this.darkMode = true;
			}
		}

		/**
		 * Restore chat session from localStorage.
		 */
		/**
		 * Build all DOM elements.
		 */
		buildDOM() {
			// Root container.
			this.root = document.createElement('div');
			this.root.className = 'ai-chatbot';
			this.root.setAttribute('role', 'region');
			this.root.setAttribute('aria-label', this.assistantTitle);

			// 1. Launcher button.
			this.launcher = document.createElement('button');
			this.launcher.className = 'ai-chatbot-launcher';
			this.launcher.setAttribute('aria-label', 'Open chat assistant');
			this.launcher.setAttribute('title', 'Open chat assistant');
			// Blurred pulsing background (matching EPM AiAssistant pattern).
			const launcherBg = document.createElement('span');
			launcherBg.className = 'ai-chatbot-launcher-bg';
			this.launcher.appendChild(launcherBg);
			this.launcher.innerHTML += this.getSparkleSVG();
			this.root.appendChild(this.launcher);

			// 2. Input bar.
			this.inputBar = document.createElement('div');
			this.inputBar.className = 'ai-chatbot-input-bar';
			this.inputBar.innerHTML = `
				<input type="text" placeholder="${this.escapeHtml(this.placeholder)}" aria-label="Your message" autocomplete="off">
				<button class="ai-chatbot-send" aria-label="Send message" disabled>
					${this.getSendSVG()}
				</button>
			`;
			this.root.appendChild(this.inputBar);

			// 3. Conversation panel.
			this.panel = document.createElement('div');
			this.panel.className = 'ai-chatbot-panel';
			this.panel.setAttribute('role', 'dialog');
			this.panel.setAttribute('aria-modal', 'true');
			this.panel.setAttribute('aria-label', this.assistantTitle);
			this.panel.innerHTML = `
				<div class="ai-chatbot-header">
					<div class="ai-chatbot-header-icon">${this.getSparkleSVG()}</div>
					<div class="ai-chatbot-header-title">${this.escapeHtml(this.assistantTitle)}</div>
					<button class="ai-chatbot-header-new" aria-label="New chat" title="New chat">${this.getNewChatSVG()}</button>
					<button class="ai-chatbot-header-close" aria-label="Close chat">
						${this.getCloseSVG()}
					</button>
				</div>
				<div class="ai-chatbot-messages" aria-live="polite" aria-atomic="false"></div>
				<div class="ai-chatbot-panel-input">
					<div class="ai-chatbot-input-bar">
						<input type="text" placeholder="${this.escapeHtml(this.placeholder)}" aria-label="Your message" autocomplete="off">
						<button class="ai-chatbot-send" aria-label="Send message" disabled>
							${this.getSendSVG()}
						</button>
					</div>
				</div>
			`;
			this.root.appendChild(this.panel);

			// Cache message area ref.
			this.messagesArea = this.panel.querySelector('.ai-chatbot-messages');

			// 4. Exit-intent modal.
			this.exitModal = document.createElement('div');
			this.exitModal.className = 'ai-chatbot-exit-modal';
			this.exitModal.setAttribute('role', 'dialog');
			this.exitModal.setAttribute('aria-modal', 'true');
			this.exitModal.setAttribute('aria-label', 'Need help before you go?');
			this.exitModal.innerHTML = `
				<div class="ai-chatbot-panel">
					<div class="ai-chatbot-header">
						<div class="ai-chatbot-header-icon">${this.getSparkleSVG()}</div>
						<div class="ai-chatbot-header-title">Need help before you go?</div>
						<button class="ai-chatbot-header-close" aria-label="Dismiss">
							${this.getCloseSVG()}
						</button>
					</div>
					<div class="ai-chatbot-messages" aria-live="polite" aria-atomic="false"></div>
					<div class="ai-chatbot-panel-input">
						<div class="ai-chatbot-input-bar">
							<input type="text" placeholder="${this.escapeHtml(this.placeholder)}" aria-label="Your message" autocomplete="off">
							<button class="ai-chatbot-send" aria-label="Send message" disabled>
								${this.getSendSVG()}
							</button>
						</div>
					</div>
				</div>
			`;
			this.root.appendChild(this.exitModal);

			// Mount to DOM.
			const mount = document.getElementById('ai-chatbot-root');
			if (mount) {
				mount.appendChild(this.root);
			} else {
				document.body.appendChild(this.root);
			}
		}

		/**
		 * Attach all event listeners.
		 */
		attachEvents() {
			// Launcher click.
			this.launcher.addEventListener('click', this.handleLauncherClick);

			// Input bar events.
			const input1 = this.inputBar.querySelector('input');
			const send1 = this.inputBar.querySelector('.ai-chatbot-send');
			input1.addEventListener('keydown', this.handleInputKeydown);
			input1.addEventListener('input', () => this.updateSendButton(send1, input1));
			send1.addEventListener('click', () => this.handleSendClick(input1));

			// Panel new chat + close.
			const newChatBtn = this.panel.querySelector('.ai-chatbot-header-new');
			newChatBtn.addEventListener('click', () => this.clearMessages());
			const closeBtn = this.panel.querySelector('.ai-chatbot-header-close');
			closeBtn.addEventListener('click', this.handlePanelClose);

			// Panel input events.
			const input2 = this.panel.querySelector('.ai-chatbot-input-bar input');
			const send2 = this.panel.querySelector('.ai-chatbot-input-bar .ai-chatbot-send');
			input2.addEventListener('keydown', this.handleInputKeydown);
			input2.addEventListener('input', () => this.updateSendButton(send2, input2));
			send2.addEventListener('click', () => this.handleSendClick(input2));

			// Click outside to close.
			document.addEventListener('click', this.handleClickOutside);

			// Escape key.
			document.addEventListener('keydown', this.handleEscape);

			// Exit intent (desktop only).
			if (!this.isTouchDevice()) {
				// Track mouse entering viewport.
				document.documentElement.addEventListener('mouseenter', () => {
					this.mouseInViewport = true;
					this.mouseEnterTime = Date.now();
				});
				// Track mouse leaving viewport.
				document.documentElement.addEventListener('mouseleave', this.handleExitIntent);
				// Track scrolling to prevent false exit-intent triggers.
				window.addEventListener('scroll', () => { this.hasScrolled = true; }, { once: true, passive: true });
			}

			// Exit modal close.
			const modalClose = this.exitModal.querySelector('.ai-chatbot-header-close');
			modalClose.addEventListener('click', this.handleModalClose);

			// Click on modal overlay (outside panel) closes modal.
			this.exitModal.addEventListener('click', (e) => {
				if (e.target === this.exitModal) {
					this.handleModalClose();
				}
			});

			// Exit modal input events.
			const input3 = this.exitModal.querySelector('.ai-chatbot-input-bar input');
			const send3 = this.exitModal.querySelector('.ai-chatbot-input-bar .ai-chatbot-send');
			input3.addEventListener('keydown', this.handleInputKeydown);
			input3.addEventListener('input', () => this.updateSendButton(send3, input3));
			send3.addEventListener('click', () => this.handleSendClick(input3));
		}

		/**
		 * Check if device is touch-based.
		 */
		isTouchDevice() {
			return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
		}

		/**
		 * Stage 1 → Stage 2: Launcher click.
		 */
		handleLauncherClick() {
			this.hasInteracted = true;
			this.stage = 'input';
			this.launcher.style.display = 'none';
			this.inputBar.classList.add('ai-chatbot-visible');
			const input = this.inputBar.querySelector('input');
			if (input) {
				setTimeout(() => input.focus(), 50);
			}
		}

		/**
		 * Stage 2 → Stage 3: Input Enter or Send click.
		 */
		handleInputKeydown(e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				const input = e.target;
				if (input.value.trim()) {
					this.sendMessage(input.value.trim(), input);
				}
			} else if (e.key === 'Escape') {
				this.collapseToLauncher();
			}
		}

		/**
		 * Handle send button click.
		 */
		handleSendClick(input) {
			if (input && input.value.trim()) {
				this.sendMessage(input.value.trim(), input);
			}
		}

		/**
		 * Update send button visual state.
		 */
		updateSendButton(btn, input) {
			btn.classList.toggle('ai-chatbot-send-disabled', !input.value.trim());
		}

		/**
		 * Send a message — core chat logic.
		 */
		async sendMessage(text, inputElement) {
			if (this.isLoading) return;

			// Clear input.
			if (inputElement) {
				inputElement.value = '';
			}
			this.updateSendButton(
				inputElement?.closest('.ai-chatbot-input-bar')?.querySelector('.ai-chatbot-send'),
				inputElement
			);

			// Add user message.
			this.messages.push({ role: 'user', content: text });
			this.renderMessages();
			this.isLoading = true;

			// Check if user is submitting lead details (name/email).
			if (this.isLeadSubmission(text)) {
				const lead = this.extractLeadInfo(text);
				if (lead.name && lead.email) {
					// Both found — submit directly.
					this.submitLeadDirectly(lead.name, lead.email, text);
					return;
				}
				if (lead.email) {
					// Only email — show the lead form pre-filled.
					this.showLeadForm(lead.email);
					return;
				}
				if (lead.name) {
					// Only name — show the lead form pre-filled.
					this.showLeadForm('', lead.name);
					return;
				}
			}

			// Add loading class to all send buttons.
			document.querySelectorAll('.ai-chatbot-send').forEach(b => b.classList.add('ai-chatbot-send-loading'));

			// Show loading dots.
			this.showLoading();

			// Transition to panel if not already.
			if (this.stage !== 'panel') {
				this.stage = 'panel';
				this.inputBar.classList.remove('ai-chatbot-visible');
				this.panel.classList.add('ai-chatbot-visible');
			}

			// Call API.
			try {
				const response = await fetch(this.apiUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
				body: JSON.stringify({
					message: text,
					history: this.messages.slice(0, -1).map(m => {
						const h = { role: m.role, content: m.content };
						if (m._tier1_type) h._tier1_type = m._tier1_type;
						if (m._tier1_payload) h._tier1_payload = m._tier1_payload;
						return h;
					}),
				}),
				});

				const data = await response.json();

				// Handle specific error codes from PHP with distinct messages.
				if (data.code === 'credit_exhausted' || data.code === 'no_api_key') {
					this.showCreditFallback();
					return;
				}

				if (data.code === 'rate_limited') {
					this.messages.push({
						role: 'assistant',
						content: "You've sent too many messages — please wait a moment before trying again.",
					});
					this.renderMessages();
					return;
				}

				if (data.code === 'api_error' && data.message) {
					this.messages.push({
						role: 'assistant',
						content: data.message,
					});
					this.renderMessages();
					return;
				}

				if (!response.ok) {
					const errMsg = data.message || `Request failed (${response.status})`;
					this.messages.push({ role: 'assistant', content: errMsg });
					this.renderMessages();
					return;
				}

				const reply = data.reply || data.data?.reply || '';

				if (!reply) {
					throw new Error('Empty response');
				}

				// Store tier1 metadata for follow-up resolution.
				const msg = { role: 'assistant', content: reply };
				if (data._tier1_type) msg._tier1_type = data._tier1_type;
				if (data._tier1_payload) msg._tier1_payload = data._tier1_payload;
				if (data.source) msg.source = data.source;
				this.messages.push(msg);

				// Check if reply asks for lead capture.
				if (this.isLeadRequest(reply)) {
					this.showLeadForm();
				}
			} catch (err) {
				console.error('Chatbot API error:', err);
				this.messages.push({
					role: 'assistant',
					content: "Sorry, I couldn't process that request. Please try again or reach out via the contact form.",
				});
				this.renderMessages();
			} finally {
				this.isLoading = false;
				// Remove loading class from all send buttons.
				document.querySelectorAll('.ai-chatbot-send').forEach(b => b.classList.remove('ai-chatbot-send-loading'));
				this.hideLoading();
				this.renderMessages();
				// Re-enable the panel's send button input listener.
				const panelInput = this.panel.querySelector('.ai-chatbot-input-bar input');
				const panelSend = this.panel.querySelector('.ai-chatbot-input-bar .ai-chatbot-send');
				if (panelInput && panelSend) {
					this.updateSendButton(panelSend, panelInput);
					panelInput.focus();
				}
			}
		}

		/**
		 * Check if the bot response is asking for lead details.
		 */
		isLeadRequest(text) {
			const clean = text.replace(/\*\*/g, ''); // strip markdown bold markers
			const patterns = [
				/name and email/i,
				/your name and email/i,
				/contact information/i,
				/your contact details/i,
				/get in touch/i,
			];
			return patterns.some(p => p.test(clean));
		}

		/**
		 * Check if the user message contains name and/or email.
		 * Only triggers on clear personal-contact signals to avoid
		 * false matches on "domain name:" / "email platform:" etc.
		 */
		isLeadSubmission(text) {
			const lower = text.toLowerCase();
			// "my name is" — clearly providing personal name.
			const hasName = /my name is\s+\w+/i.test(lower);
			// Actual email address with @ — most reliable signal.
			const hasEmail = /[\w.-]+@[\w.-]+\.\w{2,}/i.test(lower);
			return hasName || hasEmail;
		}

		/**
		 * Extract name and email from user message.
		 * Only pulls from "my name is" for name and @-addresses for email
		 * to avoid extracting compound labels like "email platform".
		 */
		extractLeadInfo(text) {
			const nameMatch = text.match(/my name is\s+([^\n,]+)/i);
			const emailMatch = text.match(/([\w.-]+@[\w.-]+\.\w{2,})/i);
			return {
				name: nameMatch ? nameMatch[1].trim() : '',
				email: emailMatch ? emailMatch[1].trim() : '',
			};
		}

		/**
		 * Submit lead directly when user provides name/email in message.
		 * Only shows acknowledgment on a successful API response.
		 */
		async submitLeadDirectly(name, email, fullMessage) {
			try {
				const transcript = this.messages.map(m => `${m.role}: ${m.content}`).join('\n');
				const response = await fetch(this.leadUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ name, email, note: transcript }),
				});

				const content = response.ok
					? `Thanks ${name || 'there'}! I've received your details and will follow up at ${email || 'your email'} soon.`
					: `I couldn't capture your details — please provide a valid name and email address.`;

				this.messages.push({ role: 'assistant', content });
			} catch (err) {
				console.error('Lead submission error:', err);
				this.messages.push({
					role: 'assistant',
					content: 'Something went wrong submitting your details. Feel free to use the contact form instead.',
				});
			} finally {
				this.isLoading = false;
				document.querySelectorAll('.ai-chatbot-send').forEach(b => b.classList.remove('ai-chatbot-send-loading'));
				this.hideLoading();
				this.renderMessages();
			}
		}

		/**
		 * Show inline lead capture form, optionally pre-filled.
		 *
		 * @param {string} prefillEmail Optional email to pre-fill.
		 * @param {string} prefillName  Optional name to pre-fill.
		 */
		showLeadForm(prefillEmail, prefillName) {
			// Remove any existing lead form to avoid duplicates.
			const existing = this.messagesArea.querySelector('.ai-chatbot-lead-form');
			if (existing) existing.remove();

			const form = document.createElement('div');
			form.className = 'ai-chatbot-lead-form';
			form.innerHTML = `
				<div style="font-size:13px;color:var(--cb-muted);margin-bottom:6px;">
					I'd love to discuss this further. Share your details and I'll follow up.
				</div>
				<input type="text" class="ai-chatbot-lead-name" placeholder="Your name" aria-label="Your name" autocomplete="name">
				<input type="email" class="ai-chatbot-lead-email" placeholder="Your email" aria-label="Your email" autocomplete="email">
				<div style="display:flex;gap:8px;">
					<button class="ai-chatbot-lead-submit">Send</button>
					<button class="ai-chatbot-lead-cancel">Cancel</button>
				</div>
			`;

			// Pre-fill values if provided.
			if (prefillName) form.querySelector('.ai-chatbot-lead-name').value = prefillName;
			if (prefillEmail) form.querySelector('.ai-chatbot-lead-email').value = prefillEmail;

			const submitBtn = form.querySelector('.ai-chatbot-lead-submit');
			const cancelBtn = form.querySelector('.ai-chatbot-lead-cancel');

			submitBtn.addEventListener('click', async () => {
				const name = form.querySelector('.ai-chatbot-lead-name').value.trim();
				const email = form.querySelector('.ai-chatbot-lead-email').value.trim();
				if (!name || !email) {
					alert('Please enter both name and email.');
					return;
				}

				// Build conversation transcript.
				const transcript = this.messages
					.map(m => `${m.role}: ${m.content}`)
					.join('\n');

				try {
					const response = await fetch(this.leadUrl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
						},
						body: JSON.stringify({
							name: name,
							email: email,
							note: transcript,
						}),
					});

					if (response.ok) {
						form.innerHTML = '<div style="color:var(--cb-accent);font-size:14px;font-weight:600;">Thanks! I\'ll be in touch soon.</div>';
						setTimeout(() => form.remove(), 4000);
					} else {
						throw new Error('Failed');
					}
				} catch (err) {
					form.innerHTML = '<div style="color:#BD2222;font-size:14px;">Could not send your details. Please use the contact form instead.</div>';
				}
			});

			cancelBtn.addEventListener('click', () => form.remove());

			this.messagesArea.appendChild(form);
			this.scrollToBottom();
		}

		/**
		 * Show fallback message when API credit is exhausted or unavailable.
		 */
		showCreditFallback() {
			this.messages.push({
				role: 'assistant',
				content: "I can't respond right now — my AI service is temporarily unavailable.",
			});

			// Add a contact prompt as a bot message with clickable link.
			const contactMsg = document.createElement('div');
			contactMsg.className = 'ai-chatbot-message ai-chatbot-bot ai-chatbot-fallback';
			contactMsg.innerHTML = `
				<div style="font-size:13px;line-height:1.5;color:var(--cb-text);">
					In the meantime, you can reach out directly through the
					<a href="${this.escapeHtml(this.contactUrl)}" style="color:var(--cb-accent);text-decoration:underline;font-weight:600;">contact form</a>
					and I'll get back to you as soon as possible.
				</div>
				<a href="${this.escapeHtml(this.contactUrl)}"
				   style="display:inline-block;margin-top:8px;padding:8px 16px;border-radius:8px;background:var(--cb-accent);color:#fff;text-decoration:none;font-size:13px;font-weight:600;">
					Send a message →
				</a>
			`;
			const container = this.getActiveMessagesArea();
			if (container) {
				container.appendChild(contactMsg);
				this.scrollToBottom();
			}
		}

		/**
		 * Render all messages in the active panel/messages area.
		 */
		renderMessages() {
			const container = this.getActiveMessagesArea();
			if (!container) return;

			container.innerHTML = '';

			// Show suggestions only if no messages yet.
			if (this.messages.length === 0 && this.suggestions.length > 0) {
				const chips = document.createElement('div');
				chips.className = 'ai-chatbot-suggestions';
				this.suggestions.forEach(text => {
					const chip = document.createElement('button');
					chip.className = 'ai-chatbot-suggestion-chip';
					chip.textContent = text;
					chip.addEventListener('click', () => this.handleSuggestionClick(text));
					chips.appendChild(chip);
				});
				container.appendChild(chips);
			}

			// Render each message.
			this.messages.forEach(msg => {
				const el = document.createElement('div');
				el.className = `ai-chatbot-message ai-chatbot-${msg.role}`;
				if (msg.role === 'assistant') {
					el.innerHTML = this.parseMarkdown(msg.content);
				} else {
					el.textContent = msg.content;
				}
				container.appendChild(el);
			});

			this.scrollToBottom();
		}

		/**
		 * Get the currently active messages container (panel or modal).
		 */
		getActiveMessagesArea() {
			if (this.exitModal.classList.contains('ai-chatbot-visible')) {
				return this.exitModal.querySelector('.ai-chatbot-messages');
			}
			return this.messagesArea;
		}

		/**
		 * Show loading indicator.
		 */
		showLoading() {
			const container = this.getActiveMessagesArea();
			if (!container) return;
			const loading = document.createElement('div');
			loading.className = 'ai-chatbot-loading';
			loading.innerHTML = `
				<div class="ai-chatbot-dot"></div>
				<div class="ai-chatbot-dot"></div>
				<div class="ai-chatbot-dot"></div>
			`;
			loading.id = 'ai-chatbot-loading-indicator';
			container.appendChild(loading);
			this.scrollToBottom();
		}

		/**
		 * Hide loading indicator.
		 */
		hideLoading() {
			const indicator = document.getElementById('ai-chatbot-loading-indicator');
			if (indicator) {
				indicator.remove();
			}
		}

		/**
		 * Scroll to show the latest bot message from the top.
		 */
		scrollToBottom() {
			const container = this.getActiveMessagesArea();
			if (!container) return;
			// Find the last bot message and scroll its top into view.
			const botMessages = container.querySelectorAll('.ai-chatbot-message.ai-chatbot-bot');
			if (botMessages.length > 0) {
				const lastBot = botMessages[botMessages.length - 1];
				// Use requestAnimationFrame to ensure DOM is painted.
				requestAnimationFrame(() => {
					lastBot.scrollIntoView({ block: 'start', behavior: 'smooth' });
				});
			} else {
				container.scrollTop = container.scrollHeight;
			}
		}

		/**
		 * Handle suggestion chip click.
		 */
		handleSuggestionClick(text) {
			this.hasInteracted = true;
			this.sendMessage(text);
		}

		/**
		 * Collapse everything back to launcher.
		 */
		collapseToLauncher() {
			this.stage = 'launcher';
			this.inputBar.classList.remove('ai-chatbot-visible');
			this.panel.classList.remove('ai-chatbot-visible');
			this.exitModal.classList.remove('ai-chatbot-visible');
			this.launcher.style.display = '';
		}

		/**
		 * Close panel (from panel close button).
		 */
		handlePanelClose() {
			this.collapseToLauncher();
		}

		/**
		 * Handle click outside widget.
		 */
		handleClickOutside(e) {
			if (!this.root.contains(e.target)) {
				if (this.stage === 'input' || this.stage === 'panel') {
					this.collapseToLauncher();
				}
				if (this.exitModal.classList.contains('ai-chatbot-visible')) {
					this.handleModalClose();
				}
			}
		}

		/**
		 * Handle Escape key.
		 */
		handleEscape(e) {
			if (e.key === 'Escape') {
				if (this.exitModal.classList.contains('ai-chatbot-visible')) {
					this.handleModalClose();
				} else if (this.stage === 'panel' || this.stage === 'input') {
					this.collapseToLauncher();
				}
			}
		}

		/**
		 * Handle exit-intent (mouse leaving viewport at top).
		 * Only fires after 15 seconds on desktop, without scrolling,
		 * and only if mouse was in viewport for at least 3 seconds.
		 */
		handleExitIntent(e) {
			// Only on desktop, not touch devices.
			if (this.isTouchDevice()) return;
			// Only after 15 seconds on page (prevent refresh triggers).
			if (Date.now() - this.pageLoadTime < 15000) return;
			// Only if user hasn't scrolled (prevents false triggers during scroll).
			if (this.hasScrolled) return;
			// Only if mouse was in viewport for at least 3 seconds.
			if (!this.mouseInViewport || (Date.now() - this.mouseEnterTime < 3000)) return;
			// Only if mouse is at top of viewport.
			if (e.clientY > 20) return;
			// Only if user hasn't interacted and modal not shown.
			if (this.hasInteracted || this.exitModalShown) return;

			this.exitModalShown = true;
			this.exitModal.classList.add('ai-chatbot-visible');

			// Render welcome message in modal if no messages.
			if (this.messages.length === 0) {
				this.messages.push({
					role: 'assistant',
					content: this.welcomeMessage,
				});
			}
			this.renderMessages();
		}

		/**
		 * Close exit-intent modal.
		 */
		handleModalClose() {
			this.exitModal.classList.remove('ai-chatbot-visible');
			this.launcher.style.display = '';
		}

		/**
		 * Clear all messages and start a new chat session.
		 */
		clearMessages() {
			this.messages = [];
			this.hasInteracted = false;
			this.renderMessages();
			localStorage.removeItem('malachy_chatbot_session');
			this.collapseToLauncher();
		}

		/**
		 * Escape HTML to prevent XSS.
		 */
		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}

		/**
		 * Parse basic markdown to HTML for bot messages.
		 * Handles: **bold**, # headings, - lists, newlines.
		 */
		parseMarkdown(text) {
			if (!text) return '';
			let html = this.escapeHtml(text);

			// Defense-in-depth: collapse blank lines between list items
			// to prevent template bugs from fragmenting a single <ul>.
			html = html.replace(/(-\s.+)\n\s*\n(?=-\s)/g, '$1\n');

			// Headings: ### or ## or # at start of line.
			html = html.replace(/^### (.+)$/gm, '<h4>$1</h4>');
			html = html.replace(/^## (.+)$/gm, '<h3>$1</h3>');
			html = html.replace(/^# (.+)$/gm, '<h3>$1</h3>');

			// Bold: **text**.
			html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

			// Italic: *text*.
			html = html.replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>');

			// Unordered lists: lines starting with - .
			html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
			html = html.replace(/(<li>.*<\/li>\n?)+/g, '<ul>$&</ul>');

			// Ordered lists: lines starting with 1. 2. etc.
			html = html.replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>');

			// Line breaks: double newline → paragraph, single newline → <br>.
			html = html.replace(/\n\n+/g, '</p><p>');
			html = html.replace(/\n/g, '<br>');

			// Wrap in paragraph if not already wrapped.
			if (!html.startsWith('<h') && !html.startsWith('<ul') && !html.startsWith('<ol') && !html.startsWith('<p>')) {
				html = '<p>' + html + '</p>';
			}

			return html;
		}

		/**
		 * Sparkle icon SVG.
		 */
		getSparkleSVG() {
			return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2L14.4 9.6L22 12L14.4 14.4L12 22L9.6 14.4L2 12L9.6 9.6L12 2Z" fill="currentColor" stroke="none"/></svg>`;
		}

		/**
		 * New chat / plus icon SVG.
		 */
		getNewChatSVG() {
			return `<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`;
		}

		/**
		 * ArrowUp icon SVG (matching EPM AiAssistant pattern).
		 */
		getSendSVG() {
			return `<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12l7-7 7 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
		}

		/**
		 * Close icon SVG.
		 */
		getCloseSVG() {
			return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6L6 18"/><path d="M6 6L18 18"/></svg>`;
		}
	}

	// Initialize when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => new AIChatbotWidget());
	} else {
		new AIChatbotWidget();
	}
})();
