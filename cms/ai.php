<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security/session.php';

if (!is_logged_in()) {
    header('Location: /cms/admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI-assistent - CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #033234;
            min-height: 100vh;
            color: white;
        }
        .chat-page {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 3.5rem);
            max-width: 52rem;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 2.5rem 0 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        .message-avatar {
            flex-shrink: 0;
            color: rgba(255,255,255,0.7);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 2.25rem;
            line-height: 1;
            padding-top: 0.25rem;
            user-select: none;
        }
        .message-bubble {
            padding: 1.125rem 1.5rem;
            border-radius: 1.125rem;
            background: rgba(55,155,131,0.18);
            border: 1px solid rgba(55,155,131,0.12);
            color: white;
        }
        .message-text {
            font-size: 1.125rem;
            line-height: 1.55;
            font-weight: 500;
        }
        .typing-indicator {
            display: flex;
            gap: 0.375rem;
            padding: 1.125rem 1.5rem;
        }
        .typing-dot {
            width: 0.5rem;
            height: 0.5rem;
            background: rgba(255,255,255,0.50);
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
        }
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        .chat-input-area {
            padding: 1rem 0 1.5rem;
        }
        .chat-input-container {
            position: relative;
            margin-bottom: 0.5rem;
        }
        .chat-input {
            width: 100%;
            padding: 1.125rem 3.75rem 1.125rem 1.5rem;
            background: rgba(55,155,131,0.12);
            border: 1px solid rgba(55,155,131,0.20);
            border-radius: 1rem;
            font-size: 1.0625rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
            color: white;
        }
        .chat-input::placeholder {
            color: rgba(255,255,255,0.30);
        }
        .chat-input:focus {
            border-color: rgba(55,155,131,0.45);
            background: rgba(55,155,131,0.15);
        }
        .send-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.45);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .send-btn:hover {
            background: rgba(55,155,131,0.25);
            color: white;
            border-color: rgba(55,155,131,0.4);
        }
        .send-btn svg {
            width: 1.125rem;
            height: 1.125rem;
        }
        .input-hint {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.25);
            padding-left: 0.25rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <div class="chat-page">
        <div class="chat-messages" id="chatMessages">
            <div class="message">
                <div class="message-avatar">p</div>
                <div class="message-bubble">
                    <p class="message-text">Hej! Jag hjälper dig förstå din webb bättre. Ställ gärna frågor om innehåll, struktur eller status.</p>
                </div>
            </div>
        </div>

        <div class="chat-input-area">
            <div class="chat-input-container">
                <input
                    type="text"
                    class="chat-input"
                    id="chatInput"
                    placeholder="Fråga mig vad som helst..."
                >
                <button class="send-btn" type="button" id="sendButton" title="Skicka">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                </button>
            </div>
            <div class="input-hint">Enter = skicka</div>
        </div>
    </div>

    <script>
        const responses = {
            default: 'Det är en bra fråga. Baserat på webbens nuvarande status ser allt stabilt ut. Om du vill att vi tittar närmare på något specifikt, rekommenderar jag att du kontaktar teamet så kan vi ta det därifrån.',
            struktur: 'Webbens struktur är välorganiserad med tydliga sidor och navigering. Alla huvudsidor är indexerade och tillgängliga. Om du upplever att något saknas eller kan förbättras, kan teamet titta på det som en del av det löpande arbetet.',
            innehåll: 'Innehållet på webben uppdaterades senast i januari. Texterna är anpassade för läsbarhet och tydlighet. Om du har idéer om nytt innehåll eller justeringar, rekommenderar jag att du skickar en förfrågan till teamet.',
            prestanda: 'Webbens laddningstider ligger inom normala värden. Bilder är optimerade och tekniken fungerar som den ska. Skulle något behöva förbättras hanterar teamet det löpande.',
            seo: 'Grundläggande sökmotoroptimering är på plats. Metadata, rubriker och struktur följer god praxis. Kom ihåg att SEO är ett kompletterande mervärde och inte kopplat till specifika resultat.',
            förbättring: 'Jag har identifierat några områden som kan stärkas över tid. Dessa hanteras av teamet som en del av det löpande arbetet. Inga åtgärder krävs från din sida just nu.'
        };

        function getResponse(message) {
            const msg = message.toLowerCase();
            if (msg.includes('struktur') || msg.includes('sidor') || msg.includes('meny')) return responses.struktur;
            if (msg.includes('innehåll') || msg.includes('text')) return responses.innehåll;
            if (msg.includes('snabb') || msg.includes('ladda') || msg.includes('prestanda')) return responses.prestanda;
            if (msg.includes('seo') || msg.includes('sök') || msg.includes('google')) return responses.seo;
            if (msg.includes('förbättr') || msg.includes('bättre') || msg.includes('optimera')) return responses.förbättring;
            return responses.default;
        }

        function addMessage(text, isUser) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message';
            messageDiv.innerHTML = `<div class="message-avatar">p</div><div class="message-bubble"><p class="message-text">${text}</p></div>`;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function showTyping() {
            const messagesDiv = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message';
            typingDiv.id = 'typing';
            typingDiv.innerHTML = '<div class="message-avatar">p</div><div class="message-bubble typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
            messagesDiv.appendChild(typingDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function removeTyping() {
            const typing = document.getElementById('typing');
            if (typing) typing.remove();
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) return;

            addMessage(message, true);
            input.value = '';
            showTyping();

            setTimeout(() => {
                removeTyping();
                const response = getResponse(message);
                addMessage(response, false);
            }, 1000 + Math.random() * 1000);
        }

        document.getElementById('sendButton').addEventListener('click', sendMessage);
        document.getElementById('chatInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    </script>
</body>
</html>
