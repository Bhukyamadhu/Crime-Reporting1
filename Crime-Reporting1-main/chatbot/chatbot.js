(function () {
    "use strict";

    function initChatbot() {
        var widget = document.getElementById("crimeChatbot");
        if (!widget) {
            return;
        }

        var toggleBtn = widget.querySelector(".chatbot-toggle");
        var headerEl = widget.querySelector(".chatbot-header");
        var titleEl = widget.querySelector(".chatbot-title");
        var closeBtn = widget.querySelector(".chatbot-close");
        var sendBtn = widget.querySelector(".chatbot-send");
        var inputWrap = widget.querySelector(".chatbot-input-wrap");
        var inputEl = widget.querySelector(".chatbot-input");
        var messagesEl = widget.querySelector(".chatbot-messages");
        var apiUrl = widget.getAttribute("data-api-url") || "chatbot/chatbot_api.php";
        var logoSrc = resolveLogoSrc(widget, toggleBtn);
        var quickActions = [
            "How do I report a crime?",
            "How can I upload evidence?",
            "How do I track my complaint?",
            "What should I do in an emergency?"
        ];
        var busy = false;
        var greeted = false;
        var typingRow = null;

        enhanceHeader(headerEl, titleEl);
        enhanceToggle(toggleBtn, logoSrc);
        enhanceInputArea(inputWrap, inputEl, sendBtn);

        toggleBtn.addEventListener("click", function () {
            var isOpen = widget.classList.toggle("open");
            toggleBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
            if (isOpen) {
                showGreetingOnce(messagesEl);
                focusInputSoon(inputEl);
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener("click", function () {
                widget.classList.remove("open");
                toggleBtn.setAttribute("aria-expanded", "false");
            });
        }

        sendBtn.addEventListener("click", function () {
            sendMessage();
        });

        inputEl.addEventListener("keydown", function (event) {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });

        function showGreetingOnce(container) {
            if (greeted) {
                return;
            }

            appendMessage(
                container,
                "bot",
                "Hello \u{1F44B}\nI am your Crime Help AI assistant.\n\nI can help you:\n\n• Report crimes\n• Upload evidence\n• Track complaints\n• Contact emergency services"
            );
            appendQuickActions(container, quickActions, function (question) {
                inputEl.value = question;
                sendMessage();
            });
            greeted = true;
        }

        function sendMessage() {
            var message = inputEl.value.trim();
            if (!message || busy) {
                return;
            }

            appendMessage(messagesEl, "user", message);
            inputEl.value = "";
            setSendingState(true);
            showTypingIndicator(messagesEl);

            fetch(apiUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ message: message })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    removeTypingIndicator();
                    if (data && data.success && data.reply) {
                        appendMessage(messagesEl, "bot", data.reply);
                        return;
                    }

                    var errorText = data && data.error ? data.error : "Unable to get response from assistant.";
                    appendMessage(messagesEl, "bot", "Error: " + errorText);
                })
                .catch(function () {
                    removeTypingIndicator();
                    appendMessage(messagesEl, "bot", "I could not reach the chatbot service just now. Please refresh the page and try again.");
                })
                .finally(function () {
                    setSendingState(false);
                    focusInputSoon(inputEl);
                });
        }

        function setSendingState(isSending) {
            busy = isSending;
            sendBtn.disabled = isSending;
            inputEl.disabled = isSending;
            sendBtn.setAttribute("aria-busy", isSending ? "true" : "false");
        }
    }

    function enhanceHeader(headerEl, titleEl) {
        if (!headerEl || !titleEl) {
            return;
        }

        var closeBtn = headerEl.querySelector(".chatbot-close");
        var headerMain = document.createElement("div");
        headerMain.className = "chatbot-header-main";

        var avatar = document.createElement("span");
        avatar.className = "chatbot-avatar";
        avatar.innerHTML = '<i class="fa-solid fa-shield-heart" aria-hidden="true"></i>';

        var titleWrap = document.createElement("div");
        titleWrap.className = "chatbot-title-wrap";

        titleEl.textContent = "Crime Help AI";
        titleWrap.appendChild(titleEl);

        var subtitle = document.createElement("span");
        subtitle.className = "chatbot-subtitle";
        subtitle.textContent = "Your AI assistant for crime reporting";
        titleWrap.appendChild(subtitle);

        headerMain.appendChild(avatar);
        headerMain.appendChild(titleWrap);

        headerEl.innerHTML = "";
        headerEl.appendChild(headerMain);
        if (closeBtn) {
            closeBtn.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
            headerEl.appendChild(closeBtn);
        }
    }

    function enhanceToggle(toggleBtn, logoSrc) {
        if (!toggleBtn) {
            return;
        }

        if (logoSrc) {
            toggleBtn.innerHTML = '<img class="chatbot-toggle-logo" src="' + escapeHtmlAttr(logoSrc) + '" alt="Chatbot logo">';
        }

        toggleBtn.setAttribute("aria-label", "Open chatbot");
        toggleBtn.setAttribute("aria-expanded", "false");
    }

    function resolveLogoSrc(widget, toggleBtn) {
        var dataLogo = widget ? widget.getAttribute("data-logo-src") : "";
        if (dataLogo) {
            return dataLogo;
        }

        var existingLogo = toggleBtn ? toggleBtn.querySelector("img") : null;
        if (existingLogo && existingLogo.getAttribute("src")) {
            return existingLogo.getAttribute("src");
        }

        return "";
    }

    function escapeHtmlAttr(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    function enhanceInputArea(inputWrap, inputEl, sendBtn) {
        if (!inputWrap || !inputEl || !sendBtn) {
            return;
        }

        inputEl.setAttribute("placeholder", "Ask about reporting crimes...");

        var shell = document.createElement("div");
        shell.className = "chatbot-input-shell";

        var attach = document.createElement("span");
        attach.className = "chatbot-attach";
        attach.innerHTML = '<i class="fa-regular fa-face-smile" aria-hidden="true"></i>';

        var icon = document.createElement("span");
        icon.className = "chatbot-input-icon";
        icon.innerHTML = '<i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>';

        inputEl.parentNode.insertBefore(shell, inputEl);
        shell.appendChild(attach);
        shell.appendChild(icon);
        shell.appendChild(inputEl);
        shell.appendChild(sendBtn);

        sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane" aria-hidden="true"></i>';
        sendBtn.setAttribute("aria-label", "Send message");
    }

    function appendMessage(container, role, text) {
        var row = document.createElement("div");
        row.className = "chatbot-row " + role;

        var message = document.createElement("div");
        message.className = "chatbot-message";

        var meta = document.createElement("div");
        meta.className = "chatbot-meta";
        meta.textContent = (role === "user" ? "You" : "Crime Help AI") + " | " + currentTime();

        var bubble = document.createElement("div");
        bubble.className = "chatbot-bubble " + role;
        bubble.textContent = text;

        message.appendChild(meta);
        message.appendChild(bubble);
        row.appendChild(message);
        container.appendChild(row);
        scrollToLatest(container);
    }

    function appendQuickActions(container, actions, onSelect) {
        if (!actions || !actions.length) {
            return;
        }

        var row = document.createElement("div");
        row.className = "chatbot-row bot chatbot-quick-row";

        var message = document.createElement("div");
        message.className = "chatbot-message";

        var shell = document.createElement("div");
        shell.className = "chatbot-quick-actions";

        actions.forEach(function (label) {
            var button = document.createElement("button");
            button.type = "button";
            button.className = "chatbot-quick-action";
            button.textContent = label;
            button.addEventListener("click", function () {
                onSelect(label);
            });
            shell.appendChild(button);
        });

        message.appendChild(shell);
        row.appendChild(message);
        container.appendChild(row);
        scrollToLatest(container);
    }

    function showTypingIndicator(container) {
        typingRow = buildTypingRow();
        container.appendChild(typingRow);
        scrollToLatest(container);
    }

    function removeTypingIndicator() {
        if (typingRow && typingRow.parentNode) {
            typingRow.parentNode.removeChild(typingRow);
        }
        typingRow = null;
    }

    function buildTypingRow() {
        var row = document.createElement("div");
        row.className = "chatbot-row bot";

        var message = document.createElement("div");
        message.className = "chatbot-message";

        var meta = document.createElement("div");
        meta.className = "chatbot-meta";
        meta.textContent = "Crime Help AI | " + currentTime();

        var bubble = document.createElement("div");
        bubble.className = "chatbot-bubble bot typing";
        bubble.innerHTML = 'AI is thinking... <span class="chatbot-typing-dots"><span></span><span></span><span></span></span>';

        message.appendChild(meta);
        message.appendChild(bubble);
        row.appendChild(message);
        return row;
    }

    function currentTime() {
        return new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    }

    function scrollToLatest(container) {
        container.scrollTop = container.scrollHeight;
    }

    function focusInputSoon(inputEl) {
        window.setTimeout(function () {
            inputEl.focus();
        }, 120);
    }

    document.addEventListener("DOMContentLoaded", initChatbot);
})();
