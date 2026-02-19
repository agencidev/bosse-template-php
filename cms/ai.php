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
            color: rgba(255,255,255,1.0);
        }
        .page-content {
            padding: 3rem 1.5rem;
        }
        .container {
            max-width: 48rem;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            color: rgba(255,255,255,0.50);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: rgba(255,255,255,1.0);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: rgba(255,255,255,1.0);
        }
        .status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 9999px;
            padding: 0.5rem 1rem;
        }
        .status-dot {
            width: 0.5rem;
            height: 0.5rem;
            background: #22c55e;
            border-radius: 50%;
        }
        .status-text {
            color: #166534;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .chat-card {
            background: linear-gradient(to bottom, rgba(255,255,255,0.03), rgba(255,255,255,0.05));
            border-radius: 1.5rem;
            border: 1px solid rgba(255,255,255,0.10);
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .chat-messages {
            height: 500px;
            overflow-y: auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .message {
            display: flex;
        }
        .message-user {
            justify-content: flex-end;
        }
        .message-assistant {
            justify-content: flex-start;
        }
        .message-bubble {
            max-width: 75%;
            padding: 1rem 1.25rem;
            border-radius: 1.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .message-user .message-bubble {
            background: #379b83;
            color: white;
            border-bottom-right-radius: 0.25rem;
        }
        .message-assistant .message-bubble {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            color: rgba(255,255,255,1.0);
            border-bottom-left-radius: 0.25rem;
        }
        .message-text {
            font-size: 0.875rem;
            line-height: 1.6;
        }
        .typing-indicator {
            display: flex;
            gap: 0.375rem;
            padding: 1rem 1.25rem;
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
        .chat-input-wrapper {
            border-top: 1px solid rgba(255,255,255,0.10);
            background: rgba(255,255,255,0.05);
            padding: 1.5rem;
        }
        .chat-input-container {
            position: relative;
            margin-bottom: 1rem;
        }
        .chat-input {
            width: 100%;
            padding: 1rem 6rem 1rem 1.25rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 9999px;
            font-size: 0.875rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
            color: white;
        }
        .chat-input:focus {
            border-color: #379b83;
            box-shadow: 0 0 0 3px rgba(55, 155, 131, 0.1);
        }
        .chat-input-actions {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 0.5rem;
        }
        .icon-button {
            padding: 0.5rem;
            background: none;
            border: none;
            color: rgba(255,255,255,0.50);
            cursor: pointer;
            border-radius: 9999px;
            transition: all 0.2s;
        }
        .icon-button:hover {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,1.0);
        }
        .icon-button svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .quick-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 9999px;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.50);
            cursor: pointer;
            transition: all 0.2s;
        }
        .quick-action:hover {
            background: rgba(255,255,255,0.08);
        }
        .quick-action svg {
            width: 1rem;
            height: 1rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .info-card {
            background: rgba(255,255,255,0.05);
            border-radius: 1rem;
            border: 1px solid rgba(255,255,255,0.10);
            padding: 1rem;
        }
        .info-card-label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.50);
            margin-bottom: 0.25rem;
        }
        .info-card-value {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255,255,255,1.0);
        }
        .info-card-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-text {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.50);
            text-align: center;
            line-height: 1.6;
        }
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-bar.php'; ?>
    <div class="page-content">
    <div class="container">
        <a href="/dashboard" class="back-link">← Tillbaka</a>
        
        <div class="header">
            <h1 class="title">AI-assistent</h1>
            <div class="status-badge">
                <div class="status-dot"></div>
                <span class="status-text">Aktiv</span>
            </div>
        </div>

        <div class="chat-card">
            <div class="chat-messages" id="chatMessages">
                <div class="message message-assistant">
                    <div class="message-bubble">
                        <p class="message-text">Hej! Jag är här för att hjälpa dig förstå din webb bättre. Ställ gärna frågor om webbens innehåll, struktur eller status så svarar jag utifrån vad jag vet.</p>
                    </div>
                </div>
            </div>

            <div class="chat-input-wrapper">
                <div class="chat-input-container">
                    <input 
                        type="text" 
                        class="chat-input" 
                        id="chatInput"
                        placeholder="Fråga mig vad som helst..."
                    >
                    <div class="chat-input-actions">
                        <button class="icon-button" type="button" id="sendButton" title="Skicka">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="quick-actions">
                    <button class="quick-action" onclick="setQuestion('Hur ser webbens struktur ut?')">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Översikt
                    </button>
                    <button class="quick-action" onclick="setQuestion('Vad kan förbättras på webben?')">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        Förbättringar
                    </button>
                    <button class="quick-action" onclick="setQuestion('Hur är webbens prestanda?')">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Prestanda
                    </button>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-label">Status</div>
                <div class="info-card-status">
                    <div class="status-dot"></div>
                    <span class="info-card-value">Aktiv</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-label">Senaste analys</div>
                <span class="info-card-value">13 januari 2026</span>
            </div>

            <div class="info-card">
                <div class="info-card-label">Övervakar</div>
                <span class="info-card-value">Innehåll, struktur, status</span>
            </div>
        </div>

        <p class="info-text">
            AI-assistenten ger svar baserat på webbens aktuella status. Förbättringar hanteras alltid av teamet.
        </p>
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
            messageDiv.className = `message message-${isUser ? 'user' : 'assistant'}`;
            messageDiv.innerHTML = `<div class="message-bubble"><p class="message-text">${text}</p></div>`;
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function showTyping() {
            const messagesDiv = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message message-assistant';
            typingDiv.id = 'typing';
            typingDiv.innerHTML = `<div class="message-bubble typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>`;
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

        function setQuestion(question) {
            document.getElementById('chatInput').value = question;
        }

        document.getElementById('sendButton').addEventListener('click', sendMessage);
        document.getElementById('chatInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    </script>
</body>
</html>
