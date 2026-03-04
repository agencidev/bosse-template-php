<?php
/**
 * AI Agent — Ticket resolution engine
 * Analyzes new tickets via Claude API and executes allowed actions
 */

require_once __DIR__ . '/tickets-db.php';
require_once __DIR__ . '/content.php';

/**
 * Resolve a single ticket via AI
 * @return array Result with keys: success, message, actions[]
 */
function ai_resolve_ticket(int $ticketId): array {
    $ticket = ticket_get($ticketId);
    if (!$ticket) {
        return ['success' => false, 'message' => 'Ticket not found'];
    }

    if (!defined('ANTHROPIC_API_KEY') || ANTHROPIC_API_KEY === '') {
        return ['success' => false, 'message' => 'ANTHROPIC_API_KEY not configured'];
    }

    // Build site context
    $siteContext = build_site_context();

    // Build prompt
    $systemPrompt = build_system_prompt($siteContext);
    $userMessage = build_user_message($ticket);

    // Call Claude API
    $aiResponse = call_claude_api($systemPrompt, $userMessage);
    if (!$aiResponse['success']) {
        return ['success' => false, 'message' => 'API call failed: ' . ($aiResponse['error'] ?? 'unknown')];
    }

    $parsed = parse_ai_response($aiResponse['content']);
    if (!$parsed) {
        // Save raw response anyway
        ticket_update($ticketId, [
            'ai_response' => $aiResponse['content'],
            'ai_resolved_at' => date('Y-m-d H:i:s'),
            'category' => 'general_question',
        ]);
        return ['success' => true, 'message' => 'AI analyzed but could not parse structured response', 'actions' => []];
    }

    // Update category from AI
    $updates = [
        'ai_response' => $parsed['response_text'] ?? $aiResponse['content'],
        'ai_resolved_at' => date('Y-m-d H:i:s'),
    ];

    if (!empty($parsed['category'])) {
        $updates['category'] = $parsed['category'];
    }

    $executedActions = [];

    // Execute actions if confidence is high enough
    if (($parsed['can_resolve'] ?? false) && ($parsed['confidence'] ?? 0) >= 0.7) {
        foreach ($parsed['actions'] ?? [] as $action) {
            $result = execute_ai_action($action);
            $result['timestamp'] = date('Y-m-d H:i:s');
            $executedActions[] = $result;
        }

        $updates['resolved_by'] = 'ai';
        $updates['status'] = 'resolved';
    }

    if (!empty($executedActions)) {
        $updates['ai_actions'] = json_encode($executedActions, JSON_UNESCAPED_UNICODE);
    }

    ticket_update($ticketId, $updates);

    return [
        'success' => true,
        'message' => ($parsed['can_resolve'] ?? false) ? 'AI resolved ticket' : 'AI analyzed ticket',
        'actions' => $executedActions,
    ];
}

/**
 * Process batch of new tickets (max 10)
 * @return array Summary of processing
 */
function ai_process_new_tickets(): array {
    $result = ticket_list(['status' => 'new'], 10, 0);
    $tickets = $result['tickets'];
    $processed = [];

    foreach ($tickets as $ticket) {
        $res = ai_resolve_ticket((int) $ticket['id']);
        $processed[] = [
            'id' => $ticket['id'],
            'subject' => $ticket['subject'],
            'result' => $res,
        ];
    }

    // Update agent state
    $stateFile = DATA_PATH . '/ai-agent-state.json';
    $state = [
        'last_run' => date('Y-m-d H:i:s'),
        'tickets_processed' => count($processed),
        'timestamp' => time(),
    ];
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);

    return $processed;
}

/**
 * Check if AI agent should run (pseudo-cron: >24h since last run + new tickets)
 */
function ai_agent_should_run(): bool {
    $stateFile = DATA_PATH . '/ai-agent-state.json';
    if (!file_exists($stateFile)) {
        return ticket_count_by_status('new') > 0;
    }

    $state = json_decode(file_get_contents($stateFile), true);
    $lastRun = $state['timestamp'] ?? 0;

    // Configurable cooldown (default 4 hours)
    $cooldownSeconds = defined('AI_AGENT_COOLDOWN') ? AI_AGENT_COOLDOWN : 14400;
    if ((time() - $lastRun) > $cooldownSeconds && ticket_count_by_status('new') > 0) {
        return true;
    }

    return false;
}

// --- Internal helpers ---

function build_site_context(): array {
    $context = [
        'site_name' => defined('SITE_NAME') ? SITE_NAME : 'Unknown',
        'site_url' => defined('SITE_URL') ? SITE_URL : '',
    ];

    // Content keys
    $contentFile = DATA_PATH . '/content.json';
    if (file_exists($contentFile)) {
        $content = json_decode(file_get_contents($contentFile), true);
        if (is_array($content)) {
            $context['content_keys'] = array_keys($content);
            // Include a summary of content structure
            $summary = [];
            foreach ($content as $key => $val) {
                if (is_array($val)) {
                    $summary[$key] = array_keys($val);
                }
            }
            $context['content_structure'] = $summary;
        }
    }

    // Available pages
    $pagesDir = ROOT_PATH . '/pages';
    if (is_dir($pagesDir)) {
        $pages = [];
        foreach (scandir($pagesDir) as $f) {
            if ($f === '.' || $f === '..' || is_dir($pagesDir . '/' . $f)) continue;
            if (str_ends_with($f, '.php')) {
                $pages[] = str_replace('.php', '', $f);
            }
        }
        $context['available_pages'] = $pages;
    }

    // Current overrides.css size (to know if CSS changes are possible)
    $overridesPath = ROOT_PATH . '/assets/css/overrides.css';
    if (file_exists($overridesPath)) {
        $context['overrides_css_size'] = filesize($overridesPath);
    }

    return $context;
}

function build_system_prompt(array $siteContext): string {
    $contextJson = json_encode($siteContext, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    return <<<PROMPT
You are a website support AI agent for a PHP-based CMS called Bosse.
Your job is to analyze support tickets and, when possible, resolve them automatically.

Site context:
{$contextJson}

You MUST respond with valid JSON only, no markdown or extra text. Use this exact structure:
{
  "category": "content_change|css_change|seo_change|bug_report|feature_request|general_question",
  "can_resolve": true/false,
  "confidence": 0.0-1.0,
  "response_text": "Human-readable explanation of your analysis and what you did or recommend",
  "actions": [
    {
      "type": "content_change|css_change|seo_change|answer_question",
      "key": "content.json key (for content/seo changes)",
      "field": "field name within the key",
      "value": "new value",
      "css": "CSS rules to append (for css_change only)"
    }
  ]
}

ALLOWED actions:
- content_change: Change text in content.json using save_content_bulk()
- css_change: Append CSS rules to assets/css/overrides.css
- seo_change: Update meta information via content.json
- answer_question: Just provide an answer — no file changes

FORBIDDEN (never do):
- Modify PHP files
- Modify config.php
- Delete any files
- Modify core framework files
- Execute shell commands

Rules:
- Set can_resolve=true ONLY if you are confident the change is safe and correct
- Set confidence >= 0.7 only for clear, straightforward requests
- For ambiguous or risky changes, set can_resolve=false and explain in response_text
- Keep CSS changes minimal and specific
- Always explain what you did or recommend in response_text (in Swedish)
PROMPT;
}

function build_user_message(array $ticket): string {
    $parts = [];
    $parts[] = 'Ämne: ' . ($ticket['subject'] ?? '');
    $parts[] = 'Meddelande: ' . ($ticket['message'] ?? '');
    if (!empty($ticket['name'])) $parts[] = 'Från: ' . $ticket['name'];
    if (!empty($ticket['email'])) $parts[] = 'Email: ' . $ticket['email'];
    $parts[] = 'Källa: ' . ($ticket['source'] === 'admin' ? 'Admin-support' : 'Kontaktformulär');
    return implode("\n", $parts);
}

function call_claude_api(string $systemPrompt, string $userMessage): array {
    $apiKey = ANTHROPIC_API_KEY;
    $model = 'claude-sonnet-4-6';

    $body = json_encode([
        'model' => $model,
        'max_tokens' => 2048,
        'system' => $systemPrompt,
        'messages' => [
            ['role' => 'user', 'content' => $userMessage],
        ],
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => 30,
            'header' => implode("\r\n", [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'User-Agent: BosseTemplate/' . (defined('BOSSE_VERSION') ? BOSSE_VERSION : '0.0.0'),
            ]) . "\r\n",
            'content' => $body,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $response = @file_get_contents('https://api.anthropic.com/v1/messages', false, $context);
    if ($response === false) {
        return ['success' => false, 'error' => 'HTTP request failed'];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }

    if (isset($data['error'])) {
        return ['success' => false, 'error' => $data['error']['message'] ?? 'API error'];
    }

    $content = '';
    foreach ($data['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $content .= $block['text'];
        }
    }

    return ['success' => true, 'content' => $content];
}

function parse_ai_response(string $content): ?array {
    // Try to extract JSON from response
    $content = trim($content);

    // Remove potential markdown code fences
    if (str_starts_with($content, '```')) {
        $content = preg_replace('/^```(?:json)?\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
    }

    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
        return null;
    }

    // Validate required fields
    if (!isset($parsed['category']) || !isset($parsed['response_text'])) {
        return null;
    }

    // Validate category
    $validCategories = ['content_change', 'css_change', 'seo_change', 'bug_report', 'feature_request', 'general_question'];
    if (!in_array($parsed['category'], $validCategories, true)) {
        $parsed['category'] = 'general_question';
    }

    return $parsed;
}

function execute_ai_action(array $action): array {
    $type = $action['type'] ?? '';
    $result = ['type' => $type, 'success' => false];

    switch ($type) {
        case 'content_change':
        case 'seo_change':
            $key = $action['key'] ?? '';
            $field = $action['field'] ?? '';
            $value = $action['value'] ?? '';

            if (empty($key) || $value === '') {
                $result['error'] = 'Missing key or value';
                break;
            }

            // Get current value for before-log
            $content = get_all_content();
            $fullKey = !empty($field) ? $key . '.' . $field : $key;
            $before = get_nested_value($content, $fullKey);

            $saved = save_content_bulk([$fullKey => $value]);
            if ($saved) {
                $result['success'] = true;
                $result['before'] = is_string($before) ? $before : json_encode($before);
                $result['after'] = is_string($value) ? $value : json_encode($value);
            } else {
                $result['error'] = 'Failed to save content';
            }
            break;

        case 'css_change':
            $css = $action['css'] ?? '';
            if (empty($css)) {
                $result['error'] = 'No CSS provided';
                break;
            }

            $overridesPath = ROOT_PATH . '/assets/css/overrides.css';
            $before = file_exists($overridesPath) ? filesize($overridesPath) . ' bytes' : '0 bytes';

            $comment = "\n/* AI-agent change — " . date('Y-m-d H:i:s') . " */\n";
            $appended = file_put_contents($overridesPath, $comment . $css . "\n", FILE_APPEND | LOCK_EX);

            if ($appended !== false) {
                $result['success'] = true;
                $result['before'] = $before;
                $result['after'] = $css;
            } else {
                $result['error'] = 'Failed to write overrides.css';
            }
            break;

        case 'answer_question':
            // No file changes, just the AI response in the ticket
            $result['success'] = true;
            $result['before'] = '';
            $result['after'] = 'Answer provided in ticket response';
            break;

        default:
            $result['error'] = 'Unknown action type: ' . $type;
    }

    return $result;
}

/**
 * Helper: Get nested value from array using dot notation
 */
function get_nested_value(array $data, string $key): mixed {
    $keys = explode('.', $key);
    $current = $data;
    foreach ($keys as $k) {
        if (!is_array($current) || !isset($current[$k])) {
            return null;
        }
        $current = $current[$k];
    }
    return $current;
}
